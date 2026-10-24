<?php

namespace Tests\Feature;

use App\Enums\AttachmentType;
use App\Enums\LiveSyncSection;
use App\Enums\PublishState;
use App\Models\Attachment;
use App\Models\Page;
use App\Models\TcePackage;
use App\Models\User;
use App\Services\LiveSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * Pulling the live site's posts, media and code into a local instance (LiveSyncService). The
 * "live" side is this same test database: a section is exported, the local state is changed,
 * and the export is served back through Http::fake().
 */
class LiveSyncTest extends TestCase
{
    use RefreshDatabase;

    private const LIVE = 'https://live.test';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function service(): LiveSyncService
    {
        return app(LiveSyncService::class);
    }

    private function storedAttachment(string $path, string $file_name, string $content, AttachmentType $type = AttachmentType::IMAGE): Attachment
    {
        Storage::disk('public')->put("$path/$file_name", $content);

        return Attachment::factory()->create([
            // Stored like the uploader stores it: with a leading slash.
            'path' => "/$path",
            'file_name' => $file_name,
            'hash' => hash('sha256', $content),
            'type' => $type,
            'url' => "/storage/$path/$file_name",
        ]);
    }

    private function zipContent(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'live-sync-test-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function test_exporting_a_section_needs_a_token(): void
    {
        $private_page = Page::factory()->create(['publish_state' => PublishState::PRIVATE]);

        $this->getJson('/api/v1/live-sync/posts')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/live-sync/posts')
            ->assertOk()
            ->assertJsonPath('section', 'posts')
            ->assertJsonPath('tables.pages.0.id', $private_page->id)
            ->assertJsonStructure(['tables' => ['pages', 'page_page', 'page_project', 'attachables', 'nova_trix_attachments', 'attachments']]);
        $this->getJson('/api/v1/live-sync/users')->assertNotFound();
    }

    public function test_pulling_posts_replaces_the_local_posts_and_downloads_their_files(): void
    {
        $live_page = Page::factory()->create(['publish_state' => PublishState::PRIVATE]);
        $cover = $this->storedAttachment('media/live-cover', 'cover.png', 'live image');
        $live_page->attachments()->attach($cover->id, ['relation' => 'thumbnail']);
        $payload = $this->service()->export(LiveSyncSection::POSTS);

        // The local instance before the pull: another page, and the file is missing.
        DB::table('attachables')->delete();
        DB::table('pages')->delete();
        Storage::disk('public')->deleteDirectory('media/live-cover');
        $local_page = Page::factory()->create();

        Http::fake([
            'live.test/api/v1/live-sync/posts' => Http::response($payload),
            'live.test/storage/media/live-cover/cover.png' => Http::response('live image'),
        ]);
        $result = $this->service()->pull(LiveSyncSection::POSTS, self::LIVE, 'live-token');

        $this->assertSame([$live_page->id], DB::table('pages')->pluck('id')->all());
        $this->assertFalse(DB::table('pages')->where('id', $local_page->id)->exists());
        $this->assertSame($cover->id, $live_page->fresh()->thumbnailAttachment()?->id);
        $this->assertSame('live image', Storage::disk('public')->get('media/live-cover/cover.png'));
        $this->assertSame(['downloaded' => 1, 'present' => 0, 'failed' => []], $result['files']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer live-token')
            && $request->url() === self::LIVE.'/api/v1/live-sync/posts');
    }

    public function test_pulling_media_removes_local_attachments_that_live_does_not_have(): void
    {
        $live_attachment = $this->storedAttachment('media/on-live', 'a.png', 'a');
        $payload = $this->service()->export(LiveSyncSection::MEDIA);

        $local_only = $this->storedAttachment('media/local-only', 'b.png', 'b');
        Page::factory()->create()->attachments()->attach($local_only->id, ['relation' => 'attachment']);

        Http::fake(['live.test/api/v1/live-sync/media' => Http::response($payload)]);
        $result = $this->service()->pull(LiveSyncSection::MEDIA, self::LIVE, 'live-token');

        $this->assertSame([$live_attachment->id], DB::table('attachments')->pluck('id')->all());
        $this->assertFalse(DB::table('attachables')->where('attachment_id', $local_only->id)->exists());
        $this->assertFalse(Storage::disk('public')->exists('media/local-only'));
        $this->assertSame(['downloaded' => 0, 'present' => 1, 'failed' => []], $result['files']);
    }

    public function test_files_only_downloads_and_unpacks_a_package_zip_without_a_token(): void
    {
        $zip = $this->zipContent(['dist/index.js' => 'export {};', 'dist/index.global.js' => '(function(){})();']);
        $code = $this->storedAttachment('tce-packages/pkg', 'tobidot-tce-clock.zip', $zip, AttachmentType::ZIP);
        $package = TcePackage::query()->create([
            'namespace' => 'tobidot', 'name' => 'tce-clock', 'kind' => 'element', 'major' => 0, 'minor' => 0, 'patch' => 1,
        ]);
        $package->attachments()->attach($code->id, ['relation' => 'code']);
        Storage::disk('public')->deleteDirectory('tce-packages/pkg');

        Http::fake(['live.test/storage/tce-packages/pkg/tobidot-tce-clock.zip' => Http::response($zip)]);
        $result = $this->service()->pull(LiveSyncSection::CODE, self::LIVE, null, with_rows: false);

        $this->assertSame(['downloaded' => 1, 'present' => 0, 'failed' => []], $result['files']);
        $this->assertSame('export {};', Storage::disk('public')->get('tce-packages/pkg/index.js'));
        Http::assertSentCount(1);
    }

    public function test_a_file_that_does_not_match_its_hash_is_not_stored(): void
    {
        $this->storedAttachment('media/tampered', 'a.png', 'expected');
        Storage::disk('public')->deleteDirectory('media/tampered');

        Http::fake(['live.test/storage/*' => Http::response('something else')]);
        $result = $this->service()->pull(LiveSyncSection::MEDIA, self::LIVE, null, with_rows: false);

        $this->assertCount(1, $result['files']['failed']);
        $this->assertStringContainsString('sha256', $result['files']['failed'][0]);
        $this->assertFalse(Storage::disk('public')->exists('media/tampered/a.png'));
    }

    public function test_a_path_outside_the_disk_is_never_written(): void
    {
        Attachment::factory()->create(['path' => '/../outside', 'file_name' => 'a.png', 'hash' => null, 'type' => AttachmentType::IMAGE]);

        Http::fake();
        $result = $this->service()->pull(LiveSyncSection::MEDIA, self::LIVE, null, with_rows: false);

        $this->assertStringContainsString('unsafe path', $result['files']['failed'][0]);
        Http::assertNothingSent();
    }

    public function test_rows_of_another_section_are_rejected_and_nothing_changes(): void
    {
        $page = Page::factory()->create();
        $payload = $this->service()->export(LiveSyncSection::POSTS);
        $payload['tables']['attachables'][] = [
            'attachment_id' => 1, 'attachable_type' => (new TcePackage)->getMorphClass(), 'attachable_id' => 1, 'relation' => 'code',
        ];

        Http::fake(['live.test/*' => Http::response($payload)]);
        try {
            $this->service()->pull(LiveSyncSection::POSTS, self::LIVE, 'live-token');
            $this->fail('A foreign row was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('another section', $exception->getMessage());
        }
        $this->assertTrue(DB::table('pages')->where('id', $page->id)->exists());
    }

    public function test_never_pulls_from_itself(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->pull(LiveSyncSection::POSTS, (string) config('app.url'), 'live-token');
    }

    public function test_never_pulls_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->service()->pull(LiveSyncSection::POSTS, self::LIVE, 'live-token');
    }

    public function test_the_command_pulls_the_given_sections(): void
    {
        $this->storedAttachment('media/cmd', 'a.png', 'a');
        Storage::disk('public')->deleteDirectory('media/cmd');
        Http::fake(['live.test/storage/media/cmd/a.png' => Http::response('a')]);

        $this->artisan('live:pull', ['sections' => ['media'], '--files-only' => true, '--url' => self::LIVE])
            ->expectsConfirmation('Replace all local media with the data from '.self::LIVE.'?', 'yes')
            ->expectsOutputToContain('1 files downloaded')
            ->assertSuccessful();
        $this->assertSame('a', Storage::disk('public')->get('media/cmd/a.png'));
    }
}
