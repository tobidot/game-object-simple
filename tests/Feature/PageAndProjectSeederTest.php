<?php

namespace Tests\Feature;

use App\Enums\PublishState;
use App\Models\Page;
use App\Models\Project;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DummySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAndProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_seeder_seeds_pages_and_home()
    {
        $this->seed(PageSeeder::class);

        $home = Page::query()->withoutGlobalScopes()->where('uri', 'home')->first();
        $this->assertNotNull($home);
        $this->assertEquals(PublishState::PUBLISHED, $home->publish_state);

        $this->assertGreaterThanOrEqual(7, Page::query()->withoutGlobalScopes()->count());
    }

    public function test_project_seeder_seeds_projects_and_code_releases()
    {
        $this->seed(ProjectSeeder::class);

        $projects = Project::query()->withoutGlobalScopes()->get();
        $this->assertGreaterThanOrEqual(8, $projects->count());

        $spaceOdyssey = $projects->firstWhere('title', 'Space Odyssey 3D');
        $this->assertNotNull($spaceOdyssey);
        $this->assertGreaterThan(0, $spaceOdyssey->codeReleases()->count());
    }

    public function test_dummy_seeder_runs_successfully()
    {
        $this->seed(DummySeeder::class);

        $this->assertGreaterThanOrEqual(8, Project::query()->withoutGlobalScopes()->count());
        $this->assertGreaterThanOrEqual(7, Page::query()->withoutGlobalScopes()->count());
    }

    public function test_database_seeder_runs_all_seeders_with_relations()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(1, Page::query()->withoutGlobalScopes()->where('uri', 'home')->count());
        $this->assertGreaterThanOrEqual(8, Project::query()->withoutGlobalScopes()->count());
        $this->assertGreaterThanOrEqual(7, Page::query()->withoutGlobalScopes()->count());

        // Check public pages index endpoint
        $response = $this->get(route('pages'));
        $response->assertStatus(200);

        // Check public projects index endpoint
        $response = $this->get(route('projects'));
        $response->assertStatus(200);

        // Check home route
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Check page detail view
        $publishedPage = Page::query()->where('uri', '!=', 'home')->first();
        if ($publishedPage) {
            $pageResponse = $this->get(route('pages.show', ['page' => $publishedPage->uri]));
            $pageResponse->assertStatus(200);
        }

        // Check project detail view
        $publishedProject = Project::query()->first();
        if ($publishedProject) {
            $projectResponse = $this->get(route('projects.show', ['project' => $publishedProject->id]));
            $projectResponse->assertStatus(200);
        }
    }
}
