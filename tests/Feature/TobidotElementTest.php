<?php

namespace Tests\Feature;

use App\Models\TobidotElement;
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
            'content' => 'tobidot-elements/uuid/index.js',
            'icon' => 'test-icon.png',
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
                    ]
                ]
            ]
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
}
