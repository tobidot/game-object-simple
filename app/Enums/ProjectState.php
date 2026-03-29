<?php

namespace App\Enums;

enum ProjectState : string
{
    case DRAFT = 'draft';
    case IN_DEVELOPMENT = 'in_development';
    case FINISHED = 'finished';
}
