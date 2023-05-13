<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ViewHelper
{
    public static function mediaUrl(string $name): string
    {
        return Storage::disk('media-library')->url($name);
    }

    public static function mediaImageSize(string $name): array
    {
        return getimagesize(Storage::disk('media-library')->path($name));
    }

    public static function mediaImageHtml(string $name,?string $alt = null) : string {
         $size = ViewHelper::mediaImageSize($name);
         $url = ViewHelper::mediaUrl($name);
         $alt ??= Str::title(Str::of($name)->replace('_', ' '));
return <<<HTML
<img src="$url"
    alt="$alt"
    width="$size[0]"
    height="$size[1]"
>
HTML;
    }

    /**
     * Get the url for a model
     *
     * @param string $model
     * @return string
     */
    public static function modelUrl(Model $model): string
    {
        $name = Str::camel(class_basename($model));
        $folder = Str::camel(Str::plural(class_basename($model)));
        return route(
            $folder . '.show', [
                $name => $model
            ]
        );
    }
}
