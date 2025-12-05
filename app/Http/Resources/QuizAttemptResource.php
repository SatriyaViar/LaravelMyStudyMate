<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'quiz_id'       => $this->quiz_id,
            'status'        => $this->status,
            'started_at'    => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at'  => $this->completed_at?->format('Y-m-d H:i:s'),
            'time_spent'    => $this->time_spent,
            'score'         => $this->score,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
            // Tambahkan quiz relasi Kalo butuh detail quiz
            // 'quiz'          => new QuizResource($this->whenLoaded('quiz')),
        ];
    }
}