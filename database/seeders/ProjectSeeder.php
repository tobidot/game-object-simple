<?php

namespace Database\Seeders;

use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Models\CodeRelease;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $showcaseProjects = [
            [
                'title' => 'Space Odyssey 3D',
                'description' => '<p>An immersive 3D space exploration and tactical combat simulator built with modern WebGL and WebAssembly technologies. Navigate vast solar systems, trade with orbital stations, and upgrade your starship systems.</p><p>Features procedurally generated celestial bodies, dynamic Newtonian physics, and real-time lighting shaders.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::FINISHED,
                'releases' => ['1.0.0', '1.1.0', '1.2.0'],
            ],
            [
                'title' => 'Cyberpunk Roguelike',
                'description' => '<p>A fast-paced turn-based tactical roguelike set in a neon-drenched dystopian metropolis. Hack surveillance networks, manipulate enemy combat bots, and survive high-risk extraction missions.</p><p>Includes deep skill trees, cybernetic augmentations, and modular procedural level generation.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::IN_DEVELOPMENT,
                'releases' => ['0.8.0', '0.9.1'],
            ],
            [
                'title' => 'Pixel Physics Platformer',
                'description' => '<p>A precision platformer featuring fluid particle simulation and interactive physics puzzles. Solve environmental challenges using gravity manipulation, momentum conservation, and elemental interactions.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::FINISHED,
                'releases' => ['1.0.0', '1.0.5'],
            ],
            [
                'title' => 'Voxel Engine Sandbox',
                'description' => '<p>A high-performance voxel rendering engine and sandbox building environment. Capable of rendering millions of volumetric blocks with compute-shader level-of-detail and dynamic ambient occlusion.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::IN_DEVELOPMENT,
                'releases' => ['0.5.0'],
            ],
            [
                'title' => 'Multiplayer Card Arena',
                'description' => '<p>A strategic deck-building and card battling game with real-time websocket matchmaking. Build synergistic decks, anticipate opponent moves, and climb the competitive ladder.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::IN_DEVELOPMENT,
                'releases' => ['0.3.0'],
            ],
            [
                'title' => 'Retro Arcade Collection',
                'description' => '<p>A curated compilation of classic arcade game reimaginings, with pixel-perfect CRT shader effects, chip-tune audio synthesizers, and modern accessibility options.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::FINISHED,
                'releases' => ['1.0.0'],
            ],
            [
                'title' => 'AI Dungeon Crawler',
                'description' => '<p>Experimental roguelike featuring LLM-driven procedural quest design and adaptive enemy tactical behaviors. Currently in early prototyping phase.</p>',
                'publish_state' => PublishState::PRIVATE,
                'state' => ProjectState::DRAFT,
                'releases' => [],
            ],
            [
                'title' => 'Shader Experiments Lab',
                'description' => '<p>An interactive gallery of experimental GLSL / WebGPU shaders, featuring raymarching fractals, volumetric clouds, and water caustics simulations.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'state' => ProjectState::DRAFT,
                'releases' => ['0.1.0'],
            ],
        ];

        $createdProjects = collect();

        foreach ($showcaseProjects as $data) {
            $releases = $data['releases'] ?? [];
            unset($data['releases']);

            /** @var Project $project */
            $project = Project::query()->updateOrCreate(
                ['title' => $data['title']],
                $data
            );

            // Create code releases
            foreach ($releases as $version) {
                CodeRelease::query()->firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'version' => $version,
                    ],
                    [
                        'completeness' => fake()->randomFloat(1, 5, 10),
                        'fun' => fake()->randomFloat(1, 6, 10),
                        'complexity' => fake()->randomFloat(1, 4, 9),
                    ]
                );
            }

            // Create comments if none exist
            if ($project->comments()->count() === 0) {
                Comment::factory()->count(fake()->numberBetween(1, 4))->create([
                    'commentable_type' => Project::class,
                    'commentable_id' => $project->id,
                ]);
            }

            // Create views if none exist
            if ($project->views()->count() === 0) {
                View::factory()->count(fake()->numberBetween(5, 20))->create([
                    'viewable_type' => Project::class,
                    'viewable_id' => $project->id,
                ]);
            }

            $createdProjects->push($project);
        }

        // Add additional factory projects if needed
        if (Project::query()->count() < 12) {
            $factoryProjects = Project::factory()->count(4)->create();
            foreach ($factoryProjects as $project) {
                CodeRelease::factory()->count(fake()->numberBetween(1, 2))->create([
                    'project_id' => $project->id,
                ]);
                Comment::factory()->count(fake()->numberBetween(0, 3))->create([
                    'commentable_type' => Project::class,
                    'commentable_id' => $project->id,
                ]);
                $createdProjects->push($project);
            }
        }

        // Cross-link related projects
        $allProjects = Project::all();
        foreach ($allProjects as $project) {
            $otherProjectIds = $allProjects->where('id', '!==', $project->id)->pluck('id');
            if ($otherProjectIds->isNotEmpty()) {
                $randomRelatedIds = $otherProjectIds->random(min(fake()->numberBetween(1, 3), $otherProjectIds->count()));
                $project->projects()->syncWithoutDetaching($randomRelatedIds);
            }

            // Link to existing pages if available
            $pageIds = Page::query()->pluck('id');
            if ($pageIds->isNotEmpty()) {
                $randomPageIds = $pageIds->random(min(fake()->numberBetween(1, 2), $pageIds->count()));
                $project->pages()->syncWithoutDetaching($randomPageIds);
            }
        }
    }
}
