<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateTceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'element-name' => [
                'required_without:name',
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-]+$/',
            ],
            'name' => [
                'required_without:element-name',
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-]+$/',
            ],
            'title' => [
                'nullable',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'version' => [
                'nullable',
                'string',
                'regex:/^\d+\.\d+\.\d+$/',
            ],
            'kind' => [
                'nullable',
                'in:element,library',
            ],
            'width' => [
                'nullable',
                'integer',
                'min:50',
                'max:3840',
            ],
            'height' => [
                'nullable',
                'integer',
                'min:50',
                'max:2160',
            ],
            'template' => [
                'nullable',
                'in:standard,canvas,minimal',
            ],
            'dependencies' => [
                'nullable',
                'array',
            ],
            'dependencies.*' => [
                'string',
                'max:255',
            ],
            'tce_dependencies' => [
                'nullable',
                'array',
            ],
            'tce_dependencies.*' => [
                'string',
                'max:255',
            ],
            'custom_dependencies' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'icon' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,svg',
                'max:2048',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'element-name.regex' => 'The element name may only contain letters, numbers, dashes, and underscores.',
            'version.regex' => 'The version must follow Semantic Versioning format (e.g. 1.0.0).',
            'width.min' => 'Width must be at least 50 pixels.',
            'height.min' => 'Height must be at least 50 pixels.',
            'icon.mimes' => 'Icon must be a PNG, JPG, JPEG, or SVG file.',
        ];
    }
}
