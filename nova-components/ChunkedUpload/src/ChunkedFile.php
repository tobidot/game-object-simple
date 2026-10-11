<?php

namespace Tobidot\ChunkedUpload;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Requests\NovaRequest;

class ChunkedFile extends File
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'chunked-file-field';

    /**
     * Client-side chunk size in bytes (default: 5MB).
     */
    protected int $chunkSize = 5 * 1024 * 1024;

    /**
     * Maximum allowed file size in bytes (null for unlimited).
     */
    protected ?int $maxFileSize = null;

    /**
     * Set the chunk size in bytes.
     */
    public function chunkSize(int $bytes): static
    {
        $this->chunkSize = $bytes;

        return $this;
    }

    /**
     * Set the maximum file size in bytes.
     */
    public function maxFileSize(int $bytes): static
    {
        $this->maxFileSize = $bytes;

        return $this;
    }

    /**
     * Retrieve file instance from request (supporting direct files or chunked uploads).
     */
    #[\Override]
    protected function retrieveFileFromRequest(Request $request, string $requestAttribute): ?UploadedFile
    {
        // 1. Check for standard multipart file upload (fallback or test)
        $standardFile = parent::retrieveFileFromRequest($request, $requestAttribute);
        if ($standardFile instanceof UploadedFile && $standardFile->isValid()) {
            return $standardFile;
        }

        // 2. Check for chunked upload session payload
        $payload = $request->input($requestAttribute);
        if (empty($payload)) {
            $payload = data_get($request->all(), $requestAttribute);
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $uploadId = null;
        $originalName = null;
        $mimeType = null;

        if (is_array($payload) && ! empty($payload['upload_id'])) {
            $uploadId = $payload['upload_id'];
            $originalName = $payload['original_name'] ?? $payload['file_name'] ?? null;
            $mimeType = $payload['mime_type'] ?? null;
        } elseif (is_string($payload) && preg_match('/^[a-zA-Z0-9_\-]+$/', $payload)) {
            $uploadId = $payload;
        }

        if ($uploadId) {
            /** @var ChunkManager $chunkManager */
            $chunkManager = app(ChunkManager::class);

            return $chunkManager->createUploadedFile($uploadId, $originalName, $mimeType);
        }

        return null;
    }

    /**
     * Fill the action fields with the resolved file.
     */
    #[\Override]
    public function fillForAction(NovaRequest $request, object $model): void
    {
        $model->{$this->attribute} = $this->retrieveFileFromRequest($request, $this->attribute);
    }

    /**
     * Prepare the field for JSON serialization.
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'chunkSize' => $this->chunkSize,
            'maxFileSize' => $this->maxFileSize,
            'uploadUrl' => route('chunked-upload.chunk', [], false),
            'cancelUrl' => route('chunked-upload.cancel', [], false),
        ]);
    }
}
