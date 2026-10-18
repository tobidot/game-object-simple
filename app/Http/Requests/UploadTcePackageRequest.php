<?php

namespace App\Http\Requests;

use App\Services\Models\TcePackageService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * `POST /api/v1/tce-packages` — see "Publishing & Discovery: REST Upload". Keep this in sync
 * with the MCP tool `upload_tce_package`; both hand their input to `TcePackageService`.
 */
class UploadTcePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return request()->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $identifier_rule = ['string', 'max:255', 'regex:/^[0-9a-zA-Z\-_]+$/'];

        return [
            'namespace' => ['required', ...$identifier_rule],
            'name' => ['required', ...$identifier_rule],
            'zip' => ['required', 'file'],
            'icon' => ['sometimes', 'file', 'image:allow_svg'],
            'version' => ['sometimes', 'string', 'regex:/^\d+\.\d+\.\d+$/', 'max:255'],
            'kind' => ['sometimes', 'string', 'in:'.implode(',', TcePackageService::KINDS)],
            'description' => ['sometimes', 'string'],
            'width' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'height' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'dependencies' => ['sometimes', 'array'],
            'dependencies.*.namespace' => ['required', ...$identifier_rule],
            'dependencies.*.name' => ['required', ...$identifier_rule],
        ];
    }

    /**
     * The declared dependency identifiers, or `null` when the field was omitted (carry over).
     *
     * @return array<int, array{namespace: string, name: string}>|null
     */
    public function dependencies(): ?array
    {
        if (! $this->has('dependencies')) {
            return null;
        }

        return array_map(
            fn (array $dependency) => ['namespace' => $dependency['namespace'], 'name' => $dependency['name']],
            $this->validated('dependencies', []),
        );
    }
}
