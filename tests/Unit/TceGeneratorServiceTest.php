<?php

namespace Tests\Unit;

use App\DTO\TceGeneratorConfig;
use App\Services\TceGeneratorService;
use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class TceGeneratorServiceTest extends TestCase
{
    private TceGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TceGeneratorService;
    }

    public function test_prepare_config_normalizes_names_and_generates_class_name(): void
    {
        $config = $this->service->prepareConfig([
            'name' => 'super-game',
            'title' => 'Super Game',
            'version' => '1.2.3',
            'kind' => 'element',
            'width' => 640,
            'height' => 480,
            'template' => 'canvas',
        ]);

        $this->assertEquals('super-game', $config['name']);
        $this->assertEquals('SuperGame', $config['className']);
        $this->assertEquals('Super Game', $config['title']);
        $this->assertEquals('1.2.3', $config['version']);
        $this->assertEquals('element', $config['kind']);
        $this->assertEquals(640, $config['width']);
        $this->assertEquals(480, $config['height']);
        $this->assertEquals('canvas', $config['template']);
        $this->assertArrayHasKey('@game.object/tce-common', $config['dependencies']);
    }

    public function test_prepare_config_adds_tce_prefix_if_name_has_no_hyphen(): void
    {
        $config = $this->service->prepareConfig([
            'name' => 'calculator',
        ]);

        $this->assertEquals('tce-calculator', $config['name']);
        $this->assertEquals('Calculator', $config['className']);
    }

    public function test_get_available_tce_elements_returns_defaults(): void
    {
        $elements = $this->service->getAvailableTceElements();

        $this->assertIsArray($elements);
        $this->assertArrayHasKey('dll-tce-desktop', $elements);
        $this->assertArrayHasKey('@game.object/tce-common', $elements);
        $this->assertEquals('library', $elements['dll-tce-desktop']['kind']);
    }

    public function test_prepare_config_merges_dependencies(): void
    {
        $config = $this->service->prepareConfig([
            'name' => 'tce-widget',
            'tce_dependencies' => [
                'dll-tce-desktop:^1.0.0',
                'dll-tce-file-system@^1.2.0',
            ],
            'dependencies' => [
                '@game.object/ts-game-toolbox',
            ],
            'custom_dependencies' => "three:^0.160.0\nlodash-es@^4.17.21",
        ]);

        $deps = $config['dependencies'];
        $this->assertArrayHasKey('@game.object/tce-common', $deps);
        $this->assertArrayHasKey('@game.object/ts-game-toolbox', $deps);
        $this->assertArrayHasKey('dll-tce-desktop', $deps);
        $this->assertArrayHasKey('dll-tce-file-system', $deps);
        $this->assertEquals('^1.0.0', $deps['dll-tce-desktop']);
        $this->assertEquals('^1.2.0', $deps['dll-tce-file-system']);
        $this->assertEquals('^0.160.0', $deps['three']);
        $this->assertEquals('^4.17.21', $deps['lodash-es']);

        // Check structured TCE dependencies for tobidot-project.json
        $tceDeps = $config['tceDependencies'];
        $this->assertNotEmpty($tceDeps);
        $names = array_column(array_column($tceDeps, 'identifier'), 'name');
        $this->assertContains('dll-tce-desktop', $names);
        $this->assertContains('dll-tce-file-system', $names);
    }

    public function test_generate_project_files_creates_all_expected_files(): void
    {
        $config = $this->service->prepareConfig([
            'name' => 'tce-clock',
            'title' => 'TCE Clock',
            'template' => 'standard',
        ]);

        $files = $this->service->generateProjectFiles($config);

        $expectedKeys = [
            'package.json',
            'tobidot-project.json',
            'tsconfig.json',
            'vite.config.ts',
            'index.html',
            '.gitignore',
            'README.md',
            'Makefile',
            'VERSION',
            'src/index.ts',
            'src/Clock.ts',
            'src/applet.html',
            'src/applet.scss',
            'src/import.ts',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $files, "Missing expected file: $key");
        }

        // Verify valid JSON content
        $packageJson = json_decode($files['package.json'], true);
        $this->assertIsArray($packageJson);
        $this->assertEquals('tce-clock', $packageJson['name']);
        $this->assertEquals('1.0.0', $packageJson['version']);

        $tobidotProjectJson = json_decode($files['tobidot-project.json'], true);
        $this->assertIsArray($tobidotProjectJson);
        $this->assertEquals('TCE Clock', $tobidotProjectJson['title']);
        $this->assertEquals(400, $tobidotProjectJson['resolution']['width']);
        $this->assertEquals(300, $tobidotProjectJson['resolution']['height']);

        // Verify tsconfig has modern options without deprecated downlevelIteration
        $tsconfigJson = json_decode($files['tsconfig.json'], true);
        $this->assertIsArray($tsconfigJson);
        $this->assertArrayNotHasKey('downlevelIteration', $tsconfigJson['compilerOptions']);
        $this->assertEquals('ESNext', $tsconfigJson['compilerOptions']['target']);

        // Verify favicon in index.html
        $this->assertStringContainsString('<link rel="icon"', $files['index.html']);
        $this->assertStringContainsString('href="./assets/icon.svg"', $files['index.html']);

        // Verify TypeScript class
        $this->assertStringContainsString('export class Clock extends HTMLElement', $files['src/Clock.ts']);
        $this->assertStringContainsString('customElements.define(\'tce-clock\', Clock)', $files['src/index.ts']);
    }

    public function test_generate_zip_creates_valid_zip_archive(): void
    {
        $zipPath = $this->service->generateZip([
            'name' => 'tce-test-element',
            'title' => 'Test Element',
            'description' => 'A test element for unit tests',
            'width' => 500,
            'height' => 350,
            'template' => 'canvas',
        ]);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('package.json'));
        $this->assertNotFalse($zip->locateName('tobidot-project.json'));
        $this->assertNotFalse($zip->locateName('src/index.ts'));
        $this->assertNotFalse($zip->locateName('src/TestElement.ts'));
        $this->assertNotFalse($zip->locateName('assets/icon.svg'));

        $zip->close();
        @unlink($zipPath);
    }

    public function test_tce_generator_config_dto_methods_and_immutability(): void
    {
        $dto = TceGeneratorConfig::fromArray([
            'name' => 'clock-widget',
            'title' => 'Clock Widget',
            'version' => '2.0.0',
            'kind' => 'element',
            'width' => 500,
            'height' => 350,
            'template' => 'canvas',
        ]);

        $this->assertEquals('clock-widget', $dto->name);
        $this->assertEquals('ClockWidget', $dto->className);
        $this->assertEquals('clock-widget-template.zip', $dto->getZipFilename());
        $this->assertEquals('CL', $dto->getInitials());
        $this->assertEquals('image/svg+xml', $dto->getIconMimeType());

        // Test ArrayAccess read
        $this->assertTrue(isset($dto['name']));
        $this->assertEquals('clock-widget', $dto['name']);
        $this->assertEquals('ClockWidget', $dto['className']);

        // Test JsonSerializable / Arrayable
        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('clock-widget', $array['name']);

        // Test immutability
        $this->expectException(BadMethodCallException::class);
        $dto['name'] = 'other-name';
    }
}
