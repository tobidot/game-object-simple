<?php

namespace App\Enums;

use App\Enums\Traits\LookupEnumTrait;

enum LogEventTypes : int
{
    use LookupEnumTrait;

    public static function table(): string
    {
        return 'lu_log_event_types';
    }

    case INFO = 1;
    case ERROR = 2;
}
