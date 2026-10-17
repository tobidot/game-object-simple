<?php

use App\Mcp\Servers\ContentServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes
|--------------------------------------------------------------------------
|
| MCP servers, authenticated with the same Sanctum API tokens as the
| "api/v1" routes (send them as "Authorization: Bearer <token>").
|
*/

Mcp::web('/mcp', ContentServer::class)
    ->middleware(['auth:sanctum', 'throttle:120,1']);
