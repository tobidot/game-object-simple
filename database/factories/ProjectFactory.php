<?php

namespace Database\Factories;

use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->randomHtml(2, 4),
            'publish_state' => $this->faker->randomElement(PublishState::cases()),
            'state' => $this->faker->randomElement(ProjectState::cases()),
        ];
    }

    public function published(): self
    {
        return $this->state([
            'publish_state' => PublishState::PUBLISHED,
        ]);
    }

    public function private(): self
    {
        return $this->state([
            'publish_state' => PublishState::PRIVATE,
        ]);
    }

    public function finished(): self
    {
        return $this->state([
            'state' => ProjectState::FINISHED,
        ]);
    }

    public function inDevelopment(): self
    {
        return $this->state([
            'state' => ProjectState::IN_DEVELOPMENT,
        ]);
    }

    public function draft(): self
    {
        return $this->state([
            'state' => ProjectState::DRAFT,
        ]);
    }
}
