<?php

namespace App\Mcp\Tools;

use App\Helpers\AppHelper;
use App\Mcp\Concerns\PresentsModels;
use App\Mcp\Concerns\WritesPages;
use App\Services\Models\AttachmentService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('upload_media')]
#[Description(<<<'TEXT'
Upload a media file (image, video, zip, ...) as an attachment and optionally attach it to a page.
Provide exactly one of "content_base64" (file bytes, base64 encoded) or "source_url" (public http(s) URL the server downloads).
Base64 is only practical for small files; for large local files POST multipart/form-data to /api/v1/attachments instead (same Bearer token, field "file").
Identical files are deduplicated: re-uploading returns the existing attachment.
Returns the attachment including its public "url", ready to be used in page HTML, e.g. <img src="...">.
TEXT)]
class UploadMediaTool extends Tool
{
    use PresentsModels;
    use WritesPages;

    public const MAX_BYTES = 50 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'file_name' => ['required_with:content_base64', 'nullable', 'string', 'max:200'],
            'content_base64' => ['required_without:source_url', 'prohibits:source_url', 'nullable', 'string'],
            'source_url' => ['required_without:content_base64', 'nullable', 'url:http,https'],
            'prefix' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-_\/]+$/'],
            'page_id' => ['nullable', 'integer'],
            'relation' => ['nullable', 'string', 'max:255'],
        ]);

        $page = null;
        if (isset($validated['page_id'])) {
            $page = $this->findPage($validated['page_id']);
            if (! $page) {
                return Response::error('Page not found.');
            }
        }

        if (isset($validated['content_base64'])) {
            $bytes = base64_decode(preg_replace('/^data:[^,]*,/', '', $validated['content_base64']), true);
            if ($bytes === false) {
                return Response::error('content_base64 is not valid base64.');
            }
            $fileName = $validated['file_name'];
        } else {
            $download = Http::timeout(60)->get($validated['source_url']);
            if (! $download->successful()) {
                return Response::error("Downloading source_url failed with HTTP {$download->status()}.");
            }
            $bytes = $download->body();
            $fileName = $validated['file_name']
                ?? basename((string) parse_url($validated['source_url'], PHP_URL_PATH))
                ?: 'download';
        }

        if ($bytes === '') {
            return Response::error('The file is empty.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            return Response::error('The file exceeds the maximum size of '.(self::MAX_BYTES / 1024 / 1024).' MB.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mcp-upload-');
        try {
            file_put_contents($tmpPath, $bytes);
            $file = new UploadedFile($tmpPath, $this->sanitizeFileName($fileName), mime_content_type($tmpPath) ?: null, null, true);

            $attachment = AppHelper::resolve(AttachmentService::class)
                ->createFromUploadedSingleFile($file, $validated['prefix'] ?? 'media');
        } finally {
            @unlink($tmpPath);
        }

        if ($page) {
            $relation = $validated['relation'] ?? 'attachment';
            $page->attachments()->wherePivot('relation', $relation)->detach($attachment->id);
            $page->attachments()->attach($attachment->id, ['relation' => $relation]);
        }

        return Response::structured([
            'attachment' => $this->presentAttachment($attachment),
            'attached_to_page_id' => $page?->id,
        ]);
    }

    protected function sanitizeFileName(string $fileName): string
    {
        $extension = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($fileName, PATHINFO_FILENAME)) ?: 'file';

        return $extension !== '' ? "$name.".preg_replace('/[^a-z0-9]/', '', $extension) : $name;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'file_name' => $schema->string()->max(200)
                ->description('File name incl. extension, e.g. "cover.png". Required with content_base64; defaults to the URL basename for source_url.'),
            'content_base64' => $schema->string()->description('Base64 encoded file content (a data: URI prefix is allowed).'),
            'source_url' => $schema->string()->format('uri')->description('http(s) URL to download the file from.'),
            'prefix' => $schema->string()->description('Storage folder prefix. Defaults to "media".'),
            'page_id' => $schema->integer()->description('Optional page to attach the uploaded file to.'),
            'relation' => $schema->string()
                ->description('Relation when attaching to a page. "thumbnail" makes it the page thumbnail. Defaults to "attachment".'),
        ];
    }
}
