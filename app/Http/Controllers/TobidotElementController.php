<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\UploadTobidotElementRequest;
use App\Http\Resources\TobidotElementResource;
use App\Models\TobidotElement;
use App\Services\Models\TobidotElementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TobidotElementController extends Controller
{
    /**
     * Show a single tobidot element loaded fullscreen and borderless, without the desktop shell.
     */
    public function view(TobidotElement $tobidotElement): View
    {
        return view('tobidot-elements.view', [
            'element' => $tobidotElement,
        ]);
    }

    /**
     * Fullscreen play page for the latest version of an element, addressed by its name.
     */
    public function play(string $name): View
    {
        $element = TobidotElement::query()
            ->where('name', $name)
            ->where('kind', 'element')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->firstOrFail();

        return $this->view($element);
    }

    public function index(): JsonResponse
    {
        $grouped = TobidotElement::query()
            // Elements without code (e.g. a failed upload) can't be loaded and break client-side registry validation.
            ->whereHas('codeAttachment')
            ->with('dependencies')
            ->get()
            ->filter($this->isValid(...))
            ->groupBy('name')
            ->map(
                function (Collection $items) {
                    return $items->values()->map(fn (TobidotElement $element) => new TobidotElementResource($element));
                }
            );

        return response()->json([
            'version' => 1,
            'packages' => $grouped->isEmpty() ? (object) [] : $grouped,
        ]);
    }

    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function upload(
        UploadTobidotElementRequest $request
    ): JsonResponse {

        $element = AppHelper::resolve(TobidotElementService::class)
            ->upload(
                $request->name,
                $request->zip,
                $request->version ?? null,
                $request->kind ?? null,
                $request->description ?? null,
                $request->file('icon')
            );

        return response()->json([
            'message' => 'Tobidot element uploaded successfully',
            'element' => new TobidotElementResource($element),
        ]);
    }

    protected function isValid(TobidotElement $element): bool
    {
        return (bool) $element->name;
    }
}
