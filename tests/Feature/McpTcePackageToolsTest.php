<?php

namespace Tests\Feature;

use App\Mcp\Servers\ContentServer;
use App\Mcp\Tools\ListTcePackagesTool;
use App\Mcp\Tools\UploadTcePackageTool;
use App\Models\TcePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class McpTcePackageToolsTest extends TestCase
{
    use RefreshDatabase;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 8"><rect width="8" height="8"/></svg>';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function zipBase64(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tce-test-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('dist/index.js', 'export {};');
        $zip->addFromString('dist/index.global.js', '(function(){})();');
        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return base64_encode($bytes);
    }

    public function test_upload_publishes_a_package_with_icon_and_dependencies(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-icon',
            'zip_base64' => $this->zipBase64(),
        ])->assertOk()->assertHasNoErrors();

        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-desktop',
            'zip_base64' => $this->zipBase64(),
            'icon_base64' => base64_encode(self::SVG),
            'kind' => 'element',
            'description' => 'Desktop',
            'width' => 800,
            'height' => 600,
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'tce-icon']],
        ])->assertOk()->assertHasNoErrors()->assertSee('tce-desktop');

        $desktop = TcePackage::query()->where('name', 'tce-desktop')->firstOrFail();
        $this->assertSame('tobidot', $desktop->namespace);
        $this->assertSame([0, 0, 1], [$desktop->major, $desktop->minor, $desktop->patch]);
        $this->assertSame(800, (int) $desktop->width);
        $this->assertNotNull($desktop->iconAttachment()->first());
        $this->assertSame(['tce-icon'], $desktop->dependencies->pluck('name')->all());
    }

    public function test_upload_bumps_patch_and_carries_icon_over(): void
    {
        foreach (range(1, 2) as $_) {
            ContentServer::tool(UploadTcePackageTool::class, [
                'name' => 'tce-notepad',
                'zip_base64' => $this->zipBase64(),
                'icon_base64' => $_ === 1 ? base64_encode(self::SVG) : null,
            ])->assertOk()->assertHasNoErrors();
        }

        $latest = TcePackage::query()->where('name', 'tce-notepad')->orderByDesc('patch')->firstOrFail();
        $this->assertSame(2, $latest->patch);
        $this->assertNotNull($latest->iconAttachment()->first());
    }

    public function test_omitted_dependencies_carry_over_and_empty_list_clears_them(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, ['name' => 'tce-icon', 'zip_base64' => $this->zipBase64()])->assertOk();
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-desktop',
            'zip_base64' => $this->zipBase64(),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'tce-icon']],
        ])->assertOk()->assertHasNoErrors();

        ContentServer::tool(UploadTcePackageTool::class, ['name' => 'tce-desktop', 'zip_base64' => $this->zipBase64()])
            ->assertOk()->assertHasNoErrors();
        $second = TcePackage::query()->where('name', 'tce-desktop')->where('patch', 2)->firstOrFail();
        $this->assertSame(['tce-icon'], $second->dependencies->pluck('name')->all());

        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-desktop', 'zip_base64' => $this->zipBase64(), 'dependencies' => [],
        ])->assertOk()->assertHasNoErrors();
        $third = TcePackage::query()->where('name', 'tce-desktop')->where('patch', 3)->firstOrFail();
        $this->assertSame([], $third->dependencies->pluck('name')->all());
    }

    public function test_upload_rejects_missing_dependency_without_creating_the_package(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-desktop',
            'zip_base64' => $this->zipBase64(),
            'dependencies' => [['namespace' => 'tobidot', 'name' => 'tce-window']],
        ])->assertHasErrors(['tobidot/tce-window']);

        $this->assertDatabaseMissing('tce_packages', ['name' => 'tce-desktop']);
    }

    public function test_upload_rejects_non_zip_content(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-icon',
            'zip_base64' => base64_encode('not a zip'),
        ])->assertHasErrors(['valid zip']);
    }

    public function test_upload_rejects_a_version_not_higher_than_the_latest(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-icon', 'zip_base64' => $this->zipBase64(), 'version' => '1.0.0',
        ])->assertOk()->assertHasNoErrors();

        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-icon', 'zip_base64' => $this->zipBase64(), 'version' => '0.9.0',
        ])->assertHasErrors(['higher than last release']);
    }

    public function test_sdk_packages_upload_as_sources_and_stay_out_of_the_registry(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tce-sdk-test-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('package.json', '{"name":"@tce-v3/common"}');
        $zip->addFromString('src/index.ts', 'export {};');
        $zip->close();
        $sdk_base64 = base64_encode((string) file_get_contents($path));
        unlink($path);

        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'common', 'kind' => 'sdk', 'zip_base64' => $sdk_base64,
        ])->assertOk()->assertHasNoErrors();
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'tce-icon', 'zip_base64' => $this->zipBase64(),
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame('sdk', TcePackage::query()->where('name', 'common')->firstOrFail()->kind);
        $this->assertSame(['tobidot/tce-icon'], array_keys($this->getJson('/api/public/tce-packages')->json('packages')));
        $this->get('/tce-elements/tobidot/common')->assertNotFound();
    }

    public function test_runtime_package_names_must_be_valid_tag_names(): void
    {
        ContentServer::tool(UploadTcePackageTool::class, [
            'name' => 'nohyphen', 'zip_base64' => $this->zipBase64(),
        ])->assertHasErrors(['not a valid package name']);
    }

    public function test_list_shows_latest_versions_by_default_and_all_on_request(): void
    {
        foreach (['tce-icon', 'tce-icon', 'tce-window'] as $name) {
            ContentServer::tool(UploadTcePackageTool::class, [
                'name' => $name, 'zip_base64' => $this->zipBase64(),
            ])->assertOk();
        }

        ContentServer::tool(ListTcePackagesTool::class, [])
            ->assertOk()
            ->assertSee('"total":2');

        ContentServer::tool(ListTcePackagesTool::class, ['all_versions' => true])
            ->assertOk()
            ->assertSee('"total":3');
    }
}
