<?php

namespace Database\Factories;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Models\Attachment;
use App\Models\TobidotElement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TobidotElement>
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
            'width' => 200,
            'height' => 200,
        ];
    }

    /**
     * Attach a code attachment, which the public index requires for an element to be listed.
     */
    public function withCode(): static
    {
        return $this->afterCreating(function (TobidotElement $element) {
            $attachment = Attachment::factory()->create([
                'path' => "tobidot-elements/$element->name",
                'file_name' => 'index.js',
                'type' => AttachmentType::BINARY,
                'publish_state' => PublishState::PUBLISHED,
            ]);
            $element->attachments()->attach($attachment->id, ['relation' => 'code']);
        });
    }
}
