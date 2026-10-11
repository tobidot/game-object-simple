<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            TobidotElementSeeder::class,
            ProjectSeeder::class,
            PageSeeder::class,
        ]);
    }
}
