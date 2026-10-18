<?php

namespace App\Http\Requests;

use App\DTO\TceGeneratorConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Input of the project generator (Web UI, `POST /api/public/tce-generator`). The shape is
 * validated here; registry facts (does a dependency exist, is the SDK published) are checked
 * by `TceGeneratorConfig::fromArray` / `TceGeneratorService`.
 */
class GenerateTceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'namespace' => ['nullable', 'string', 'max:255', 'regex:'.TceGeneratorConfig::IDENTIFIER_PATTERN],
            'name' => ['required', 'string', 'max:100', 'regex:'.TceGeneratorConfig::NAME_PATTERN],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'version' => ['nullable', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'kind' => ['nullable', 'in:'.implode(',', TceGeneratorConfig::KINDS)],
            'width' => ['nullable', 'integer', 'min:50', 'max:3840'],
            'height' => ['nullable', 'integer', 'min:50', 'max:2160'],
            'template' => ['nullable', 'in:'.implode(',', TceGeneratorConfig::TEMPLATES)],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*' => ['string', 'max:255', 'regex:/^([0-9a-zA-Z\-_]+\/)?[0-9a-zA-Z\-_]+$/'],
            'icon' => ['nullable', 'file', 'mimes:svg,png', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name must be lower-case letters, digits and hyphens with at least one hyphen, e.g. tce-my-element or dll-my-library.',
            'namespace.regex' => 'The namespace may only contain letters, digits, dashes and underscores.',
            'version.regex' => 'The version must be major.minor.patch, e.g. 0.1.0.',
            'dependencies.*.regex' => 'Dependencies are written as namespace/name.',
            'icon.mimes' => 'The icon must be an SVG or PNG file.',
        ];
    }
}
