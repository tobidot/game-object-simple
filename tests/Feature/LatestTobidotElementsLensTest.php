<?php

namespace Tests\Feature;

use App\Models\TobidotElement;
use App\Nova\Lenses\LatestTobidotElements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\LensRequest;
use Tests\TestCase;

class LatestTobidotElementsLensTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_returns_only_latest_versions()
    {
        // Project A
        TobidotElement::factory()->create(['name' => 'Project A', 'major' => 1, 'minor' => 0, 'patch' => 0]);
        TobidotElement::factory()->create(['name' => 'Project A', 'major' => 1, 'minor' => 1, 'patch' => 0]);
        $latestA = TobidotElement::factory()->create(['name' => 'Project A', 'major' => 1, 'minor' => 1, 'patch' => 1]);

        // Project B
        TobidotElement::factory()->create(['name' => 'Project B', 'major' => 0, 'minor' => 9, 'patch' => 0]);
        $latestB = TobidotElement::factory()->create(['name' => 'Project B', 'major' => 1, 'minor' => 0, 'patch' => 0]);

        // Project C (only one version)
        $latestC = TobidotElement::factory()->create(['name' => 'Project C', 'major' => 2, 'minor' => 0, 'patch' => 0]);

        $request = LensRequest::create('/nova-api/tobidot-elements/lens/latest-tobidot-elements');

        $query = LatestTobidotElements::query($request, TobidotElement::query());
        $results = $query->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $latestA->id));
        $this->assertTrue($results->contains('id', $latestB->id));
        $this->assertTrue($results->contains('id', $latestC->id));

        $this->assertFalse($results->contains('name', 'Project A') && $results->where('name', 'Project A')->count() > 1);
    }
}
