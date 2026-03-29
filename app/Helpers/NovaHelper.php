<?php

namespace App\Helpers;

use App\Enums\Traits\LookupEnumTrait;
use Laravel\Nova\Fields\Select;
use LogicException;
use Tobidot\LookupEnum\LookupEnum;

class NovaHelper
{
    /**
     * @param string $label
     * @param string $key
     * @param class-string $enum
     * @return Select
     */
    public static function makeEnum(
        string $label,
        string $key,
        string $enum
    ) : Select {
        if (!is_subclass_of($enum, \BackedEnum::class)) {
            throw new LogicException("$enum is not a valid backed enum type.");
        }

        $options = [];
        foreach ($enum::cases() as $case) {
            $options[$case->value] = $case->name;
        }

        return Select::make($label,$key)
            ->options($options)
            ->displayUsingLabels();
    }
}
