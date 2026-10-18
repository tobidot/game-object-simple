<?php

namespace Tests\Feature;

use App\Helpers\AppHelper;
use App\Services\Models\TcePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * The generator reads everything from the registry: the SDK packages it vendors and the
 * packages a project may depend on — see "TCE Project Generator".
 */
class TceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tce-gen-test-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'package.zip', 'application/zip', null, true);
    }

    private function publishSdk(): void
    {
        $service = AppHelper::resolve(TcePackageService::class);
        $service->upload('tobidot', 'common', $this->zip([
            'package.json' => '{"name":"@tce-v3/common","version":"0.1.0","main":"src/index.ts"}',
            'src/index.ts' => 'export const common = true;',
        ]), '0.1.0', 'sdk');
        $service->upload('tobidot', 'desktop-protocol', $this->zip([
            'package.json' => '{"name":"@tce-v3/desktop-protocol","version":"0.1.0","main":"src/index.ts"}',
            'src/index.ts' => 'export const protocol = true;',
        ]), '0.1.0', 'sdk');
    }

    private function publishLibrary(string $name = 'dll-tce-file-system'): void
    {
        AppHelper::resolve(TcePackageService::class)->upload('tobidot', $name, $this->zip([
            'dist/index.js' => 'export {};',
            'dist/index.global.js' => '(function(){})();',
        ]), '1.2.0', 'library', 'A library.');
    }

    private function openZip($response): ZipArchive
    {
        /** @var BinaryFileResponse $binary */
        $binary = $response->baseResponse;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($binary->getFile()->getPathname()));

        return $zip;
    }

    public function test_generator_page_lists_registry_packages_and_needs_the_sdk(): void
    {
        $this->publishLibrary();

        $this->get('/tce-generator')
            ->assertOk()
            ->assertSee('TCE Project Generator')
            ->assertSee('tobidot/dll-tce-file-system')
            ->assertSee('not published to this');

        $this->publishSdk();

        $this->get('/tce-generator')->assertOk()->assertDontSee('not published to this');
    }

    public function test_standard_element_project_is_complete_and_vendors_the_sdk(): void
    {
        $this->publishSdk();
        $this->publishLibrary();

        $response = $this->post('/tce-generator', [
            'name' => 'tce-space-invaders',
            'title' => 'Space Invaders',
            'description' => 'A classic arcade clone.',
            'version' => '0.1.0',
            'kind' => 'element',
            'width' => 800,
            'height' => 600,
            'template' => 'standard',
            'dependencies' => ['tobidot/dll-tce-file-system'],
        ]);

        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('tce-space-invaders-0.1.0.zip', (string) $response->headers->get('content-disposition'));

        $zip = $this->openZip($response);
        foreach ([
            'package.json', 'tsconfig.json', 'vite.config.ts', 'eslint.config.js', 'tobidot-project.json',
            'index.html', 'README.md', '.gitignore', 'icon.svg', 'scripts/pack.mjs',
            'src/index.ts', 'src/vite-env.d.ts', 'src/applet.scss',
            'src/TceSpaceInvaders.ts', 'src/TceSpaceInvadersSettings.ts', 'src/TceSpaceInvadersReferences.ts',
            'src/TceSpaceInvadersProperties.ts', 'src/TceSpaceInvadersRenderer.ts', 'src/TceSpaceInvadersLogic.ts',
            'src/contracts/dll-tce-file-system.contract.ts',
            'vendor/tce-v3-common/package.json', 'vendor/tce-v3-common/src/index.ts',
            'vendor/tce-v3-desktop-protocol/package.json',
        ] as $file) {
            $this->assertNotFalse($zip->locateName("tce-space-invaders/$file"), "missing $file");
        }
        $this->assertFalse($zip->locateName('tce-space-invaders/vendor/tce-v3-common/package.zip'), 'the uploaded zip itself must not be vendored');

        $package_json = json_decode($zip->getFromName('tce-space-invaders/package.json'), true);
        $this->assertSame('tce-space-invaders', $package_json['name']);
        $this->assertSame('file:vendor/tce-v3-common', $package_json['dependencies']['@tce-v3/common']);
        $this->assertSame('file:vendor/tce-v3-desktop-protocol', $package_json['dependencies']['@tce-v3/desktop-protocol']);
        $this->assertArrayHasKey('zod', $package_json['dependencies']);
        $this->assertArrayNotHasKey('dll-tce-file-system', $package_json['dependencies'], 'registry packages are runtime dependencies, not npm ones');

        $manifest = json_decode($zip->getFromName('tce-space-invaders/tobidot-project.json'), true);
        $this->assertSame('tobidot', $manifest['namespace']);
        $this->assertSame('tce-space-invaders', $manifest['name']);
        $this->assertContains('element', $manifest['informal']['categories']);
        $this->assertSame(['width' => 800, 'height' => 600], $manifest['resolution']);
        $this->assertSame([['identifier' => ['namespace' => 'tobidot', 'name' => 'dll-tce-file-system']]], $manifest['dependencies']);

        $index = $zip->getFromName('tce-space-invaders/src/index.ts');
        $this->assertStringContainsString("TceRoot.bind_self(package_identifier, tce_space_invaders_package", $index);
        $this->assertStringContainsString("{ namespace: 'tobidot', name: 'tce-space-invaders' }", $index);
        $this->assertStringContainsString("{ major: 0, minor: 1, patch: 0 }", $index);
        $this->assertStringContainsString("name: 'dll-tce-file-system'", $index);

        $this->assertStringContainsString('/tce-elements/tobidot/dll-tce-file-system', $zip->getFromName('tce-space-invaders/index.html'));
        $this->assertStringContainsString("formats: ['es', 'iife']", $zip->getFromName('tce-space-invaders/vite.config.ts'));
        $this->assertStringContainsString('explicit-member-accessibility', $zip->getFromName('tce-space-invaders/eslint.config.js'));
        $this->assertStringContainsString('<svg', $zip->getFromName('tce-space-invaders/icon.svg'));
        $this->assertStringContainsString('dependencies[0][name]=dll-tce-file-system', $zip->getFromName('tce-space-invaders/README.md'));
        $zip->close();
    }

    public function test_library_project_has_no_window_and_no_desktop_protocol(): void
    {
        $this->publishSdk();

        $response = $this->post('/tce-generator', ['name' => 'dll-my-greeter', 'kind' => 'library']);
        $response->assertOk();

        $zip = $this->openZip($response);
        $this->assertNotFalse($zip->locateName('dll-my-greeter/src/DllMyGreeter.ts'));
        $this->assertFalse($zip->locateName('dll-my-greeter/src/applet.scss'));
        $this->assertFalse($zip->locateName('dll-my-greeter/vendor/tce-v3-desktop-protocol/package.json'));

        $package_json = json_decode($zip->getFromName('dll-my-greeter/package.json'), true);
        $this->assertArrayNotHasKey('@tce-v3/desktop-protocol', $package_json['dependencies']);
        $this->assertArrayNotHasKey('sass', $package_json['devDependencies']);

        $manifest = json_decode($zip->getFromName('dll-my-greeter/tobidot-project.json'), true);
        $this->assertContains('library', $manifest['informal']['categories']);
        $this->assertSame(['width' => 0, 'height' => 0], $manifest['resolution']);
        $this->assertStringContainsString("kind: 'library'", $zip->getFromName('dll-my-greeter/src/index.ts'));
        $this->assertStringContainsString('extends TcePackage', $zip->getFromName('dll-my-greeter/src/DllMyGreeter.ts'));
        $zip->close();
    }

    public function test_minimal_and_canvas_presets(): void
    {
        $this->publishSdk();

        $zip = $this->openZip($this->post('/tce-generator', ['name' => 'tce-tiny', 'template' => 'minimal'])->assertOk());
        $this->assertNotFalse($zip->locateName('tce-tiny/src/TceTiny.ts'));
        $this->assertFalse($zip->locateName('tce-tiny/src/TceTinyLogic.ts'));
        $zip->close();

        $zip = $this->openZip($this->post('/tce-generator', ['name' => 'tce-bounce', 'template' => 'canvas'])->assertOk());
        $this->assertStringContainsString('ResizeObserver', $zip->getFromName('tce-bounce/src/TceBounceLogic.ts'));
        $this->assertStringContainsString('HTMLCanvasElement', $zip->getFromName('tce-bounce/src/TceBounceReferences.ts'));
        $zip->close();
    }

    public function test_uploaded_icon_is_kept_as_is(): void
    {
        $this->publishSdk();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64"/></svg>';

        $zip = $this->openZip($this->post('/tce-generator', [
            'name' => 'tce-icon-app',
            'icon' => UploadedFile::fake()->createWithContent('custom.svg', $svg),
        ])->assertOk());
        $this->assertSame($svg, $zip->getFromName('tce-icon-app/icon.svg'));
        $zip->close();
    }

    public function test_invalid_input_is_rejected_not_rewritten(): void
    {
        $this->publishSdk();

        $this->from('/tce-generator')->post('/tce-generator', [
            'name' => 'NoHyphen',
            'version' => 'not-semver',
            'width' => 10,
        ])->assertSessionHasErrors(['name', 'version', 'width']);

        $this->from('/tce-generator')->post('/tce-generator', [
            'name' => 'tce-fine',
            'dependencies' => ['tobidot/dll-does-not-exist'],
        ])->assertSessionHasErrors(['dependencies']);
    }

    public function test_generation_refuses_without_published_sdk(): void
    {
        $this->postJson('/api/public/tce-generator', ['name' => 'tce-no-sdk'])->assertStatus(503);
    }

    public function test_api_returns_the_archive_as_base64(): void
    {
        $this->publishSdk();

        $response = $this->postJson('/api/public/tce-generator', [
            'name' => 'tce-api-element',
            'title' => 'API Element',
            'version' => '1.0.0',
            'width' => 400,
            'height' => 300,
        ]);

        $response->assertOk()->assertJsonStructure([
            'success', 'message', 'filename', 'config' => ['namespace', 'name', 'className', 'title', 'version', 'sdk'], 'archive_base64',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertSame('tce-api-element-1.0.0.zip', $response->json('filename'));
        $this->assertSame('TceApiElement', $response->json('config.className'));

        $path = tempnam(sys_get_temp_dir(), 'tce-api-').'.zip';
        file_put_contents($path, base64_decode($response->json('archive_base64')));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $this->assertNotFalse($zip->locateName('tce-api-element/src/TceApiElement.ts'));
        $zip->close();
        @unlink($path);
    }

    public function test_artisan_command_generates_zip_and_directory(): void
    {
        $this->publishSdk();
        $this->publishLibrary('dll-tce-settings');

        $target_zip = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tce-cli-element-0.1.0.zip';
        @unlink($target_zip);

        $this->artisan('tce:generate', [
            'name' => 'tce-cli-element',
            '--title' => 'CLI Element',
            '--dependency' => ['tobidot/dll-tce-settings'],
            '--output' => $target_zip,
        ])->assertSuccessful();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($target_zip));
        $this->assertNotFalse($zip->locateName('tce-cli-element/src/TceCliElement.ts'));
        $this->assertNotFalse($zip->locateName('tce-cli-element/src/contracts/dll-tce-settings.contract.ts'));
        $zip->close();
        @unlink($target_zip);

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tce-cli-dir-'.uniqid();
        $this->artisan('tce:generate', ['name' => 'dll-cli-lib', '--kind' => 'library', '--output' => $directory])->assertSuccessful();
        $this->assertFileExists("$directory/src/DllCliLib.ts");
        $this->assertFileExists("$directory/vendor/tce-v3-common/package.json");

        $this->artisan('tce:generate', ['name' => 'bad', '--output' => $directory])->assertFailed();
    }
}
