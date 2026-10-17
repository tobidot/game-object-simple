<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AttachMediaTool;
use App\Mcp\Tools\CreatePageTool;
use App\Mcp\Tools\DetachMediaTool;
use App\Mcp\Tools\GetMediaTool;
use App\Mcp\Tools\GetPageTool;
use App\Mcp\Tools\ListMediaTool;
use App\Mcp\Tools\ListPagesTool;
use App\Mcp\Tools\UpdateMediaTool;
use App\Mcp\Tools\UpdatePageTool;
use App\Mcp\Tools\UploadMediaTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('game-object.de Content')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Manage the content of the game-object.de website: pages (blog posts / articles) and media (attachments such as images).
Typical flow for a post with images: upload_media (get the url) -> create_page or update_page with <img src="url"> in the HTML content
-> attach_media with relation "thumbnail" to set the cover image. New pages are private until publish_state is set to "published".
TEXT)]
class ContentServer extends Server
{
    protected array $tools = [
        ListPagesTool::class,
        GetPageTool::class,
        CreatePageTool::class,
        UpdatePageTool::class,
        ListMediaTool::class,
        GetMediaTool::class,
        UploadMediaTool::class,
        UpdateMediaTool::class,
        AttachMediaTool::class,
        DetachMediaTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
