<?php

namespace App\Mcp\Tools;

use App\Enums\PublishState;
use App\Mcp\Concerns\PresentsModels;
use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_pages')]
#[IsReadOnly]
#[Description('List pages (blog posts / articles), newest first. Supports searching title/uri and filtering by publish state. Does not include page content; use get_page for that.')]
class ListPagesTool extends Tool
{
    use PresentsModels;

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'publish_state' => ['nullable', Rule::enum(PublishState::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = Page::query()
            ->withoutGlobalScopes()
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($q) => $q
                    ->where('title', 'like', "%$search%")
                    ->orWhere('uri', 'like', "%$search%"));
            })
            ->when($validated['publish_state'] ?? null, fn ($query, string $state) => $query->where('publish_state', $state))
            ->latest('id')
            ->paginate($validated['per_page'] ?? 25, ['*'], 'page', $validated['page'] ?? 1);

        return Response::structured([
            'pages' => $paginator->getCollection()->map(fn (Page $page) => $this->presentPage($page))->values()->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Substring to match against title or uri.'),
            'publish_state' => $schema->string()->enum(array_column(PublishState::cases(), 'value')),
            'page' => $schema->integer()->min(1)->description('Pagination page, starting at 1.'),
            'per_page' => $schema->integer()->min(1)->max(100)->description('Defaults to 25.'),
        ];
    }
}
