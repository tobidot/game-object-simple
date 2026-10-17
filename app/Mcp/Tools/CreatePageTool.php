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

#[Name('create_page')]
#[Description(<<<'TEXT'
Create a new page (blog post / article). Content is HTML; <h1> headings are converted to <h2> because the page title is the <h1>.
To embed images, upload them with upload_media first and use the returned url in an <img> tag.
New pages default to "private" so they can be reviewed before publishing.
TEXT)]
class CreatePageTool extends Tool
{
    use PresentsModels;
    use WritesPages;

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'uri' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-\/]+$/', Rule::unique(Page::class, 'uri')],
            'content' => ['required', 'string', 'max:65535'],
            'publish_state' => ['nullable', Rule::enum(PublishState::class)],
            'allow_comments' => ['nullable', 'boolean'],
            ...$this->relationRules(),
        ]);

        $page = DB::transaction(function () use ($validated) {
            $page = new Page;
            $page->title = $validated['title'];
            $page->uri = $validated['uri'];
            $page->content = $this->normalizeContent($validated['content']);
            $page->publish_state = PublishState::tryFrom($validated['publish_state'] ?? '') ?? PublishState::PRIVATE;
            $page->allow_comments = $validated['allow_comments'] ?? true;
            $page->save();

            $this->syncPageRelations($page, $validated);

            return $page;
        });

        return Response::structured($this->presentPage($page->refresh(), withContent: true));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->max(255)->required(),
            'uri' => $schema->string()->max(255)->pattern('^[a-z0-9\-/]+$')->required()
                ->description('URL slug, lowercase letters, digits, "-" and "/". Must be unique.'),
            'content' => $schema->string()->required()->description('HTML body of the page.'),
            'publish_state' => $schema->string()->enum(array_column(PublishState::cases(), 'value'))
                ->description('Defaults to "private".'),
            'allow_comments' => $schema->boolean()->description('Defaults to true.'),
            'related_page_ids' => $schema->array()->items($schema->integer())->description('Ids of related pages.'),
            'project_ids' => $schema->array()->items($schema->integer())->description('Ids of related projects.'),
        ];
    }
}
