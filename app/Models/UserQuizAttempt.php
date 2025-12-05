<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'started_at',
        'completed_at',
        'score',
        'total_correct',
        'total_incorrect',
        'total_questions',
        'total_points_earned',
        'total_points_possible',
        'status',
        'time_spent_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
        'total_correct' => 'integer',
        'total_incorrect' => 'integer',
        'total_questions' => 'integer',
        'total_points_earned' => 'integer',
        'total_points_possible' => 'integer',
        'time_spent_seconds' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(UserQuizAnswer::class);
    }

    // Status checks
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isAbandoned()
    {
        return $this->status === 'abandoned';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    // Get time spent in human readable format
    public function getTimeSpentFormattedAttribute()
    {
        if (!$this->time_spent_seconds) return '0s';
        
        $seconds = $this->time_spent_seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $minutes . 'm ' . $secs . 's';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }

    // Calculate percentage
    public function getPercentageAttribute()
    {
        if ($this->total_questions === 0) return 0;
        
        return round(($this->total_correct / $this->total_questions) * 100, 2);
    }

    // Get grade letter
    public function getGradeAttribute()
    {
        $score = $this->score ?? $this->percentage;
        
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    // Check if passed (score >= 60)
    public function isPassed()
    {
        $score = $this->score ?? $this->percentage;
        return $score >= 60;
    }
}