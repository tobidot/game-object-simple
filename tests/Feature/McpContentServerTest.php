<?php

namespace Tests\Feature;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Mcp\Servers\ContentServer;
use App\Mcp\Tools\AttachMediaTool;
use App\Mcp\Tools\CreatePageTool;
use App\Mcp\Tools\DetachMediaTool;
use App\Mcp\Tools\GetMediaTool;
use App\Mcp\Tools\GetPageTool;
use App\Mcp\Tools\ListMediaTool;
use App\Mcp\Tools\ListPagesTool;
use App\Mcp\Tools\UpdateMediaTool;
use App\Mcp\Tools\UpdatePageTool;
use App\Mcp\Tools\UploadMediaTool;
use App\Models\Attachment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpContentServerTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_mcp_endpoint_requires_a_sanctum_token(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'upload_media']);
    }

    public function test_create_page_defaults_to_private_and_demotes_h1(): void
    {
        ContentServer::tool(CreatePageTool::class, [
            'title' => 'Hello World',
            'uri' => 'hello-world',
            'content' => '<h1>Intro</h1><p>Text</p>',
        ])->assertOk()->assertHasNoErrors();

        $page = Page::query()->withoutGlobalScopes()->where('uri', 'hello-world')->firstOrFail();
        $this->assertSame(PublishState::PRIVATE, $page->publish_state);
        $this->assertSame('<h2>Intro</h2><p>Text</p>', $page->content);
    }

    public function test_create_page_rejects_duplicate_uri(): void
    {
        Page::factory()->create(['uri' => 'taken']);

        ContentServer::tool(CreatePageTool::class, [
            'title' => 'Dup',
            'uri' => 'taken',
            'content' => '<p>x</p>',
        ])->assertHasErrors(['uri']);
    }

    public function test_update_page_changes_only_given_fields(): void
    {
        $page = Page::factory()->create(['title' => 'Old', 'uri' => 'old', 'publish_state' => PublishState::PRIVATE]);
        $related = Page::factory()->create();

        ContentServer::tool(UpdatePageTool::class, [
            'id' => $page->id,
            'title' => 'New',
            'publish_state' => 'published',
            'related_page_ids' => [$related->id],
        ])->assertOk()->assertHasNoErrors();

        $page = Page::query()->withoutGlobalScopes()->findOrFail($page->id);
        $this->assertSame('New', $page->title);
        $this->assertSame('old', $page->uri);
        $this->assertSame(PublishState::PUBLISHED, $page->publish_state);
        $this->assertSame([$related->id], $page->pages()->withoutGlobalScopes()->pluck('pages.id')->all());
    }

    public function test_list_and_get_pages_include_private_pages(): void
    {
        $page = Page::factory()->create(['title' => 'Secret Draft', 'uri' => 'secret', 'publish_state' => PublishState::PRIVATE, 'content' => '<p>Draft body</p>']);

        ContentServer::tool(ListPagesTool::class, ['search' => 'Secret'])
            ->assertOk()
            ->assertSee('Secret Draft');

        ContentServer::tool(GetPageTool::class, ['uri' => 'secret'])
            ->assertOk()
            ->assertSee('Draft body');
    }

    public function test_upload_media_from_base64_and_set_as_thumbnail(): void
    {
        $page = Page::factory()->create();

        ContentServer::tool(UploadMediaTool::class, [
            'file_name' => 'My Cover.PNG',
            'content_base64' => 'data:image/png;base64,'.self::PNG_1PX,
            'page_id' => $page->id,
            'relation' => 'thumbnail',
        ])->assertOk()->assertHasNoErrors()->assertSee('my-cover.png');

        $attachment = Attachment::query()->sole();
        $this->assertSame(AttachmentType::IMAGE, $attachment->type);
        Storage::disk('public')->assertExists($attachment->path.'/my-cover.png');
        $this->assertSame($attachment->id, $page->refresh()->thumbnailAttachment()?->id);
    }

    public function test_upload_media_from_source_url(): void
    {
        Http::fake(['example.com/*' => Http::response(base64_decode(self::PNG_1PX), 200, ['Content-Type' => 'image/png'])]);

        ContentServer::tool(UploadMediaTool::class, ['source_url' => 'https://example.com/images/remote.png'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('remote.png');

        $this->assertSame('remote.png', Attachment::query()->sole()->file_name);
    }

    public function test_upload_media_requires_exactly_one_source(): void
    {
        ContentServer::tool(UploadMediaTool::class, ['file_name' => 'x.png'])
            ->assertHasErrors();

        ContentServer::tool(UploadMediaTool::class, [
            'file_name' => 'x.png',
            'content_base64' => self::PNG_1PX,
            'source_url' => 'https://example.com/x.png',
        ])->assertHasErrors();
    }

    public function test_update_media_renames_file_and_changes_publish_state(): void
    {
        ContentServer::tool(UploadMediaTool::class, ['file_name' => 'a.png', 'content_base64' => self::PNG_1PX]);
        $attachment = Attachment::query()->sole();

        ContentServer::tool(UpdateMediaTool::class, [
            'id' => $attachment->id,
            'file_name' => 'b.png',
            'publish_state' => 'private',
        ])->assertOk()->assertHasNoErrors();

        $attachment->refresh();
        $this->assertSame('b.png', $attachment->file_name);
        $this->assertSame(PublishState::PRIVATE, $attachment->publish_state);
        $this->assertStringEndsWith('/b.png', $attachment->url);
        Storage::disk('public')->assertExists($attachment->path.'/b.png');
        Storage::disk('public')->assertMissing($attachment->path.'/a.png');
    }

    public function test_attach_list_get_and_detach_media(): void
    {
        $page = Page::factory()->create();
        ContentServer::tool(UploadMediaTool::class, ['file_name' => 'pic.png', 'content_base64' => self::PNG_1PX]);
        $attachment = Attachment::query()->sole();

        ContentServer::tool(AttachMediaTool::class, ['attachment_id' => $attachment->id, 'page_id' => $page->id, 'relation' => 'thumbnail'])
            ->assertOk()->assertHasNoErrors();
        // attaching twice must not create duplicate pivot rows
        ContentServer::tool(AttachMediaTool::class, ['attachment_id' => $attachment->id, 'page_id' => $page->id, 'relation' => 'thumbnail']);
        $this->assertSame(1, $page->attachments()->count());

        ContentServer::tool(ListMediaTool::class, ['page_id' => $page->id])->assertOk()->assertSee('pic.png');
        ContentServer::tool(GetMediaTool::class, ['id' => $attachment->id, 'include_preview' => true])
            ->assertOk()
            ->assertSee('thumbnail');

        ContentServer::tool(DetachMediaTool::class, ['attachment_id' => $attachment->id, 'page_id' => $page->id])
            ->assertOk();
        $this->assertSame(0, $page->attachments()->count());
    }

    public function test_rest_attachment_upload_endpoint(): void
    {
        $page = Page::factory()->create();

        $this->post('/api/v1/attachments', ['file' => UploadedFile::fake()->createWithContent('big.png', base64_decode(self::PNG_1PX))], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/v1/attachments', [
            'file' => UploadedFile::fake()->createWithContent('big.png', base64_decode(self::PNG_1PX)),
            'page_id' => $page->id,
            'relation' => 'thumbnail',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('attachment.file_name', 'big.png');

        $this->assertSame('big.png', $page->refresh()->thumbnailAttachment()?->file_name);
    }
}
