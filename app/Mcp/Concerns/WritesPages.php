<?php

namespace App\Mcp\Concerns;

use App\Models\Page;
use App\Models\Project;

/**
 * Shared write logic for pages, mirroring what the Nova Page resource does on save.
 */
trait WritesPages
{
    /**
     * Pages render their own title as the only <h1>, so content headings are demoted (same as Nova).
     */
    protected function normalizeContent(string $content): string
    {
        return str_replace(['<h1>', '</h1>'], ['<h2>', '</h2>'], $content);
    }

    protected function findPage(int $id): ?Page
    {
        return Page::query()->withoutGlobalScopes()->find($id);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function syncPageRelations(Page $page, array $validated): void
    {
        if (array_key_exists('related_page_ids', $validated)) {
            $page->pages()->sync($validated['related_page_ids'] ?? []);
        }
        if (array_key_exists('project_ids', $validated)) {
            $page->projects()->sync($validated['project_ids'] ?? []);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationRules(): array
    {
        return [
            'related_page_ids' => ['sometimes', 'array'],
            'related_page_ids.*' => ['integer', 'exists:'.Page::class.',id'],
            'project_ids' => ['sometimes', 'array'],
            'project_ids.*' => ['integer', 'exists:'.Project::class.',id'],
        ];
    }
}
