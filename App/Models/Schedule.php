<?php
// app/Models/Schedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'location',
        'lecturer',
        'color',
        'type',
        'has_reminder',
        'reminder_minutes',
        'is_completed',
        'notification_sent',
        'is_done',
        'deadline',
        'last_notification_type',
    ];

    protected $casts = [
        'date' => 'date',
        'has_reminder' => 'boolean',
        'is_completed' => 'boolean',
        'reminder_minutes' => 'integer',
        'notification_sent' => 'boolean',
        'is_done' => 'boolean',
        'deadline' => 'datetime',
    ];

    protected $appends = ['start_datetime', 'end_datetime', 'reminder_datetime'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getStartDatetimeAttribute()
    {
        return Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time);
    }

    public function getEndDatetimeAttribute()
    {
        return Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);
    }

    public function getReminderDatetimeAttribute()
    {
        return $this->start_datetime->subMinutes($this->reminder_minutes);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                     ->orderBy('date')
                     ->orderBy('start_time');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeAssignments($query)
    {
        return $query->where('type', 'assignment');
    }

    public function scopePendingAssignments($query)
    {
        return $query->where('type', 'assignment')
                     ->where('is_done', false);
    }

    public function scopeWeeklyAssignments($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        return $query->where('type', 'assignment')
                     ->whereBetween('deadline', [$startOfWeek, $endOfWeek]);
    }

    // Methods
    public function hasConflictWith($startTime, $endTime, $date)
    {
        $start = Carbon::parse($date . ' ' . $startTime);
        $end = Carbon::parse($date . ' ' . $endTime);

        return $start->lt($this->end_datetime) && $end->gt($this->start_datetime);
    }

    public static function checkConflict($userId, $date, $startTime, $endTime, $excludeId = null)
    {
        $query = self::forUser($userId)
            ->onDate($date)
            ->where('is_completed', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $schedules = $query->get();

        foreach ($schedules as $schedule) {
            if ($schedule->hasConflictWith($startTime, $endTime, $date)) {
                return true;
            }
        }

        return false;
    }
}