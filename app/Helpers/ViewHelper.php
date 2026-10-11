<?php

namespace App\Helpers;

use App\Models\Attachment;
use App\Services\Models\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Illuminate\Filesystem\join_paths;

class ViewHelper
{
    public static function mediaUrl(mixed $name): string
    {
        if ($name instanceof Attachment) {
            $attachmentService = AppHelper::resolve(AttachmentService::class);

            return $attachmentService->getUrl($name) ?? $name->url ?? '';
        }

        if (! is_string($name) || trim($name) === '') {
            return '';
        }

        if (Str::startsWith($name, ['http://', 'https://', '//'])) {
            return $name;
        }

        if (Str::startsWith($name, ['/storage/', 'storage/'])) {
            return asset(ltrim($name, '/'));
        }

        if (Storage::disk('media-library')->exists($name)) {
            return Storage::disk('media-library')->url($name);
        }

        if (Storage::disk('public')->exists($name)) {
            return asset(Storage::disk('public')->url($name));
        }

        if (str_contains($name, '/')) {
            return asset(ltrim($name, '/'));
        }

        return Storage::disk('media-library')->url($name);
    }

    public static function mediaImageSize(mixed $name): array
    {
        if ($name instanceof Attachment) {
            $filePath = join_paths($name->path, $name->file_name ?? '');
            if (Storage::disk('public')->exists($filePath)) {
                $size = @getimagesize(Storage::disk('public')->path($filePath));
                if (is_array($size)) {
                    return $size;
                }
            }

            return [];
        }

        if (! is_string($name) || trim($name) === '') {
            return [];
        }

        if (Storage::disk('media-library')->exists($name)) {
            $size = @getimagesize(Storage::disk('media-library')->path($name));
            if (is_array($size)) {
                return $size;
            }
        }

        if (Storage::disk('public')->exists($name)) {
            $size = @getimagesize(Storage::disk('public')->path($name));
            if (is_array($size)) {
                return $size;
            }
        }

        $publicPath = public_path(ltrim($name, '/'));
        if (file_exists($publicPath) && is_file($publicPath)) {
            $size = @getimagesize($publicPath);
            if (is_array($size)) {
                return $size;
            }
        }

        return [];
    }

    public static function mediaImageHtml(mixed $name, ?string $alt = null): string
    {
        if (empty($name)) {
            return '';
        }

        $url = ViewHelper::mediaUrl($name);
        if (empty($url)) {
            return '';
        }

        $size = ViewHelper::mediaImageSize($name);
        if ($alt === null) {
            if ($name instanceof Attachment) {
                $alt = Str::title(Str::of($name->file_name ?? 'Attachment')->replace(['_', '-'], ' '));
            } elseif (is_string($name)) {
                $alt = Str::title(Str::of(basename($name))->replace(['_', '-'], ' '));
            } else {
                $alt = 'Thumbnail';
            }
        }

        $sizeAttrs = '';
        if (! empty($size) && isset($size[0], $size[1])) {
            $sizeAttrs = " width=\"{$size[0]}\" height=\"{$size[1]}\"";
        }

        return "<img src=\"{$url}\" alt=\"{$alt}\"{$sizeAttrs}>";
    }

    /**
     * Get the url for a model
     */
    public static function modelUrl(Model $model): string
    {
        $name = Str::camel(class_basename($model));
        $folder = Str::snake(Str::plural(class_basename($model)), '-');

        return route(
            $folder.'.show', [
                $name => $model,
            ]
        );
    }
}
