<?php

namespace Database\Factories;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'path' => $this->faker->word(),
            'publish_state' => $this->faker->randomElement(PublishState::cases()),
            'type' => $this->faker->randomElement(AttachmentType::cases()),
            'url' => $this->faker->url(),
        ];
    }
}
