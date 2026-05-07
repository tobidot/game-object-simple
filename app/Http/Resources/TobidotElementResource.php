<?php

namespace App\Http\Resources;

use App\Enums\AttachmentType;
use App\Models\TobidotElement;
use App\Helpers\AppHelper;
use App\Services\Models\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        $attachmentService = AppHelper::resolve(AttachmentService::class);

        $codeAttachment = $element->codeAttachment()->first();
        $iconAttachment = $element->iconAttachment()->first();

        if ($codeAttachment && $codeAttachment->type === AttachmentType::ZIP) {

        }

        return [
            'name' => $element->name,
            'kind' => $element->kind,
            'major' => (int) $element->major,
            'minor' => (int) $element->minor,
            'patch' => (int) $element->patch,
            'root' => $codeAttachment ? $attachmentService->getBaseUrl($codeAttachment) : null,
            'icon' => $iconAttachment ? $attachmentService->getUrl($iconAttachment) : null,
            'content' => $codeAttachment ? $attachmentService->getIndexUrl($codeAttachment, $element->name) : null,
            'width' => (int) $element->width,
            'height' => (int) $element->height,
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
