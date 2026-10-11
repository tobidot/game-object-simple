<?php

namespace Tests\Feature;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Helpers\ViewHelper;
use App\Models\Attachment;
use App\Models\Page;
use App\Models\Project;
use App\Nova\Actions\UploadAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Tests\TestCase;

class PageAttachmentAndThumbnailTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_can_have_attachments_and_resolves_thumbnail_from_attachment(): void
    {
        Storage::fake('public');

        /** @var Page $page */
        $page = Page::factory()->create([
            'title' => 'Sample Engine Overview',
            'uri' => 'sample-engine-overview',
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $this->assertNull($page->thumbnail);
        $this->assertNull($page->thumbnailAttachment());

        // Create attachment
        /** @var Attachment $attachment */
        $attachment = Attachment::create([
            'path' => 'media/thumbnails',
            'file_name' => 'engine-cover.png',
            'url' => '/storage/media/thumbnails/engine-cover.png',
            'content_type' => 'image/png',
            'publish_state' => PublishState::PUBLISHED,
            'type' => AttachmentType::IMAGE,
        ]);

        $page->attachments()->attach($attachment->id, ['relation' => 'thumbnail']);

        $page->refresh();

        $this->assertNotNull($page->thumbnailAttachment());
        $this->assertEquals($attachment->id, $page->thumbnailAttachment()->id);
        $this->assertStringContainsString('engine-cover.png', $page->thumbnail);
    }

    public function test_upload_attachment_action_attaches_thumbnail_to_page(): void
    {
        Storage::fake('public');

        /** @var Page $page */
        $page = Page::factory()->create([
            'title' => 'Nova Page Test',
            'uri' => 'nova-page-test',
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $file = UploadedFile::fake()->image('nova-cover.png', 400, 300);

        $action = new UploadAttachment;
        $fields = new ActionFields(collect([
            'file' => $file,
            'prefix' => 'media',
            'relation' => 'thumbnail',
        ]), collect([]));

        $response = $action->handle($fields, collect([$page]));

        $this->assertInstanceOf(ActionResponse::class, $response);

        $page->refresh();
        $this->assertCount(1, $page->attachments);
        $this->assertEquals('thumbnail', $page->attachments()->first()->pivot->relation);
        $this->assertNotNull($page->thumbnail);
        $this->assertStringContainsString('nova-cover.png', $page->thumbnail);
    }

    public function test_view_helper_renders_image_for_attachment_and_string(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test-art.png', 'fake-image-bytes');

        $attachment = Attachment::create([
            'path' => 'media',
            'file_name' => 'test-art.png',
            'url' => '/storage/media/test-art.png',
            'content_type' => 'image/png',
            'publish_state' => PublishState::PUBLISHED,
            'type' => AttachmentType::IMAGE,
        ]);

        $urlFromAttachment = ViewHelper::mediaUrl($attachment);
        $this->assertStringContainsString('test-art.png', $urlFromAttachment);

        $htmlFromAttachment = ViewHelper::mediaImageHtml($attachment, 'Test Art Cover');
        $this->assertStringContainsString('<img', $htmlFromAttachment);
        $this->assertStringContainsString('alt="Test Art Cover"', $htmlFromAttachment);

        $urlFromString = ViewHelper::mediaUrl('/storage/media/test-art.png');
        $this->assertStringContainsString('test-art.png', $urlFromString);

        $emptyHtml = ViewHelper::mediaImageHtml(null);
        $this->assertEquals('', $emptyHtml);
    }

    public function test_excerpt_view_renders_placeholder_when_thumbnail_is_empty(): void
    {
        /** @var Page $pageWithoutThumb */
        $pageWithoutThumb = Page::factory()->create([
            'title' => 'Page No Thumb',
            'uri' => 'page-no-thumb',
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $response = $this->get(route('pages.show', ['page' => $pageWithoutThumb]));
        $response->assertStatus(200);

        // Render page excerpt view directly
        $view = $this->blade('<x-excerpts.page :model="$page" />', ['page' => $pageWithoutThumb]);
        $view->assertSee('excerpt__thumbnail--placeholder', false);
        $view->assertSee('excerpt__placeholder', false);
    }

    public function test_excerpt_view_renders_image_when_page_has_thumbnail(): void
    {
        Storage::fake('public');

        /** @var Page $pageWithThumb */
        $pageWithThumb = Page::factory()->create([
            'title' => 'Page With Thumb',
            'uri' => 'page-with-thumb',
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $attachment = Attachment::create([
            'path' => 'media',
            'file_name' => 'attached-thumb.png',
            'url' => '/storage/media/attached-thumb.png',
            'content_type' => 'image/png',
            'publish_state' => PublishState::PUBLISHED,
            'type' => AttachmentType::IMAGE,
        ]);

        $pageWithThumb->attachments()->attach($attachment->id, ['relation' => 'thumbnail']);
        $pageWithThumb->refresh();

        $view = $this->blade('<x-excerpts.page :model="$page" />', ['page' => $pageWithThumb]);
        $view->assertSee('<img', false);
        $view->assertSee('attached-thumb.png', false);
        $view->assertDontSee('excerpt__thumbnail--placeholder', false);
    }

    public function test_project_excerpt_renders_placeholder_and_image(): void
    {
        Storage::fake('public');

        /** @var Project $projectWithoutThumb */
        $projectWithoutThumb = Project::factory()->create([
            'title' => 'Project No Thumb',
            'publish_state' => PublishState::PUBLISHED,
            'thumbnail' => null,
        ]);

        $viewWithout = $this->blade('<x-excerpts.project :model="$project" />', ['project' => $projectWithoutThumb]);
        $viewWithout->assertSee('excerpt__thumbnail--placeholder', false);
        $viewWithout->assertSee('excerpt__placeholder', false);

        /** @var Project $projectWithThumb */
        $projectWithThumb = Project::factory()->create([
            'title' => 'Project With Thumb',
            'publish_state' => PublishState::PUBLISHED,
            'thumbnail' => '/storage/media/project-thumb.png',
        ]);

        $viewWith = $this->blade('<x-excerpts.project :model="$project" />', ['project' => $projectWithThumb]);
        $viewWith->assertSee('<img', false);
        $viewWith->assertSee('project-thumb.png', false);
        $viewWith->assertDontSee('excerpt__thumbnail--placeholder', false);
    }
}
