<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'study_card_id',
        'title',
        'description',
        'total_questions',
        'duration_minutes',
        'generated_by_ai',
        'ai_model',
        'shuffle_questions',
        'shuffle_answers',
        'show_correct_answers',
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'shuffle_answers' => 'boolean',
        'show_correct_answers' => 'boolean',
        'generated_by_ai' => 'boolean',
    ];

    public function studyCard(): BelongsTo
    {
        return $this->belongsTo(StudyCard::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserQuizAttempt::class);
    }
}