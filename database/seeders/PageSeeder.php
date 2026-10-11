<?php

namespace Database\Seeders;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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
                'has_thumbnail' => true,
            ],
            [
                'uri' => 'about',
                'title' => 'About Game Object',
                'content' => '<h2>Our Mission</h2><p>Game Object focuses on exploring the limits of browser-based game engines using WebGL, WebGPU, WebAssembly, and high-performance JavaScript runtimes.</p><p>We build open prototypes, experimental sandbox tools, and modular desktop shell elements that empower developers to create rich interactive applications.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'has_thumbnail' => true,
            ],
            [
                'uri' => 'getting-started',
                'title' => 'Getting Started with Game Development',
                'content' => '<h2>Getting Started</h2><p>Discover how to integrate and build with our game elements and interactive components.</p><h3>Key Steps:</h3><ul><li>Browse through our published projects and inspect live web releases.</li><li>Review the modular element definitions and script integrations.</li><li>Experiment with custom physics parameters and shader uniforms.</li></ul>',
                'publish_state' => PublishState::PUBLISHED,
                'has_thumbnail' => false,
            ],
            [
                'uri' => 'engine-architecture',
                'title' => 'Game Engine Architecture',
                'content' => '<h2>Engine Architecture & Core Systems</h2><p>Our custom engine utilizes a cache-friendly Entity Component System (ECS) paired with decoupled worker threads for heavy mathematical computations and physics simulations.</p><h3>Core Subsystems:</h3><ul><li><strong>Render Graph:</strong> Dynamic pass management and deferred shading pipeline.</li><li><strong>Physics Worker:</strong> Deterministic step calculation running on off-main-thread workers.</li><li><strong>Asset Streamer:</strong> Progressive texture decoding and geometry streaming.</li></ul>',
                'publish_state' => PublishState::PUBLISHED,
                'has_thumbnail' => true,
            ],
            [
                'uri' => 'tutorials/shaders',
                'title' => 'Tutorial: WebGL Shaders & Lighting',
                'content' => '<h2>Introduction to Shader Programming</h2><p>Learn how to craft performant vertex and fragment shaders that bring depth, reflections, and atmospheric volumetric effects to your scenes.</p><p>We walk through basic phong illumination, normal mapping, and screen-space ambient occlusion (SSAO) implementations.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'has_thumbnail' => true,
            ],
            [
                'uri' => 'community-guidelines',
                'title' => 'Community Guidelines & Collaboration',
                'content' => '<h2>Community Standards</h2><p>We are committed to providing a friendly, safe, and welcoming environment for all creators, developers, and game designers.</p><p>Please share constructive feedback in project comments and adhere to collaborative open-source best practices.</p>',
                'publish_state' => PublishState::PUBLISHED,
                'has_thumbnail' => false,
            ],
            [
                'uri' => 'draft-asset-pipeline',
                'title' => 'Internal Draft: Next-Gen Asset Streaming',
                'content' => '<p>Confidential working draft detailing plans for chunked mesh streaming over HTTP/3 and WebTransport.</p>',
                'publish_state' => PublishState::PRIVATE,
                'has_thumbnail' => false,
            ],
        ];

        $createdPages = collect();

        foreach ($showcasePages as $data) {
            $hasThumbnail = $data['has_thumbnail'] ?? false;
            unset($data['has_thumbnail']);

            /** @var Page $page */
            $page = Page::query()->updateOrCreate(
                ['uri' => $data['uri']],
                $data
            );

            // Create and attach thumbnail attachment if requested
            if ($hasThumbnail && $page->attachments()->wherePivot('relation', 'thumbnail')->count() === 0) {
                $filename = 'page-'.str_replace('/', '-', $page->uri).'-thumb.png';
                $attachment = $this->createSampleImageAttachment(
                    $filename,
                    $page->title,
                    38,
                    48,
                    106
                );
                $page->attachments()->syncWithoutDetaching([
                    $attachment->id => ['relation' => 'thumbnail'],
                ]);
            }

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

    private function createSampleImageAttachment(
        string $filename,
        string $title,
        int $bgR = 38,
        int $bgG = 48,
        int $bgB = 106
    ): Attachment {
        $disk = Storage::disk('public');
        $folder = 'media/samples';
        $disk->makeDirectory($folder);
        $filePath = $folder.'/'.$filename;

        // Generate retro 320x200 pixel art thumbnail
        $im = imagecreatetruecolor(320, 200);
        $bg = imagecolorallocate($im, $bgR, $bgG, $bgB);
        $turquoise = imagecolorallocate($im, 108, 218, 217);
        $orange = imagecolorallocate($im, 212, 140, 23);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 17, 17, 17);

        imagefill($im, 0, 0, $bg);

        // Dithered dot pattern
        for ($x = 0; $x < 320; $x += 6) {
            for ($y = 0; $y < 200; $y += 6) {
                if (($x + $y) % 12 === 0) {
                    imagesetpixel($im, $x, $y, $turquoise);
                }
            }
        }

        // Window border
        imagerectangle($im, 4, 4, 315, 195, $turquoise);
        imagerectangle($im, 6, 6, 313, 193, $orange);

        // Title box
        imagefilledrectangle($im, 12, 75, 308, 125, $black);
        imagerectangle($im, 12, 75, 308, 125, $turquoise);
        imagestring($im, 5, 24, 92, substr($title, 0, 28), $white);

        ob_start();
        imagepng($im);
        $content = ob_get_clean();
        imagedestroy($im);

        $disk->put($filePath, $content);
        $disk->setVisibility($filePath, 'public');

        $hash = hash('sha256', $content);

        return Attachment::query()->updateOrCreate(
            ['hash' => $hash],
            [
                'path' => $folder,
                'file_name' => $filename,
                'url' => '/storage/'.$filePath,
                'content_type' => 'image/png',
                'publish_state' => PublishState::PUBLISHED,
                'type' => AttachmentType::IMAGE,
            ]
        );
    }
}
