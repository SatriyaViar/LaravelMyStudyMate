<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'title'             => $this->title,
            'description'       => $this->description,
            'material_type'     => $this->material_type,
            'material_content'  => $this->when(
                $this->material_type === 'text',
                $this->material_content
            ),
            'material_file'     => $this->when(
                $this->material_type === 'file',
                [
                    'url'   => $this->material_file_url ? asset('storage/' . $this->material_file_url) : null,
                    'name'  => $this->material_file_name,
                    'type'  => $this->material_file_type,
                    'size'  => $this->material_file_size,
                ]
            ),
            'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
            // tambahkan relasi ini kalau butuh , misal: quiz collection
            // 'quizzes'           => QuizResource::collection($this->whenLoaded('quizzes')),
        ];
    }
}