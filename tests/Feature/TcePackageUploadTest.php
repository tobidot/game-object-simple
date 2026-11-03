<?php

namespace Tests\Feature;

use App\Models\TcePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tobidot\ChunkedUpload\ChunkManager;
use ZipArchive;

/**
 * `POST /api/v1/tce-packages` and the public registry — see "Publishing & Discovery".
 */
class TcePackageUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $chunk_dir;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 8"><rect width="8" height="8"/></svg>';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $this->chunk_dir = sys_get_temp_dir().'/tce-chunks-'.uniqid();
        $this->app->instance(ChunkManager::class, new ChunkManager($this->chunk_dir));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->chunk_dir);
        parent::tearDown();
    }

    private function distZip(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tce-upload-test-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('dist/index.js', 'export {};');
        $zip->addFromString('dist/index.global.js', '(function(){})();');
        $zip->close();

        return new UploadedFile($path, 'release.zip', 'application/zip', null, true);
    }

    public function test_upload_with_dependencies_size_and_svg_icon(): void
    {
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'dll-tce-file-system', 'kind' => 'library', 'zip' => $this->distZip(),
        ])->assertOk();

        $response = $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot',
            'name' => 'tce-notepad',
            'kind' => 'element',
            'version' => '0.2.0',
            'description' => 'Notepad',
            'width' => 480,
            'height' => 360,
            'icon' => UploadedFile::fake()->createWithContent('icon.svg', self::SVG),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'dll-tce-file-system']],
            'tags' => ['tool', 'files'],
            'zip' => $this->distZip(),
        ]);

        $response->assertOk()
            ->assertJsonPath('package.major', 0)
            ->assertJsonPath('package.minor', 2)
            ->assertJsonPath('package.width', 480)
            ->assertJsonPath('package.dependencies.0.identifier.name', 'dll-tce-file-system')
            ->assertJsonPath('package.tags', ['tool', 'files']);
        $this->assertNotNull($response->json('package.icon'));

        $registry = $this->getJson('/api/public/tce-packages')->assertOk();
        $this->assertArrayHasKey('tobidot/tce-notepad', $registry->json('packages'));
        $this->assertSame('tobidot', $registry->json('packages.tobidot/tce-notepad.0.namespace'));
        $this->assertSame(['tool', 'files'], $registry->json('packages.tobidot/tce-notepad.0.tags'));
        $this->assertSame([], $registry->json('packages.tobidot/dll-tce-file-system.0.tags'));
    }

    /** Posts `$bytes` as `$count` chunks of upload `$upload_id`; returns the last response. */
    private function postChunks(string $bytes, string $upload_id, int $count): TestResponse
    {
        $size = (int) ceil(strlen($bytes) / $count);
        $response = null;
        foreach (str_split($bytes, $size) as $index => $part) {
            $response = $this->post('/api/v1/tce-packages/chunks', [
                'upload_id' => $upload_id,
                'chunk_index' => $index,
                'total_chunks' => $count,
                'chunk' => UploadedFile::fake()->createWithContent("chunk_$index", $part),
            ], ['Accept' => 'application/json']);
        }

        return $response;
    }

    public function test_a_zip_sent_in_chunks_is_published_and_its_chunks_cleaned_up(): void
    {
        $zip = $this->distZip();
        $bytes = file_get_contents($zip->getPathname());

        $this->postChunks($bytes, 'abc-123', 3)->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('size', strlen($bytes));

        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-big-game', 'version' => '1.0.0', 'zip_upload_id' => 'abc-123',
        ])->assertOk()->assertJsonPath('package.name', 'tce-big-game');
        $this->getJson('/api/public/tce-packages')->assertOk()->assertJsonPath('packages.tobidot/tce-big-game.0.major', 1);

        // The session is gone: the same id cannot publish twice.
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-big-game', 'version' => '1.0.1', 'zip_upload_id' => 'abc-123',
        ])->assertStatus(422)->assertJsonValidationErrors('zip_upload_id');
    }

    public function test_chunk_uploads_are_scoped_to_the_user_and_need_every_chunk(): void
    {
        $bytes = file_get_contents($this->distZip()->getPathname());
        $this->postChunks($bytes, 'shared-id', 2)->assertJsonPath('status', 'completed');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-big-game', 'zip_upload_id' => 'shared-id',
        ])->assertStatus(422)->assertJsonValidationErrors('zip_upload_id');

        // Only the first of two chunks: not assembled, not publishable.
        $this->post('/api/v1/tce-packages/chunks', [
            'upload_id' => 'half', 'chunk_index' => 0, 'total_chunks' => 2,
            'chunk' => UploadedFile::fake()->createWithContent('chunk_0', substr($bytes, 0, 10)),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('status', 'chunk_uploaded');
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-big-game', 'zip_upload_id' => 'half',
        ])->assertStatus(422)->assertJsonValidationErrors('zip_upload_id');

        $this->post('/api/v1/tce-packages/chunks', [
            'upload_id' => 'bad', 'chunk_index' => 2, 'total_chunks' => 2,
            'chunk' => UploadedFile::fake()->createWithContent('chunk_2', 'x'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('chunk_index');
    }

    public function test_missing_dependency_and_bad_version_are_rejected(): void
    {
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-explorer', 'zip' => $this->distZip(),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'dll-tce-file-system']],
        ])->assertStatus(422);
        $this->assertDatabaseMissing('tce_packages', ['name' => 'tce-explorer']);

        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'tce-clock', 'version' => '1.0.0', 'zip' => $this->distZip()])->assertOk();
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'tce-clock', 'version' => '1.0.0', 'zip' => $this->distZip()])->assertStatus(422);
    }

    public function test_runtime_package_names_must_be_tag_names_but_sdk_names_need_not(): void
    {
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'nohyphen', 'zip' => $this->distZip()])->assertStatus(422);
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'common', 'kind' => 'sdk', 'zip' => $this->distZip()])->assertOk();
    }

    public function test_sdk_packages_are_not_listed_in_the_registry_and_have_no_embed(): void
    {
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'common', 'kind' => 'sdk', 'zip' => $this->distZip()])->assertOk();
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'tce-clock', 'zip' => $this->distZip()])->assertOk();

        $packages = $this->getJson('/api/public/tce-packages')->assertOk()->json('packages');
        $this->assertSame(['tobidot/tce-clock'], array_keys($packages));
        $this->assertSame(1, TcePackage::query()->where('kind', 'sdk')->count());
    }

    public function test_play_route_serves_the_latest_element_with_its_dependency_chain(): void
    {
        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'dll-tce-settings', 'kind' => 'library', 'zip' => $this->distZip()])->assertOk();
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-settings', 'zip' => $this->distZip(),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'dll-tce-settings']],
        ])->assertOk();

        $this->get('/tce/tce-settings')
            ->assertOk()
            ->assertSee('<tce-settings>', false)
            ->assertSeeInOrder(['/tce-elements/tobidot/dll-tce-settings', '/tce-elements/tobidot/tce-settings'], false);
    }

    public function test_homepage_is_the_desktop_once_published_and_the_regular_homepage_before(): void
    {
        $this->get('/')->assertOk()->assertDontSee('<tce-desktop', false);
        $this->get('/v3')->assertNotFound();

        $this->postJson('/api/v1/tce-packages', ['namespace' => 'tobidot', 'name' => 'tce-bootloader', 'zip' => $this->distZip()])->assertOk();
        $this->postJson('/api/v1/tce-packages', [
            'namespace' => 'tobidot', 'name' => 'tce-desktop', 'zip' => $this->distZip(),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'tce-bootloader']],
        ])->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('<tce-desktop', false)
            ->assertSeeInOrder(['/tce-elements/tobidot/tce-bootloader', '/tce-elements/tobidot/tce-desktop'], false);
        $this->get('/v3')->assertOk()->assertSee('<tce-desktop', false);
    }
}
