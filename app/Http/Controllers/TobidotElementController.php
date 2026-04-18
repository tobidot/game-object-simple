<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Requests\UploadTobidotElementRequest;
use App\Http\Resources\TobidotElementResource;
use App\Models\TobidotElement;
use App\Services\Models\TobidotElementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TobidotElementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'version' => 1,
            'packages' => TobidotElement::query()
                ->with('dependencies')
                ->get()
                ->groupBy('name')
                ->map(
                    function (Collection $items) {
                        return $items->map(fn(TobidotElement $element) => new TobidotElementResource($element));
                    }
                ),
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
                $request->description ?? null
            );

        return response()->json([
            'message' => 'Tobidot element uploaded successfully',
            'element' => new TobidotElementResource($element),
        ]);
    }
}
