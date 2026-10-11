<?php

namespace App\Http\Resources;

use App\Helpers\AppHelper;
use App\Models\TobidotElement;
use App\Services\Models\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TobidotElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var TobidotElement $element */
        $element = $this->resource;

        $attachmentService = AppHelper::resolve(AttachmentService::class);

        $codeAttachment = $element->codeAttachment()->first();
        $iconAttachment = $element->iconAttachment()->first();

        $dependencies = $this->dependencies($element);

        return [
            'name' => $element->name,
            'kind' => $element->kind,
            'major' => $element->major,
            'minor' => $element->minor,
            'patch' => $element->patch,
            'root' => $codeAttachment ? $attachmentService->getBaseUrl($codeAttachment) : null,
            'icon' => $iconAttachment ? $attachmentService->getUrl($iconAttachment) : null,
            'content' => $codeAttachment ? $attachmentService->getIndexUrl($codeAttachment, $element->name) : null,
            'width' => (int) $element->width,
            'height' => (int) $element->height,
            'extra' => $element->extra ?? [],
            'created_at' => $element->created_at,
            'dependencies' => $dependencies,
        ];
    }

    protected function dependencies(TobidotElement $element): array
    {
        return $element->dependencies->map(function (TobidotElement $dependency): array {
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
            ], fn ($val) => $val !== null);

            if (! empty($version)) {
                $requirement['version'] = $version;
            }

            return $requirement;
        })->all();
    }
}
