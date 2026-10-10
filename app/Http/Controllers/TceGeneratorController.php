<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\GenerateTceRequest;
use App\Services\TceGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TceGeneratorController extends Controller
{
    /**
     * Display the TCE project generator interface.
     */
    public function index(): View
    {
        /** @var TceGeneratorService $generatorService */
        $generatorService = AppHelper::resolve(TceGeneratorService::class);
        $availableTceElements = $generatorService->getAvailableTceElements();
        $availableDependencies = TceGeneratorService::AVAILABLE_DEPENDENCIES;

        return view('tce-generator.index', [
            'availableTceElements' => $availableTceElements,
            'availableDependencies' => $availableDependencies,
        ]);
    }

    /**
     * Generate the TCE project template and trigger a ZIP download.
     */
    public function download(GenerateTceRequest $request): BinaryFileResponse
    {
        /** @var TceGeneratorService $generatorService */
        $generatorService = AppHelper::resolve(TceGeneratorService::class);
        $config = $generatorService->prepareConfig($request->all());
        $zipPath = $generatorService->generateZip($config);

        return response()->download($zipPath, $config->getZipFilename(), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    /**
     * API endpoint to generate and download a TCE template.
     */
    public function apiGenerate(GenerateTceRequest $request): BinaryFileResponse|JsonResponse
    {
        /** @var TceGeneratorService $generatorService */
        $generatorService = AppHelper::resolve(TceGeneratorService::class);
        $config = $generatorService->prepareConfig($request->all());
        $zipPath = $generatorService->generateZip($config);

        if ($request->wantsJson()) {
            $base64 = base64_encode(file_get_contents($zipPath));
            @unlink($zipPath);

            return response()->json([
                'success' => true,
                'message' => 'TCE project template generated successfully.',
                'filename' => $config->getZipFilename(),
                'config' => $config->toArray(),
                'archive_base64' => $base64,
            ]);
        }

        return response()->download($zipPath, $config->getZipFilename(), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }
}
