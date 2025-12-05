<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudyCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'required|in:text,file',
            'material_content' => 'required_if:material_type,text|string',
            'material_file' => 'required_if:material_type,file|file|mimes:pdf|max:10240', // ✅ Hanya PDF, max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'material_file.mimes' => 'Only PDF files are supported for quiz generation.',
            'material_file.max' => 'File size cannot exceed 10MB.',
            'material_content.required_if' => 'Material content is required when material type is text.',
            'material_file.required_if' => 'Material file is required when material type is file.',
        ];
    }
}