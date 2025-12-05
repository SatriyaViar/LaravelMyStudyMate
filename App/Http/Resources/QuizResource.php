<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'study_card_id'      => $this->study_card_id,
            'title'              => $this->title,
            'description'        => $this->description,
            'duration_minutes'   => $this->duration_minutes,
            'shuffle_questions'  => (bool) $this->shuffle_questions,
            'shuffle_answers'    => (bool) $this->shuffle_answers,
            'show_correct_answers' => (bool) $this->show_correct_answers,
            'generated_by_ai'    => (bool) $this->generated_by_ai,
            'created_at'         => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'         => $this->updated_at?->format('Y-m-d H:i:s'),
            // Tambahkan Kalo perlu tampilkan question (optional) 
            // 'questions'          => QuizQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}