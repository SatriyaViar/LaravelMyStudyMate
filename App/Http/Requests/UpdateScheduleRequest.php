<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'lecturer' => 'nullable|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'type' => 'sometimes|required|in:lecture,lab,meeting,event,assignment,other',
            'has_reminder' => 'nullable|boolean',
            'reminder_minutes' => 'nullable|integer|min:1|max:1440',
            'is_completed' => 'nullable|boolean',
            'is_done' => 'nullable|boolean',
        ];

        // For assignment type, time validation is lenient
        if ($this->type === 'assignment') {
            $rules['start_time'] = 'nullable|date_format:H:i';
            $rules['end_time'] = 'nullable|date_format:H:i';
        } else {
            $rules['start_time'] = 'sometimes|required|date_format:H:i';
            $rules['end_time'] = 'sometimes|required|date_format:H:i|after:start_time';
        }

        return $rules;
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul schedule wajib diisi',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai',
            'type.in' => 'Tipe schedule tidak valid',
        ];
    }
}