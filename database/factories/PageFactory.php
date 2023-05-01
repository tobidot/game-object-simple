<?php

namespace Database\Factories;

use App\Enums\PublishState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'uri' => implode('/', $this->faker->words($this->faker->numberBetween(1, 3))),
            'title' => $this->faker->sentence($this->faker->numberBetween(1, 9)),
            'content' => $this->faker->randomHtml(4, 4),
            'publish_state_id' => $this->faker->randomElement(PublishState::cases()),
        ];
    }

    public function published() : self
    {
        return $this->state([
            'publish_state_id' => PublishState::PUBLISHED->value,
        ]);
    }
}
