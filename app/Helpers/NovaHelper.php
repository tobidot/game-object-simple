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
        if (!in_array(LookupEnumTrait::class, class_uses($enum,true)) ) {
            throw new LogicException("$enum is not a valid enum type.");
        }
//        return LookupEnum::make();
        /** @var LookupEnumTrait $enum */
        return Select::make($label,$key)
            ->options($enum::options())
            ->displayUsingLabels();
    }
}
