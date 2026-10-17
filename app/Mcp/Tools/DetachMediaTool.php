<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\PresentsModels;
use App\Mcp\Concerns\WritesPages;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('detach_media')]
#[IsIdempotent]
#[Description('Detach a media attachment from a page. The file itself is kept. Without "relation", every link between the two is removed.')]
class DetachMediaTool extends Tool
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

        $page = $this->findPage($validated['page_id']);
        if (! $page) {
            return Response::error('Page not found.');
        }

        $page->attachments()
            ->when($validated['relation'] ?? null, fn ($query, string $relation) => $query->wherePivot('relation', $relation))
            ->detach($validated['attachment_id']);

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
            'relation' => $schema->string()->description('Only remove the link with this relation.'),
        ];
    }
}
