<?php

namespace App\Enums;

enum AttachmentType : string
{
    case ZIP = 'zip';
    case BINARY = 'binary';
    case IMAGE = 'image';
    case VIDEO = 'video';
}
