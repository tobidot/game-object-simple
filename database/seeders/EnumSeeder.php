<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\AttachmentType;
use App\Enums\LogEventTypes;
use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Enums\Traits\LookupEnumTrait;
use App\Exceptions\DummyException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tobidot\LookupEnum\LookupEnum;

class EnumSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->upsertEnum(AttachmentType::class);
        $this->upsertEnum(PublishState::class);
        $this->upsertEnum(ProjectState::class);
        $this->upsertEnum(LogEventTypes::class);
    }


    public function upsertEnum(
        string $class
    ) {
        if (!array_search(LookupEnumTrait::class, class_uses($class, true))) {
            throw new DummyException("Not a lookup enum");
        }
        /** @var LookupEnumTrait $class */
        DB::table($class::table())
            ->upsert(
                array_map(
                    fn(mixed $state) => [
                        'id' => $state->value,
                        'name' => $state->name,
                        'label' => Str::headline(Str::lower($state->name)),
                    ],
                    $class::cases()
                ),
                'id'
            );
    }
}
