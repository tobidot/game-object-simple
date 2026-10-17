<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\Page;
use App\Services\Models\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    /**
     * Multipart upload for files too large to send base64 encoded through the MCP "upload_media" tool.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'prefix' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-_\/]+$/'],
            'page_id' => ['nullable', 'integer', 'exists:'.Page::class.',id'],
            'relation' => ['nullable', 'string', 'max:255'],
        ]);

        $attachmentService = AppHelper::resolve(AttachmentService::class);
        $attachment = $attachmentService->createFromUploadedSingleFile($request->file('file'), $validated['prefix'] ?? 'media');

        if (isset($validated['page_id'])) {
            $page = Page::query()->withoutGlobalScopes()->findOrFail($validated['page_id']);
            $relation = $validated['relation'] ?? 'attachment';
            $page->attachments()->wherePivot('relation', $relation)->detach($attachment->id);
            $page->attachments()->attach($attachment->id, ['relation' => $relation]);
        }

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'type' => $attachment->type->value,
                'content_type' => $attachment->content_type,
                'url' => $attachmentService->getUrl($attachment),
                'relative_url' => $attachment->url,
            ],
        ], 201);
    }
}
