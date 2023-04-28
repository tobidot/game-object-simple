<?php

namespace App\Enums;

use App\Enums\Traits\LookupEnumTrait;

enum AttachmentType : int
{
    use LookupEnumTrait;

    public static function table(): string
    {
        return 'lu_attachment_types';
    }

    case ZIP = 1;
    case BINARY = 2;
    case IMAGE = 3;
    case VIDEO = 4;
}
