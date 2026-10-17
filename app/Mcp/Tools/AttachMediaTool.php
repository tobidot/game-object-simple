<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\PresentsModels;
use App\Mcp\Concerns\WritesPages;
use App\Models\Attachment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('attach_media')]
#[IsIdempotent]
#[Description('Attach an existing media attachment to a page. Use relation "thumbnail" to make it the page thumbnail (the newest thumbnail wins).')]
class AttachMediaTool extends Tool
{
    use PresentsModels;
    use WritesPages;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'attachment_id' => ['required', 'integer'],
            'page_id' => ['required', 'integer'],
            'relation' => ['nullable', 'string', 'max:255'],
        ]);

        $attachment = Attachment::query()->find($validated['attachment_id']);
        if (! $attachment) {
            return Response::error('Attachment not found.');
        }
        $page = $this->findPage($validated['page_id']);
        if (! $page) {
            return Response::error('Page not found.');
        }

        $relation = $validated['relation'] ?? 'attachment';
        $page->attachments()->wherePivot('relation', $relation)->detach($attachment->id);
        $page->attachments()->attach($attachment->id, ['relation' => $relation]);

        return Response::structured($this->presentPage($page->refresh(), withContent: true));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'attachment_id' => $schema->integer()->required(),
            'page_id' => $schema->integer()->required(),
            'relation' => $schema->string()->description('e.g. "thumbnail" or "attachment" (default).'),
        ];
    }
}
