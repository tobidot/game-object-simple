<?php

namespace App\Enums;

use App\Enums\Traits\LookupEnumTrait;

enum PublishState : int
{
    use LookupEnumTrait;

    public static function table(): string
    {
        return 'lu_publish_states';
    }

    case PRIVATE = 1;
    case PUBLISHED = 2;
}
