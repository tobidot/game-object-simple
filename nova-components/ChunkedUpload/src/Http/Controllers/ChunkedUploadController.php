<?php

namespace Tobidot\ChunkedUpload\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tobidot\ChunkedUpload\ChunkManager;

class ChunkedUploadController extends Controller
{
    protected ChunkManager $chunkManager;

    public function __construct(ChunkManager $chunkManager)
    {
        $this->chunkManager = $chunkManager;
    }

    /**
     * Handle an incoming chunk upload.
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => ['required', 'string', 'max:255'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'file_name' => ['required', 'string', 'max:255'],
            'total_size' => ['nullable', 'integer', 'min:0'],
            'chunk' => ['required', 'file'],
        ]);

        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $fileName = $request->input('file_name');
        $chunkFile = $request->file('chunk');

        $this->chunkManager->saveChunk($uploadId, $chunkIndex, $chunkFile);

        // Check if all chunks have been received
        if ($this->chunkManager->hasAllChunks($uploadId, $totalChunks)) {
            $mergedPath = $this->chunkManager->assembleChunks($uploadId, $totalChunks, $fileName);
            $size = filesize($mergedPath);
            $mimeType = @mime_content_type($mergedPath) ?: 'application/octet-stream';

            return response()->json([
                'status' => 'completed',
                'upload_id' => $uploadId,
                'file_name' => $fileName,
                'size' => $size,
                'mime_type' => $mimeType,
            ]);
        }

        return response()->json([
            'status' => 'chunk_uploaded',
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
        ]);
    }

    /**
     * Cancel an active upload session and clean up temporary chunks.
     */
    public function cancelUpload(Request $request): JsonResponse
    {
        $uploadId = $request->input('upload_id');
        if ($uploadId) {
            $this->chunkManager->cleanupUpload($uploadId);
        }

        return response()->json([
            'status' => 'cancelled',
        ]);
    }
}
