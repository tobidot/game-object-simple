<?php

namespace Database\Factories;

use App\Enums\PublishState;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uri' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence($this->faker->numberBetween(2, 6)),
            'content' => $this->faker->randomHtml(4, 4),
            'publish_state' => $this->faker->randomElement(PublishState::cases()),
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
}
