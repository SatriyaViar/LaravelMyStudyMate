<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_quiz_attempt_id',
        'quiz_question_id',
        'selected_answer_id',
        'answer_text',
        'is_correct',
        'points_earned',
        'answered_at',
        'time_spent_seconds',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'answered_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function attempt()
    {
        return $this->belongsTo(UserQuizAttempt::class, 'user_quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function selectedAnswer()
    {
        return $this->belongsTo(QuizAnswer::class, 'selected_answer_id');
    }
}