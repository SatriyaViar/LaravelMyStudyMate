<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date->format('Y-m-d'),
            'start_time' => date('H:i', strtotime($this->start_time)),
            'end_time' => date('H:i', strtotime($this->end_time)),
            'location' => $this->location,
            'color' => $this->color,
            'type' => $this->type,
            'has_reminder' => $this->has_reminder,
            'reminder_minutes' => $this->reminder_minutes,
            'is_completed' => $this->is_completed,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}