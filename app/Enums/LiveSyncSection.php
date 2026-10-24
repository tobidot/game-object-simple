<?php

namespace App\Enums;

use App\Models\CodeRelease;
use App\Models\Page;
use App\Models\Project;
use App\Models\TcePackage;
use App\Models\TobidotElement;

/**
 * The parts of the site that can be pulled from the live site into a local instance
 * (see LiveSyncService). Each section owns its tables completely; the shared polymorphic tables
 * (`attachables`, `nova_trix_attachments`) are owned per morph type.
 */
enum LiveSyncSection: string
{
    case POSTS = 'posts';
    case MEDIA = 'media';
    case CODE = 'code';

    /** Tables this section replaces completely. */
    public function tables(): array
    {
        return match ($this) {
            self::POSTS => ['pages', 'page_page', 'page_project'],
            self::MEDIA => ['attachments'],
            self::CODE => [
                'projects', 'project_project', 'code_releases',
                'tobidot_elements', 'tobidot_element_dependencies',
                'tce_packages', 'tce_package_dependencies',
            ],
        };
    }

    /** Morph types whose rows in `attachables` and `nova_trix_attachments` this section replaces. */
    public function morphTypes(): array
    {
        $models = match ($this) {
            self::POSTS => [Page::class],
            self::MEDIA => [],
            self::CODE => [Project::class, CodeRelease::class, TobidotElement::class, TcePackage::class],
        };

        return array_map(fn (string $model) => (new $model)->getMorphClass(), $models);
    }

    public function label(): string
    {
        return match ($this) {
            self::POSTS => 'blog posts',
            self::MEDIA => 'media',
            self::CODE => 'projects and custom elements',
        };
    }
}
