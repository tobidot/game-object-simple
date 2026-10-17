<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\PresentsModels;
use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_page')]
#[IsReadOnly]
#[Description('Get a single page including its HTML content, attachments (media), related pages and projects. Look it up by id or by uri.')]
class GetPageTool extends Tool
{
    use PresentsModels;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required_without:uri', 'nullable', 'integer'],
            'uri' => ['required_without:id', 'nullable', 'string'],
        ]);

        $page = Page::query()
            ->withoutGlobalScopes()
            ->when($validated['id'] ?? null, fn ($query, int $id) => $query->whereKey($id))
            ->when($validated['uri'] ?? null, fn ($query, string $uri) => $query->where('uri', $uri))
            ->first();

        if (! $page) {
            return Response::error('Page not found.');
        }

        return Response::structured($this->presentPage($page, withContent: true));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Page id.'),
            'uri' => $schema->string()->description('Page uri, e.g. "my-first-post".'),
        ];
    }
}
