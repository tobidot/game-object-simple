<?php

namespace App\Enums;

use App\Enums\Traits\LookupEnumTrait;

enum ProjectState : int
{
    use LookupEnumTrait;

    public static function table(): string
    {
        return 'lu_project_states';
    }

    case DRAFT = 1;
    case IN_DEVELOPMENT = 2;
    case FINISHED = 3;
}
