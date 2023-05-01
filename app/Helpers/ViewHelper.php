<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ViewHelper
{
    public static function mediaUrl(string $name): string
    {
        return Storage::disk('media-library')->url($name);
    }
}
