<?php

namespace Tests\Feature;

use App\Models\TobidotElement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class TceGeneratorTest extends TestCase
{
    public function test_generator_page_is_accessible(): void
    {
        TobidotElement::factory()->create([
            'name' => 'tce-custom-test-widget',
            'major' => 2,
            'minor' => 1,
            'patch' => 0,
            'kind' => 'element',
            'description' => 'A custom widget from database',
        ]);

        $response = $this->get('/tce-generator');

        $response->assertStatus(200);
        $response->assertSee('TCE Project Generator');
        $response->assertSee('tce-custom-test-widget');
        $response->assertSee('dll-tce-desktop');
        $response->assertSee('Generate & Download TCE Project ZIP', false);
    }

    public function test_generator_download_endpoint_returns_zip(): void
    {
        $response = $this->post('/tce-generator', [
            'name' => 'tce-space-invaders',
            'title' => 'Space Invaders',
            'description' => 'A classic arcade space invaders clone in TCE.',
            'version' => '1.0.0',
            'kind' => 'element',
            'width' => 800,
            'height' => 600,
            'template' => 'canvas',
            'tce_dependencies' => ['dll-tce-desktop:^1.0.0'],
            'dependencies' => ['@game.object/ts-game-toolbox'],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('tce-space-invaders-template.zip', (string) $response->headers->get('content-disposition'));

        /** @var BinaryFileResponse $binaryResponse */
        $binaryResponse = $response->baseResponse;
        $filePath = $binaryResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($filePath));
        $this->assertNotFalse($zip->locateName('package.json'));
        $this->assertNotFalse($zip->locateName('tobidot-project.json'));
        $this->assertNotFalse($zip->locateName('src/SpaceInvaders.ts'));
        $this->assertNotFalse($zip->locateName('src/index.ts'));

        $packageJson = json_decode($zip->getFromName('package.json'), true);
        $this->assertEquals('tce-space-invaders', $packageJson['name']);
        $this->assertArrayHasKey('@game.object/ts-game-toolbox', $packageJson['dependencies']);
        $this->assertArrayHasKey('dll-tce-desktop', $packageJson['dependencies']);

        $tobidotProjectJson = json_decode($zip->getFromName('tobidot-project.json'), true);
        $this->assertArrayHasKey('dependencies', $tobidotProjectJson);
        $depNames = array_column(array_column($tobidotProjectJson['dependencies'], 'identifier'), 'name');
        $this->assertContains('dll-tce-desktop', $depNames);

        $zip->close();
    }

    public function test_generator_download_with_uploaded_icon(): void
    {
        Storage::fake('avatars');
        $icon = UploadedFile::fake()->image('custom-icon.png', 128, 128);

        $response = $this->post('/tce-generator', [
            'name' => 'tce-custom-icon-app',
            'title' => 'Icon App',
            'icon' => $icon,
            'template' => 'standard',
        ]);

        $response->assertStatus(200);

        /** @var BinaryFileResponse $binaryResponse */
        $binaryResponse = $response->baseResponse;
        $filePath = $binaryResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($filePath));
        $this->assertNotFalse($zip->locateName('assets/icon.png'));

        $indexHtml = $zip->getFromName('index.html');
        $this->assertStringContainsString('<link rel="icon"', $indexHtml);
        $this->assertStringContainsString('href="./assets/icon.png"', $indexHtml);

        $zip->close();
    }

    public function test_generator_validates_inputs(): void
    {
        $response = $this->post('/tce-generator', [
            'name' => 'invalid name with spaces!#$',
            'version' => 'not-semver',
            'width' => 10, // too small
        ]);

        $response->assertSessionHasErrors(['name', 'version', 'width']);
    }

    public function test_api_generator_endpoint(): void
    {
        $response = $this->postJson('/api/public/tce-generator', [
            'name' => 'tce-api-element',
            'title' => 'API Element',
            'version' => '1.0.0',
            'width' => 400,
            'height' => 300,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'filename',
            'config' => [
                'name',
                'className',
                'title',
                'version',
            ],
            'archive_base64',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('tce-api-element-template.zip', $response->json('filename'));
    }

    public function test_artisan_command_generates_zip(): void
    {
        $targetZip = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cli-test-element-template.zip';
        if (file_exists($targetZip)) {
            @unlink($targetZip);
        }

        $this->artisan('tce:generate', [
            'name' => 'tce-cli-element',
            '--title' => 'CLI Element',
            '--tce-dependency' => ['dll-tce-desktop'],
            '--output' => $targetZip,
        ])->assertSuccessful();

        $this->assertFileExists($targetZip);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($targetZip));
        $this->assertNotFalse($zip->locateName('package.json'));
        $this->assertNotFalse($zip->locateName('src/CliElement.ts'));

        $tobidotProjectJson = json_decode($zip->getFromName('tobidot-project.json'), true);
        $this->assertArrayHasKey('dependencies', $tobidotProjectJson);
        $depNames = array_column(array_column($tobidotProjectJson['dependencies'], 'identifier'), 'name');
        $this->assertContains('dll-tce-desktop', $depNames);

        $zip->close();

        @unlink($targetZip);
    }
}
