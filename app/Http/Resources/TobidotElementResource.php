<?php

namespace App\Http\Resources;

use App\Enums\LogEventTypes;
use App\Models\TobidotElement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TobidotElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var TobidotElement $element */
        $element = $this;

        $content = null;
        $root = null;
        $codeAttachment = $element->codeAttachment()->first();
        if ($codeAttachment && $codeAttachment->file_name) {
             $temp = array_filter(explode('/', $codeAttachment->path), fn($item) => !empty($item));
             array_splice($temp, -1, 1);
             $root = implode('/', $temp);

             // If 'storage' is the first segment, remove it because we'll use the public disk
             if (count($temp) > 0 && $temp[array_key_first($temp)] === 'storage') {
                 array_shift($temp);
                 $root = implode('/', $temp);
             }

             $content = $root . '/' . $codeAttachment->file_name;
        }

        if ($iconAttachment = $element->iconAttachment()->first()) {
            $path = $iconAttachment->path === '/' ? '' : $iconAttachment->path;
            if ($iconAttachment->file_name) {
                $path = rtrim($path, '/') . '/' . $iconAttachment->file_name;
            }
            $raw_path = ltrim($path, '/');

            if (Str::startsWith($raw_path, 'media/')) {
                $icon = asset(Storage::disk('public')->url($raw_path));
            } else {
                $icon = asset(Storage::disk('media-library')->url($raw_path));
            }
        } else {
            $icon = null;
        }

        return [
            'name' => $element->name,
            'kind' => $element->kind,
            'major' => (int)$element->major,
            'minor' => (int)$element->minor,
            'patch' => (int)$element->patch,
            'root' => $root === null ? null : asset(Storage::disk('public')->url($root)),
            'icon' => $icon,
            'content' => $content === null ? null : asset(Storage::disk('public')->url($content)),
            'width' => (int)$element->width,
            'height' => (int)$element->height,
            'extra' => $element->extra ?? [],
            'created_at' => $element->created_at,
            'dependencies' => $element->dependencies->map(function (TobidotElement $dependency) {
                $requirement = [
                    'identifier' => [
                        'namespace' => 'tobidot', // Default namespace
                        'name' => $dependency->name,
                    ],
                ];

                $version = array_filter([
                    'major' => $dependency->pivot->required_major,
                    'minor' => $dependency->pivot->required_minor,
                    'patch' => $dependency->pivot->required_patch,
                ], fn($val) => $val !== null);

                if (!empty($version)) {
                    $requirement['version'] = $version;
                }

                return $requirement;
            }),
        ];
    }
}
