<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\PublishState;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'object.name@live.de')->first();
        if ($admin === null) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'object.name@live.de',
                'password' => bcrypt('password'),
            ]);
        }
    }
}
