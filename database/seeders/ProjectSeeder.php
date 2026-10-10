<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\PublishState;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $pages = Project::factory()->count(10)->create();
        for ($i = 0; $i < 3; $i++) {
            $pages[$i]->publish_state = PublishState::PUBLISHED;
            $pages[$i]->save();
        }
    }
}
