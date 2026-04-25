<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadTobidotElementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'file'],
            'icon' => ['sometimes', 'file', 'image'],
            'version' => ['sometimes', 'string', 'regex:/^\d+\.\d+\.\d+$/', 'max:255'],
            'kind' => ['sometimes', 'in:element,library', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
        ];
    }
}
