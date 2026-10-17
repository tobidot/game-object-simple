<?php

namespace App\Mcp\Tools;

use App\Enums\AttachmentType;
use App\Helpers\AppHelper;
use App\Mcp\Concerns\PresentsModels;
use App\Models\Attachment;
use App\Services\Models\AttachmentService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_media')]
#[IsReadOnly]
#[Description('Get a single media attachment, including the pages it is attached to. With include_preview=true, raster images up to 2 MB are returned as image content so you can look at them.')]
class GetMediaTool extends Tool
{
    use PresentsModels;

    public const PREVIEW_MAX_BYTES = 2 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'include_preview' => ['nullable', 'boolean'],
        ]);

        $attachment = Attachment::query()->find($validated['id']);
        if (! $attachment) {
            return Response::error('Attachment not found.');
        }

        $data = [
            ...$this->presentAttachment($attachment),
            'pages' => $attachment->pages()->withoutGlobalScopes()->withPivot('relation')->get()
                ->map(fn ($page) => ['id' => $page->id, 'title' => $page->title, 'relation' => $page->pivot->relation])
                ->all(),
        ];

        $responses = [Response::text(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))];

        if ($validated['include_preview'] ?? false) {
            $responses[] = $this->preview($attachment);
        }

        return Response::make($responses)->withStructuredContent($data);
    }

    protected function preview(Attachment $attachment): Response
    {
        $disk = Storage::disk('public');
        $path = AppHelper::resolve(AttachmentService::class)->getPublicFilePath($attachment);
        $previewable = $attachment->type === AttachmentType::IMAGE
            && in_array($attachment->content_type, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'], true);

        if (! $previewable) {
            return Response::text('No preview: only png/jpeg/gif/webp images can be previewed.');
        }
        if (! $disk->exists($path)) {
            return Response::text('No preview: file is missing on disk.');
        }
        if ($disk->size($path) > self::PREVIEW_MAX_BYTES) {
            return Response::text('No preview: image is larger than 2 MB.');
        }

        return Response::image($disk->get($path), $attachment->content_type === 'image/jpg' ? 'image/jpeg' : $attachment->content_type);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('Attachment id.'),
            'include_preview' => $schema->boolean()->description('Return the image itself as well. Defaults to false.'),
        ];
    }
}
