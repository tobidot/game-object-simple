<?php

namespace App\Http\Controllers;

use App\Enums\LiveSyncSection;
use App\Services\LiveSyncService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * A `/storage/...` file this instance doesn't have (existing files never reach PHP), served
     * from the live site, so rows pulled without their files still show their images and run their
     * code. Local instances only: everywhere else it is a plain 404.
     */
    public function storageFile(LiveSyncService $service, string $path): StreamedResponse
    {
        $response = $service->liveFile($path) ?? abort(404);
        $body = $response->toPsrResponse()->getBody();

        return response()->stream(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(1 << 20);
            }
        }, 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
