<?php

namespace App\Mcp\Tools;

use App\Enums\AttachmentType;
use App\Mcp\Concerns\PresentsModels;
use App\Models\Attachment;
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

#[Name('list_media')]
#[IsReadOnly]
#[Description('List uploaded media (attachments), newest first. Filter by type, file name, or by the page they are attached to.')]
class ListMediaTool extends Tool
{
    use PresentsModels;

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(AttachmentType::class)],
            'page_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = Attachment::query()
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('file_name', 'like', "%$search%"))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['page_id'] ?? null, fn ($query, int $pageId) => $query->whereHas(
                'pages',
                fn ($q) => $q->withoutGlobalScopes()->whereKey($pageId),
            ))
            ->latest('id')
            ->paginate($validated['per_page'] ?? 25, ['*'], 'page', $validated['page'] ?? 1);

        return Response::structured([
            'media' => $paginator->getCollection()->map(fn (Attachment $a) => $this->presentAttachment($a))->values()->all(),
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
            'search' => $schema->string()->description('Substring to match against the file name.'),
            'type' => $schema->string()->enum(array_column(AttachmentType::cases(), 'value')),
            'page_id' => $schema->integer()->description('Only media attached to this page.'),
            'page' => $schema->integer()->min(1),
            'per_page' => $schema->integer()->min(1)->max(100)->description('Defaults to 25.'),
        ];
    }
}
