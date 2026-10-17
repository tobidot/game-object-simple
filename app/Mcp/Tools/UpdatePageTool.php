<?php

namespace App\Mcp\Tools;

use App\Enums\PublishState;
use App\Mcp\Concerns\PresentsModels;
use App\Mcp\Concerns\WritesPages;
use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_page')]
#[IsIdempotent]
#[Description(<<<'TEXT'
Update an existing page. Only the fields you pass are changed.
"content" replaces the whole HTML body, so fetch it with get_page first when making partial edits.
"related_page_ids" / "project_ids" replace the full list of relations when given.
TEXT)]
class UpdatePageTool extends Tool
{
    use PresentsModels;
    use WritesPages;

    public function handle(Request $request): Response|ResponseFactory
    {
        $page = $this->findPage((int) $request->get('id'));
        if (! $page) {
            return Response::error('Page not found.');
        }

        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['sometimes', 'string', 'max:255'],
            'uri' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9\-\/]+$/', Rule::unique(Page::class, 'uri')->ignore($page->id)],
            'content' => ['sometimes', 'string', 'max:65535'],
            'publish_state' => ['sometimes', Rule::enum(PublishState::class)],
            'allow_comments' => ['sometimes', 'boolean'],
            ...$this->relationRules(),
        ]);

        DB::transaction(function () use ($page, $validated) {
            if (array_key_exists('title', $validated)) {
                $page->title = $validated['title'];
            }
            if (array_key_exists('uri', $validated)) {
                $page->uri = $validated['uri'];
            }
            if (array_key_exists('content', $validated)) {
                $page->content = $this->normalizeContent($validated['content']);
            }
            if (array_key_exists('publish_state', $validated)) {
                $page->publish_state = PublishState::from($validated['publish_state']);
            }
            if (array_key_exists('allow_comments', $validated)) {
                $page->allow_comments = $validated['allow_comments'];
            }
            $page->save();

            $this->syncPageRelations($page, $validated);
        });

        return Response::structured($this->presentPage($page->refresh(), withContent: true));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('Id of the page to update.'),
            'title' => $schema->string()->max(255),
            'uri' => $schema->string()->max(255)->pattern('^[a-z0-9\-/]+$'),
            'content' => $schema->string()->description('Full replacement HTML body.'),
            'publish_state' => $schema->string()->enum(array_column(PublishState::cases(), 'value')),
            'allow_comments' => $schema->boolean(),
            'related_page_ids' => $schema->array()->items($schema->integer()),
            'project_ids' => $schema->array()->items($schema->integer()),
        ];
    }
}
