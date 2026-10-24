<?php

namespace App\Nova;

use App\Helpers\AppHelper;
use App\Services\MarkdownService;
use Laravel\Nova\Fields\Markdown\MarkdownPreset;

/**
 * Renders the Nova Markdown preview exactly like the site renders page content.
 */
class SiteMarkdownPreset implements MarkdownPreset
{
    public function convert(string $content): string
    {
        return AppHelper::resolve(MarkdownService::class)->toHtml($content);
    }
}
