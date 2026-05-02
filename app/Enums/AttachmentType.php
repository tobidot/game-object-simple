<?php

namespace App\Enums;

enum AttachmentType : string
{
    case ZIP = 'zip';
    case BINARY = 'binary';
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
}
