<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quiz_question_id' => 'required|integer|exists:quiz_questions,id',
            'selected_answer_id' => 'required|integer|exists:quiz_answers,id',
            'time_spent_seconds' => 'nullable|integer|min:0',
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
            'quiz_question_id.required' => 'Question ID is required.',
            'quiz_question_id.exists' => 'Question not found.',
            'selected_answer_id.required' => 'Answer ID is required.',
            'selected_answer_id.exists' => 'Answer not found.',
            'time_spent_seconds.integer' => 'Time spent must be a valid number.',
            'time_spent_seconds.min' => 'Time spent cannot be negative.',
        ];
    }
}