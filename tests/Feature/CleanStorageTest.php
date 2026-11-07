<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Page;
use App\Models\TcePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanStorageTest extends TestCase
{
    use RefreshDatabase;

    private const KEPT = [
        'media/page-thumb/shot.png',
        'media/mentioned-in-markdown.png',
        'media/mentioned-in-trix.png',
        'media/with%20space.png',
        'media/go-logo/abc/intro.mp4',
        'tce-packages/kept/index.js',
        'tce-packages/kept/index.wasm',
        'avatars/not-managed.png',
        'releases/.gitignore',
    ];

    private const REMOVED = [
        'media/never-mentioned.png',
        'media/unlinked-row/file.png',
        'releases/old-1-0-0/old.zip',
        'releases/old-1-0-0/nested/index.html',
        'tobidot-elements/some-uuid/index.js',
        'tce-packages/retired/index.js',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        foreach ([...self::KEPT, ...self::REMOVED] as $file) {
            Storage::disk('public')->put(urldecode($file), 'x');
        }
        Storage::disk('public')->put(
            'tce-packages/kept/index.js',
            'const intro = "https://www.game-object.de/storage/media/go-logo/abc/intro.mp4";',
        );

        $page = Page::factory()->create([
            'content' => '![a](https://www.game-object.de/media/mentioned-in-markdown.png) ![b](/storage/media/with%20space.png)',
            'content_html_legacy' => '<img src="/media/mentioned-in-trix.png">',
        ]);
        $this->link($page, 'App\Models\Page', '/media/page-thumb', 'thumbnail');

        $package = TcePackage::query()->create([
            'namespace' => 'tobidot',
            'name' => 'tce-kept',
            'kind' => 'element',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
        ]);
        $this->link($package, 'App\Models\TcePackage', '/tce-packages/kept', 'code');

        Attachment::factory()->create(['path' => '/media/unlinked-row', 'url' => '/storage/media/unlinked-row']);
    }

    private function link(object $model, string $type, string $path, string $relation): void
    {
        $attachment = Attachment::factory()->create(['path' => $path, 'url' => '/storage'.$path]);
        DB::table('attachables')->insert([
            'attachment_id' => $attachment->id,
            'attachable_id' => $model->id,
            'attachable_type' => $type,
            'relation' => $relation,
        ]);
    }

    public function test_without_delete_nothing_changes(): void
    {
        $this->artisan('app:clean-storage')->assertSuccessful();

        foreach ([...self::KEPT, ...self::REMOVED] as $file) {
            Storage::disk('public')->assertExists(urldecode($file));
        }
        $this->assertDatabaseHas('attachments', ['path' => '/media/unlinked-row']);
    }

    public function test_delete_removes_only_what_nothing_references(): void
    {
        $this->artisan('app:clean-storage', ['--delete' => true])->assertSuccessful();

        foreach (self::KEPT as $file) {
            Storage::disk('public')->assertExists(urldecode($file));
        }
        foreach (self::REMOVED as $file) {
            Storage::disk('public')->assertMissing($file);
        }
        $this->assertDatabaseMissing('attachments', ['path' => '/media/unlinked-row']);
        $this->assertDatabaseHas('attachments', ['path' => '/media/page-thumb']);
        $this->assertDatabaseHas('attachments', ['path' => '/tce-packages/kept']);
    }

    public function test_empty_folders_go_too(): void
    {
        $this->artisan('app:clean-storage', ['--delete' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('tobidot-elements/some-uuid');
        Storage::disk('public')->assertMissing('releases/old-1-0-0/nested');
        Storage::disk('public')->assertExists('tce-packages/kept');
    }

    public function test_names_with_a_backslash_go_with_their_folder(): void
    {
        Storage::disk('public')->put('releases/windows-zip/keep-the-folder-open.txt', 'x');
        file_put_contents(Storage::disk('public')->path('releases/windows-zip').'/dist\\app.js', 'x');
        file_put_contents(Storage::disk('public')->path('releases/windows-zip')."/dist\u{F05C}app.js", 'x');

        $this->artisan('app:clean-storage', ['--delete' => true])->assertSuccessful();

        $this->assertDirectoryDoesNotExist(Storage::disk('public')->path('releases/windows-zip'));
    }

    public function test_a_dotfile_inside_a_removed_folder_goes_too(): void
    {
        Storage::disk('public')->put('tobidot-elements/some-uuid/.hidden', 'x');

        $this->artisan('app:clean-storage', ['--delete' => true])->assertSuccessful();

        $this->assertDirectoryDoesNotExist(Storage::disk('public')->path('tobidot-elements/some-uuid'));
    }

    public function test_refuses_when_nothing_is_linked(): void
    {
        DB::table('attachables')->delete();

        $this->artisan('app:clean-storage', ['--delete' => true])->assertFailed();

        Storage::disk('public')->assertExists('tce-packages/kept/index.js');
    }

    public function test_only_stale_chunked_uploads_are_removed(): void
    {
        Storage::disk('local')->put('chunks/upl_old/0', 'x');
        touch(Storage::disk('local')->path('chunks/upl_old/0'), now()->subDays(2)->getTimestamp());
        Storage::disk('local')->put('chunks/upl_new/0', 'x');

        $this->artisan('app:clean-storage', ['--delete' => true])->assertSuccessful();

        Storage::disk('local')->assertMissing('chunks/upl_old/0');
        Storage::disk('local')->assertExists('chunks/upl_new/0');
    }
}
