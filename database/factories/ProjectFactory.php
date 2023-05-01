<?php

namespace Database\Factories;

use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'title' => $this->faker->sentence($this->faker->numberBetween(1, 9)),
            'description' => $this->faker->randomHtml(2, 4),
            'publish_state_id' => $this->faker->randomElement(PublishState::cases()),
            'state_id' => $this->faker->randomElement(ProjectState::cases()),
        ];
    }

    public function published() : self
    {
        return $this->state([
            'publish_state_id' => PublishState::PUBLISHED->value,
        ]);
    }
}
