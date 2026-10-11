<?php

namespace Tests\Feature;

use App\Models\TobidotElement;
use Database\Seeders\TobidotElementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TobidotElementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_endpoint_matches_schema()
    {
        TobidotElement::factory()->create([
            'name' => 'test-element',
            'kind' => 'element',
            'major' => 1,
            'minor' => 2,
            'patch' => 3,
            'width' => 100,
            'height' => 100,
            'extra' => ['foo' => 'bar'],
        ]);

        $response = $this->getJson('/api/public/tobidot-elements');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'version',
            'packages' => [
                'test-element' => [
                    '*' => [
                        'name',
                        'kind',
                        'major',
                        'minor',
                        'patch',
                        'root',
                        'icon',
                        'content',
                        'width',
                        'height',
                        'extra',
                        'dependencies' => [
                            '*' => [
                                'identifier' => [
                                    'namespace',
                                    'name',
                                ],
                                'version' => [
                                    'major',
                                    'minor',
                                    'patch',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $data = $response->json();
        $this->assertEquals(1, $data['version']);
        $package = $data['packages']['test-element'][0];

        $this->assertEquals('test-element', $package['name']);
        // ... (existing assertions)
    }

    public function test_index_endpoint_includes_dependencies()
    {
        $dep = TobidotElement::factory()->create([
            'name' => 'dependency-pkg',
            'major' => 2,
            'minor' => 0,
            'patch' => 0,
        ]);

        $element = TobidotElement::factory()->create([
            'name' => 'main-pkg',
        ]);

        $element->dependencies()->attach($dep, [
            'required_major' => 2,
            'required_minor' => 1,
        ]);

        $response = $this->getJson('/api/public/tobidot-elements');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('main-pkg', $data['packages']);
        $mainPkg = collect($data['packages']['main-pkg'])->firstWhere('name', 'main-pkg');

        $this->assertArrayHasKey('dependencies', $mainPkg);
        $this->assertCount(1, $mainPkg['dependencies']);

        $dependency = $mainPkg['dependencies'][0];
        $this->assertEquals('tobidot', $dependency['identifier']['namespace']);
        $this->assertEquals('dependency-pkg', $dependency['identifier']['name']);
        $this->assertEquals(2, $dependency['version']['major']);
        $this->assertEquals(1, $dependency['version']['minor']);
        $this->assertArrayNotHasKey('patch', $dependency['version']);
    }

    public function test_packages_are_serialized_as_indexed_arrays()
    {
        TobidotElement::factory()->create(['name' => 'first-pkg']);
        TobidotElement::factory()->create(['name' => 'second-pkg']);

        $response = $this->getJson('/api/public/tobidot-elements');

        $response->assertStatus(200);
        $content = $response->getContent();
        $decoded = json_decode($content, true);

        $this->assertTrue(array_is_list($decoded['packages']['first-pkg']));
        $this->assertTrue(array_is_list($decoded['packages']['second-pkg']));
    }

    public function test_empty_packages_serialized_as_json_object()
    {
        TobidotElement::query()->delete();

        $response = $this->getJson('/api/public/tobidot-elements');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('"packages":{}', $content);
    }

    public function test_tobidot_element_seeder_seeds_required_packages()
    {
        $this->seed(TobidotElementSeeder::class);

        $response = $this->getJson('/api/public/tobidot-elements');
        $response->assertStatus(200);

        $packages = $response->json('packages');
        $this->assertArrayHasKey('dll-tce-package-registry', $packages);
        $this->assertArrayHasKey('dll-tce-desktop', $packages);
        $this->assertArrayHasKey('tce-desktop', $packages);
        $this->assertArrayHasKey('tce-bootloader', $packages);
        $this->assertArrayHasKey('tce-clock', $packages);
    }
}
