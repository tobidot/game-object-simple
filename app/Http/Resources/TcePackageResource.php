<?php

namespace App\Http\Resources;

use App\Helpers\AppHelper;
use App\Models\TcePackage;
use App\Services\Models\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TcePackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var TcePackage $package */
        $package = $this->resource;

        $attachmentService = AppHelper::resolve(AttachmentService::class);

        $codeAttachment = $package->codeAttachment()->first();
        $iconAttachment = $package->iconAttachment()->first();

        return [
            'namespace' => $package->namespace,
            'name' => $package->name,
            'kind' => $package->kind,
            'description' => $package->description,
            'major' => $package->major,
            'minor' => $package->minor,
            'patch' => $package->patch,
            'root' => $codeAttachment ? $attachmentService->getBaseUrl($codeAttachment) : null,
            'content' => $codeAttachment ? $attachmentService->getIndexUrl($codeAttachment, $package->name) : null,
            'icon' => $iconAttachment ? $attachmentService->getUrl($iconAttachment) : null,
            'width' => (int) $package->width,
            'height' => (int) $package->height,
            'extra' => $package->extra ?? [],
            'created_at' => $package->created_at,
            'dependencies' => $this->dependencies($package),
        ];
    }

    protected function dependencies(TcePackage $package): array
    {
        return $package->dependencies->map(function (TcePackage $dependency): array {
            $requirement = [
                'identifier' => [
                    'namespace' => $dependency->namespace,
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
