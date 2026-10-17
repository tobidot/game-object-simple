<?php

namespace App\Mcp\Concerns;

use App\Helpers\AppHelper;
use App\Models\Attachment;
use App\Models\Page;
use App\Services\Models\AttachmentService;

/**
 * Shared array representations of models returned by the MCP tools.
 */
trait PresentsModels
{
    /**
     * @return array<string, mixed>
     */
    protected function presentPage(Page $page, bool $withContent = false): array
    {
        $data = [
            'id' => $page->id,
            'title' => $page->title,
            'uri' => $page->uri,
            'url' => route('pages.show', ['page' => $page]),
            'publish_state' => $page->publish_state->value,
            'allow_comments' => (bool) $page->allow_comments,
            'thumbnail' => $page->thumbnail,
            'created_at' => $page->created_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];

        if ($withContent) {
            $data['content'] = $page->content;
            $data['attachments'] = $page->attachments()->get()
                ->map(fn (Attachment $attachment) => $this->presentAttachment($attachment))
                ->values()
                ->all();
            $data['related_page_ids'] = $page->pages()->withoutGlobalScopes()->pluck('pages.id')->all();
            $data['project_ids'] = $page->projects()->withoutGlobalScopes()->pluck('projects.id')->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentAttachment(Attachment $attachment): array
    {
        $data = [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'type' => $attachment->type->value,
            'content_type' => $attachment->content_type,
            'publish_state' => $attachment->publish_state->value,
            'url' => AppHelper::resolve(AttachmentService::class)->getUrl($attachment) ?? $attachment->url,
            'relative_url' => $attachment->url,
            'created_at' => $attachment->created_at?->toIso8601String(),
        ];

        if ($attachment->pivot?->relation !== null) {
            $data['relation'] = $attachment->pivot->relation;
        }

        return $data;
    }
}
