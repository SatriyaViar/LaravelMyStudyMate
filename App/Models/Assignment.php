<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'deadline',
        'is_done',
        'color',
        'has_reminder',
        'reminder_minutes',
        'last_notification_type',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'is_done' => 'boolean',
        'has_reminder' => 'boolean',
        'reminder_minutes' => 'integer',
    ];

    // Only append these attributes when explicitly requested
    protected $appends = [];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('is_done', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_done', true);
    }

    public function scopeWeekly($query)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        return $query->whereBetween('deadline', [$startOfWeek, $endOfWeek]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_done', false)
                     ->where('deadline', '<', Carbon::now());
    }

    public function scopeDueToday($query)
    {
        return $query->where('is_done', false)
                     ->whereDate('deadline', Carbon::today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_done', false)
                     ->where('deadline', '>', Carbon::now())
                     ->orderBy('deadline', 'asc');
    }

    // Accessors - These are NOT automatically appended to JSON to improve performance
    // Frontend will calculate these values instead
    public function getIsOverdueAttribute()
    {
        return !$this->is_done && $this->deadline->isPast();
    }

    public function getIsDueTodayAttribute()
    {
        return !$this->is_done && $this->deadline->isToday();
    }
}
