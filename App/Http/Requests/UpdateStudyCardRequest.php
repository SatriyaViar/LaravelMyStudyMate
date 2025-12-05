<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudyCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'sometimes|required|in:text,file',
            'material_content' => 'required_if:material_type,text|string',
            'material_file' => 'nullable|file|mimes:pdf|max:10240', // ✅ Hanya PDF
        ];
    }

    public function messages(): array
    {
        return [
            'material_file.mimes' => 'Only PDF files are supported for quiz generation.',
            'material_file.max' => 'File size cannot exceed 10MB.',
        ];
    }
}