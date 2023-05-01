<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\PublishState;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $pages = Project::factory()->count(10)->create();
        for ($i = 0; $i < 3; ++$i) {
            $pages[$i]->publish_state_id = PublishState::PUBLISHED->value;
            $pages[$i]->save();
        }
    }
}
