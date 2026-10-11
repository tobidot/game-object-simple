<?php

namespace Database\Seeders;

use App\Enums\PublishState;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $showcasePages = [
            [
                'uri' => 'home',
                'title' => 'Home',
                'content' => '<h1>Welcome to Game Object</h1><p>Game Object is an open sandbox and experimental game development platform dedicated to modern web-based rendering, physics simulation, and interactive experiences.</p><p>Explore our active project prototypes, try out web releases directly in your browser, or dive into our technical documentation and engine architecture articles.</p>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'about',
                'title' => 'About Game Object',
                'content' => '<h2>Our Mission</h2><p>Game Object focuses on exploring the limits of browser-based game engines using WebGL, WebGPU, WebAssembly, and high-performance JavaScript runtimes.</p><p>We build open prototypes, experimental sandbox tools, and modular desktop shell elements that empower developers to create rich interactive applications.</p>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'getting-started',
                'title' => 'Getting Started with Game Development',
                'content' => '<h2>Getting Started</h2><p>Discover how to integrate and build with our game elements and interactive components.</p><h3>Key Steps:</h3><ul><li>Browse through our published projects and inspect live web releases.</li><li>Review the modular element definitions and script integrations.</li><li>Experiment with custom physics parameters and shader uniforms.</li></ul>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'engine-architecture',
                'title' => 'Game Engine Architecture',
                'content' => '<h2>Engine Architecture & Core Systems</h2><p>Our custom engine utilizes a cache-friendly Entity Component System (ECS) paired with decoupled worker threads for heavy mathematical computations and physics simulations.</p><h3>Core Subsystems:</h3><ul><li><strong>Render Graph:</strong> Dynamic pass management and deferred shading pipeline.</li><li><strong>Physics Worker:</strong> Deterministic step calculation running on off-main-thread workers.</li><li><strong>Asset Streamer:</strong> Progressive texture decoding and geometry streaming.</li></ul>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'tutorials/shaders',
                'title' => 'Tutorial: WebGL Shaders & Lighting',
                'content' => '<h2>Introduction to Shader Programming</h2><p>Learn how to craft performant vertex and fragment shaders that bring depth, reflections, and atmospheric volumetric effects to your scenes.</p><p>We walk through basic phong illumination, normal mapping, and screen-space ambient occlusion (SSAO) implementations.</p>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'community-guidelines',
                'title' => 'Community Guidelines & Collaboration',
                'content' => '<h2>Community Standards</h2><p>We are committed to providing a friendly, safe, and welcoming environment for all creators, developers, and game designers.</p><p>Please share constructive feedback in project comments and adhere to collaborative open-source best practices.</p>',
                'publish_state' => PublishState::PUBLISHED,
            ],
            [
                'uri' => 'draft-asset-pipeline',
                'title' => 'Internal Draft: Next-Gen Asset Streaming',
                'content' => '<p>Confidential working draft detailing plans for chunked mesh streaming over HTTP/3 and WebTransport.</p>',
                'publish_state' => PublishState::PRIVATE,
            ],
        ];

        $createdPages = collect();

        foreach ($showcasePages as $data) {
            /** @var Page $page */
            $page = Page::query()->updateOrCreate(
                ['uri' => $data['uri']],
                $data
            );

            // Create comments if none exist
            if ($page->comments()->count() === 0) {
                Comment::factory()->count(fake()->numberBetween(1, 3))->create([
                    'commentable_type' => Page::class,
                    'commentable_id' => $page->id,
                ]);
            }

            // Create views if none exist
            if ($page->views()->count() === 0) {
                View::factory()->count(fake()->numberBetween(5, 20))->create([
                    'viewable_type' => Page::class,
                    'viewable_id' => $page->id,
                ]);
            }

            $createdPages->push($page);
        }

        // Add additional factory pages if needed
        if (Page::query()->count() < 12) {
            $factoryPages = Page::factory()->count(5)->create();
            foreach ($factoryPages as $page) {
                Comment::factory()->count(fake()->numberBetween(0, 2))->create([
                    'commentable_type' => Page::class,
                    'commentable_id' => $page->id,
                ]);
                $createdPages->push($page);
            }
        }

        // Cross-link related pages
        $allPages = Page::all();
        foreach ($allPages as $page) {
            $otherPageIds = $allPages->where('id', '!==', $page->id)->pluck('id');
            if ($otherPageIds->isNotEmpty()) {
                $randomRelatedIds = $otherPageIds->random(min(fake()->numberBetween(1, 3), $otherPageIds->count()));
                $page->pages()->syncWithoutDetaching($randomRelatedIds);
            }

            // Link to existing projects if available
            $projectIds = Project::query()->pluck('id');
            if ($projectIds->isNotEmpty()) {
                $randomProjectIds = $projectIds->random(min(fake()->numberBetween(1, 3), $projectIds->count()));
                $page->projects()->syncWithoutDetaching($randomProjectIds);
            }
        }
    }
}
