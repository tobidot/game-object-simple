<?php

namespace Database\Factories;

use App\Models\TobidotElement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TobidotElement>
 */
class TobidotElementFactory extends Factory
{
    protected $model = TobidotElement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'kind' => $this->faker->randomElement(['element', 'library']),
            'major' => $this->faker->numberBetween(1, 10),
            'minor' => $this->faker->numberBetween(0, 50),
            'patch' => $this->faker->numberBetween(0, 100),
            'content' => 'tobidot-elements/uuid/index.js',
            'width' => 200,
            'height' => 200,
        ];
    }
}
