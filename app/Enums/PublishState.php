<?php

namespace App\Enums;

enum PublishState : string
{
    case PRIVATE = 'private';
    case PUBLISHED = 'published';
}
