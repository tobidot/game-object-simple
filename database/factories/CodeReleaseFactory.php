<?php

namespace Database\Factories;

use App\Models\CodeRelease;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodeRelease>
 */
class CodeReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => $this->faker->numerify('#.#.#'),
            'completeness' => $this->faker->randomFloat(1, 1, 10),
            'fun' => $this->faker->randomFloat(1, 1, 10),
            'complexity' => $this->faker->randomFloat(1, 1, 10),
            'project_id' => Project::factory(),
        ];
    }
}
