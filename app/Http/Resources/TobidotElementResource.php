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

        // determine root folder
        $temp = array_filter(explode('/', $element->content), fn($item) => !empty($item));
        if (count($temp) > 0 && (Str::startsWith($element->content, 'http') || $temp[array_key_first($temp)] === 'storage')) {
            // If it's a full URL or starts with storage, we need to be careful
            // But based on Service code, it should be /tobidot-elements/...
        }

        array_splice($temp, -1, 1);
        $root = implode('/', $temp);

        // If 'storage' is the first segment, remove it because we'll use the public disk
        if (count($temp) > 0 && $temp[array_key_first($temp)] === 'storage') {
            array_shift($temp);
            $root = implode('/', $temp);
        }

        $content = null;
        if ($element->attachment && $element->attachment->file_name) {
             $content = $root . '/' . $element->attachment->file_name;
        } elseif (Str::endsWith($element->content, '.zip')) {
            // if this is a zip look for the primary source
            $name = Str::ucfirst(\Str::camel( $element->name ) );
            $candidate_names = [
                "$name.es.js",
                "$name.js",
                "index.es.js",
                "index.js",
                $element->name . ".es.js",
                $element->name . ".js",
            ];
            foreach($candidate_names as $candidate_name) {
                $candidate = "$root/$candidate_name";

                \Log::info("test", [
                    $candidate
                ]);
                if (Storage::disk('public')->exists($candidate)) {
                    $content = $candidate;
                    break;
                }
            }
        }

        \Log::info("content", [
            $content,
            $element->content,
        ]);

        if ($element->icon) {
            $raw_icon_path = ltrim($element->icon, '/');
            if (Str::startsWith($raw_icon_path, 'media/')) {
                $icon = asset(Storage::disk('public')->url($raw_icon_path));
            } else {
                $icon = asset(Storage::disk('media-library')->url($raw_icon_path));
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
            'root' => asset(Storage::disk('public')->url($root)),
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
