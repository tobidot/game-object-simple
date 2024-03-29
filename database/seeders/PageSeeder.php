<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\PublishState;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {

        $home = Page::query()->where('uri', 'home')->first();
        if ($home === null) {
            Page::factory()->create([
                'title' => 'Home',
                'uri' => 'home',
                'publish_state_id' => PublishState::PUBLISHED,
            ]);
        }

        $pages = Page::factory()->count(10)->create();
        for ($i = 0; $i < 3; ++$i) {
            $pages[$i]->publish_state_id = PublishState::PUBLISHED->value;
            $pages[$i]->save();
        }
    }
}
