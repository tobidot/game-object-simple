<?php

namespace App\Services\Models;

use App\Helpers\AppHelper;
use App\Models\TobidotElement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class TobidotElementService
{
    /**
     * @param  string  $name
     * @param  UploadedFile  $zip
     * @param  string|null  $version
     * @param  string|null  $kind
     * @param  string|null  $description
     * @param  UploadedFile|null  $icon
     * @return TobidotElement
     * @throws \Exception|\Throwable
     */
    public function upload(
        string $name,
        UploadedFile $zip,
        ?string $version = null,
        ?string $kind = null,
        ?string $description = null,
        ?UploadedFile $icon = null
    ): TobidotElement {
        $last_element = TobidotElement::query()
            ->where('name', $name)
            ->orderByDesc('major')
            ->orderByDesc('minor')
            ->orderByDesc('patch')
            ->first();

        // determine new version
        [$major, $minor, $patch] = $this->determineNextVersion($last_element, $version);

        DB::transaction(function () use (
            $name,
            $zip,
            $major,
            $minor,
            $patch,
            $kind,
            $description,
            $last_element,
            $icon,
            &$element,
        ) {
            $attachmentService = AppHelper::resolve(AttachmentService::class);
            $attachment = $attachmentService
                ->createFromUploadedZipFile(
                    $zip,
                    "tobidot-elements",
                    "tobidot-elements",
                );

            $iconAttachment = null;
            if ($icon) {
                $iconAttachment = $attachmentService->createFromUploadedImage(
                    $icon,
                    "media",
                    ""
                );
            }

            $element = new TobidotElement();
            $element->name = $name;
            $element->major = $major;
            $element->minor = $minor;
            $element->patch = $patch;
            $element->kind = $kind ?? ($last_element?->kind ?? 'element');
            $element->description = $description ?? ($last_element?->description ?? null);
            $element->content = $attachment->url.'/index.zip';
            $element->attachment_id = $attachment->id;

            // Default values from migration if available, or sensible defaults
            $element->standalone = $last_element?->standalone ?? true;
            $element->width = $last_element?->width ?? 200;
            $element->height = $last_element?->height ?? 200;
            $element->extra = $last_element?->extra ?? null;

            if ($iconAttachment) {
                $element->icon = ltrim($iconAttachment->url . '/' . $iconAttachment->file_name, '/');
            } elseif ($last_element?->icon) {
                $element->icon = $last_element->icon;
            }

            $element->save();
        });

        return $element;
    }

    #[ArrayShape(['int', 'int', 'int'])]
    protected function determineNextVersion(
        TobidotElement $last_element = null,
        string $version = null
    ): array {
        if (empty($version) && empty($last_element)) {
            return [0, 0, 1];
        }


        if (empty($version) && $last_element instanceof TobidotElement) {
            return [
                $last_element->major,
                $last_element->minor,
                $last_element->patch + 1
            ];
        }

        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
            throw new \InvalidArgumentException("Invalid Semver Version");
        }
        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];

        if ($last_element instanceof TobidotElement) {
            $last_version = "{$last_element->major}.{$last_element->minor}.{$last_element->patch}";
            if (version_compare($version, $last_version, '<=')) {
                throw new \InvalidArgumentException("Version must be higher than last release '$last_version'");
            }
        }

        return [
            $major,
            $minor,
            $patch
        ];
    }
}
