<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'study_card_id'         => 'required|integer|exists:study_cards,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string|max:1000',
            'duration_minutes'      => 'nullable|integer|min:1|max:180',
            'shuffle_questions'     => 'nullable|boolean',
            'shuffle_answers'       => 'nullable|boolean',
            'show_correct_answers'  => 'nullable|boolean',
        ];
    }
}