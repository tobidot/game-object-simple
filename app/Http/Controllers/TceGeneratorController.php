<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\GenerateTceRequest;
use App\Services\TceGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TceGeneratorController extends Controller
{
    /**
     * The generator form, listing every registry package that can be declared as a dependency.
     */
    public function index(): View
    {
        $service = AppHelper::resolve(TceGeneratorService::class);

        return view('tce-generator.index', [
            'available_dependencies' => $service->availableDependencies(),
            'default_namespace' => (string) config('tce.default_namespace'),
            'sdk_published' => $service->isSdkPublished(),
        ]);
    }

    /**
     * Web UI: generate the project and download it as a zip.
     */
    public function download(GenerateTceRequest $request): BinaryFileResponse
    {
        [$config, $zipPath] = $this->generate($request);

        return response()->download($zipPath, $config->getZipFilename(), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    /**
     * REST API: the same generation; a JSON client gets the zip embedded as base64 next to the
     * resolved config, anything else gets the file download.
     */
    public function apiGenerate(GenerateTceRequest $request): BinaryFileResponse|JsonResponse
    {
        [$config, $zipPath] = $this->generate($request);

        if ($request->wantsJson()) {
            $base64 = base64_encode((string) file_get_contents($zipPath));
            @unlink($zipPath);

            return response()->json([
                'success' => true,
                'message' => 'TCE project generated successfully.',
                'filename' => $config->getZipFilename(),
                'config' => $config->toArray(),
                'archive_base64' => $base64,
            ]);
        }

        return response()->download($zipPath, $config->getZipFilename(), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    /**
     * @return array{0: \App\DTO\TceGeneratorConfig, 1: string}
     *
     * @throws ValidationException for input the registry cannot satisfy (unknown dependency)
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException when the SDK is not published
     */
    protected function generate(GenerateTceRequest $request): array
    {
        $service = AppHelper::resolve(TceGeneratorService::class);

        try {
            $config = $service->prepareConfig([...$request->validated(), 'icon' => $request->file('icon')]);
            $zipPath = $service->generateZip($config);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['dependencies' => [$exception->getMessage()]]);
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        return [$config, $zipPath];
    }
}
