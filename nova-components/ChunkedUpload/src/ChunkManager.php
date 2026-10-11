<?php

namespace Tobidot\ChunkedUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ChunkManager
{
    /**
     * The base directory where chunk files are stored.
     */
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? storage_path('app/chunks');
    }

    /**
     * Get the sanitized upload directory path for a given upload ID.
     */
    public function getUploadDir(string $uploadId): string
    {
        $safeUploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $uploadId);
        if (empty($safeUploadId)) {
            $safeUploadId = Str::random(40);
        }

        $dir = $this->basePath.DIRECTORY_SEPARATOR.$safeUploadId;
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        return $dir;
    }

    /**
     * Save an individual chunk file.
     */
    public function saveChunk(string $uploadId, int $chunkIndex, UploadedFile $chunkFile): string
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $chunkPath = $uploadDir.DIRECTORY_SEPARATOR.'chunk_'.$chunkIndex;

        $chunkFile->move($uploadDir, 'chunk_'.$chunkIndex);

        return $chunkPath;
    }

    /**
     * Check if all chunks from 0 to totalChunks - 1 are present.
     */
    public function hasAllChunks(string $uploadId, int $totalChunks): bool
    {
        $uploadDir = $this->getUploadDir($uploadId);
        for ($i = 0; $i < $totalChunks; $i++) {
            if (! File::exists($uploadDir.DIRECTORY_SEPARATOR.'chunk_'.$i)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assemble all chunks into a single unified file.
     */
    public function assembleChunks(string $uploadId, int $totalChunks, string $originalFileName): string
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $safeFileName = basename($originalFileName);
        $mergedFilePath = $uploadDir.DIRECTORY_SEPARATOR.'merged_'.$safeFileName;

        $target = fopen($mergedFilePath, 'wb');
        if (! $target) {
            throw new RuntimeException("Unable to create target assembled file at: {$mergedFilePath}");
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFilePath = $uploadDir.DIRECTORY_SEPARATOR.'chunk_'.$i;
            if (! File::exists($chunkFilePath)) {
                fclose($target);
                throw new RuntimeException("Missing chunk index {$i} for upload session {$uploadId}");
            }

            $chunkStream = fopen($chunkFilePath, 'rb');
            if (! $chunkStream) {
                fclose($target);
                throw new RuntimeException("Unable to open chunk index {$i} for reading.");
            }

            stream_copy_to_stream($chunkStream, $target);
            fclose($chunkStream);
            @unlink($chunkFilePath);
        }

        fclose($target);

        $metadata = [
            'upload_id' => $uploadId,
            'original_name' => $safeFileName,
            'file_name' => 'merged_'.$safeFileName,
            'size' => filesize($mergedFilePath),
            'mime_type' => @mime_content_type($mergedFilePath) ?: 'application/octet-stream',
            'assembled_at' => time(),
        ];

        File::put($uploadDir.DIRECTORY_SEPARATOR.'metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        return $mergedFilePath;
    }

    /**
     * Locate the merged file for a given upload session.
     */
    public function getMergedFilePath(string $uploadId): ?string
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $metadataFile = $uploadDir.DIRECTORY_SEPARATOR.'metadata.json';

        if (File::exists($metadataFile)) {
            $metadata = json_decode(File::get($metadataFile), true);
            if (! empty($metadata['file_name'])) {
                $path = $uploadDir.DIRECTORY_SEPARATOR.$metadata['file_name'];
                if (File::exists($path)) {
                    return $path;
                }
            }
        }

        // Fallback: look for merged_* files
        $files = File::glob($uploadDir.DIRECTORY_SEPARATOR.'merged_*');
        if (! empty($files)) {
            return $files[0];
        }

        return null;
    }

    /**
     * Get the metadata for an upload session.
     */
    public function getMetadata(string $uploadId): ?array
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $metadataFile = $uploadDir.DIRECTORY_SEPARATOR.'metadata.json';

        if (File::exists($metadataFile)) {
            return json_decode(File::get($metadataFile), true);
        }

        return null;
    }

    /**
     * Create an UploadedFile instance from the assembled chunk file.
     */
    public function createUploadedFile(string $uploadId, ?string $originalName = null, ?string $mimeType = null): ?UploadedFile
    {
        $mergedPath = $this->getMergedFilePath($uploadId);
        if (! $mergedPath || ! File::exists($mergedPath)) {
            return null;
        }

        $metadata = $this->getMetadata($uploadId);
        $name = $originalName ?? ($metadata['original_name'] ?? basename($mergedPath));
        $mime = $mimeType ?? ($metadata['mime_type'] ?? (@mime_content_type($mergedPath) ?: 'application/octet-stream'));

        return new UploadedFile(
            $mergedPath,
            $name,
            $mime,
            null,
            true // $test mode = true to consider it valid without is_uploaded_file check
        );
    }

    /**
     * Clean up an upload session directory.
     */
    public function cleanupUpload(string $uploadId): bool
    {
        $safeUploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $uploadId);
        if (empty($safeUploadId)) {
            return false;
        }

        $dir = $this->basePath.DIRECTORY_SEPARATOR.$safeUploadId;
        if (File::isDirectory($dir)) {
            return File::deleteDirectory($dir);
        }

        return true;
    }

    /**
     * Delete abandoned chunks older than the given number of seconds (default 24h).
     */
    public function cleanupOldUploads(int $maxAgeInSeconds = 86400): int
    {
        if (! File::isDirectory($this->basePath)) {
            return 0;
        }

        $deletedCount = 0;
        $directories = File::directories($this->basePath);
        $cutoffTime = time() - $maxAgeInSeconds;

        foreach ($directories as $directory) {
            if (File::lastModified($directory) < $cutoffTime) {
                File::deleteDirectory($directory);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
