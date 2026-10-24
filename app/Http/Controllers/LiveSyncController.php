<?php

namespace App\Http\Controllers;

use App\Enums\LiveSyncSection;
use App\Services\LiveSyncService;
use Illuminate\Http\JsonResponse;

/**
 * Serves one section of this site's data to a local instance pulling from live
 * (LiveSyncService::pull()). Like every `/api/v1` route it needs a Sanctum token, which only the
 * admin issues (Nova "Generate API Token"); the export includes private pages and every attachment row.
 */
class LiveSyncController extends Controller
{
    public function export(LiveSyncService $service, string $section): JsonResponse
    {
        return response()->json($service->export(LiveSyncSection::from($section)));
    }
}
