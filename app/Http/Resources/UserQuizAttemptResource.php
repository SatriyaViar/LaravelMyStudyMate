<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQuizAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'user_id' => $this->user_id,
            
            // Quiz info
            'quiz' => $this->whenLoaded('quiz', function () {
                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'description' => $this->quiz->description,
                    'total_questions' => $this->quiz->total_questions,
                    'duration_minutes' => $this->quiz->duration_minutes,
                    'study_card' => $this->whenLoaded('quiz.studyCard', function () {
                        return [
                            'id' => $this->quiz->studyCard->id,
                            'title' => $this->quiz->studyCard->title,
                            'description' => $this->quiz->studyCard->description,
                        ];
                    }),
                    'questions' => $this->whenLoaded('quiz.questions', function () {
                        return $this->quiz->questions->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'question_type' => $question->question_type,
                                'order_number' => $question->order_number,
                                'points' => $question->points,
                                'explanation' => $question->explanation,
                                'answers' => $question->answers->map(function ($answer) {
                                    return [
                                        'id' => $answer->id,
                                        'answer_text' => $answer->answer_text,
                                        'order_number' => $answer->order_number,
                                        // Don't show is_correct in active quiz
                                        'is_correct' => $this->status === 'completed' ? $answer->is_correct : null,
                                    ];
                                }),
                            ];
                        });
                    }),
                ];
            }),
            
            // Attempt statistics
            'total_questions' => $this->total_questions,
            'total_correct' => $this->total_correct,
            'total_incorrect' => $this->total_incorrect,
            'total_points_possible' => $this->total_points_possible,
            'total_points_earned' => $this->total_points_earned,
            'score' => $this->score,
            
            // Time tracking
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'time_spent_formatted' => $this->time_spent_seconds 
                ? $this->formatTimeSpent($this->time_spent_seconds) 
                : null,
            
            // Status
            'status' => $this->status,
            
            // User answers
            'answers' => $this->whenLoaded('answers', function () {
                return $this->answers->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'quiz_question_id' => $answer->quiz_question_id,
                        'selected_answer_id' => $answer->selected_answer_id,
                        'is_correct' => $answer->is_correct,
                        'points_earned' => $answer->points_earned,
                        'time_spent_seconds' => $answer->time_spent_seconds,
                        'answered_at' => $answer->answered_at?->toIso8601String(),
                        
                        // Question info
                        'question' => $this->whenLoaded('answers.question', function () use ($answer) {
                            return [
                                'id' => $answer->question->id,
                                'question_text' => $answer->question->question_text,
                                'explanation' => $answer->question->explanation,
                            ];
                        }),
                        
                        // Selected answer info
                        'selected_answer' => $this->whenLoaded('answers.selectedAnswer', function () use ($answer) {
                            return [
                                'id' => $answer->selectedAnswer->id,
                                'answer_text' => $answer->selectedAnswer->answer_text,
                                'is_correct' => $answer->selectedAnswer->is_correct,
                            ];
                        }),
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
    
    /**
     * Format time spent into human readable format
     */
    private function formatTimeSpent(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}