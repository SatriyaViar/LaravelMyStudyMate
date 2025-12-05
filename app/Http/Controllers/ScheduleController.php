<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Clear user's schedules cache
     */
    private function clearSchedulesCache($userId)
    {
        Cache::forget("schedules_all_user_{$userId}");
        Cache::forget("schedules_upcoming_user_{$userId}");
        // Clear date range caches (wildcard not supported, so we clear key patterns)
        Cache::flush(); // Or implement more granular cache tags
    }

    /**
     * Get authenticated user ID with fallback for testing
     */
    private function getUserId(Request $request)
    {
        // Priority: Auth ID > Header > Query Param > Default
        if (Auth::id()) {
            return Auth::id();
        }
        
        // For testing: allow user_id in header or query
        if ($request->header('X-User-Id')) {
            return (int) $request->header('X-User-Id');
        }
        
        if ($request->query('user_id')) {
            return (int) $request->query('user_id');
        }
        
        // Default fallback
        return 1;
    }

    /**
     * Display a listing of schedules.
     * GET /api/schedules
     */
    public function index(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            
            // Cache for 30 seconds
            $schedules = Cache::remember("schedules_all_user_{$userId}", 30, function () use ($userId) {
                return Schedule::forUser($userId)
                    ->orderBy('date', 'desc')
                    ->orderBy('start_time', 'asc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Schedules retrieved successfully',
                'data' => ScheduleResource::collection($schedules),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created schedule.
     * POST /api/schedules
     */
    public function store(StoreScheduleRequest $request)
    {
        try {
            $userId = $this->getUserId($request);

            // Check for schedule conflicts (skip for assignment type)
            if ($request->type !== 'assignment') {
                $hasConflict = Schedule::checkConflict(
                    $userId,
                    $request->date,
                    $request->start_time,
                    $request->end_time
                );

                if ($hasConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule conflict detected. You already have a schedule at this time.',
                        'has_conflict' => true,
                    ], 422);
                }
            }

            // Prepare data
            $data = [
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date,
                'location' => $request->location,
                'lecturer' => $request->lecturer,
                'color' => $request->color ?? '#5B9FED',
                'type' => $request->type,
                'has_reminder' => $request->has_reminder ?? true,
                'reminder_minutes' => $request->reminder_minutes ?? 30,
            ];

            // Handle time fields based on type
            if ($request->type === 'assignment') {
                // For assignment, use default times or provided ones
                $data['start_time'] = $request->start_time ?? '00:00';
                $data['end_time'] = $request->end_time ?? '23:59';
                $data['is_done'] = false;
                // Set deadline to date + end of day (23:59:59)
                $data['deadline'] = Carbon::parse($request->date)->endOfDay();
            } else {
                // For other types, times are required
                $data['start_time'] = $request->start_time;
                $data['end_time'] = $request->end_time;
            }

            // Create schedule
            $schedule = Schedule::create($data);

            // Clear cache after creating
            $this->clearSchedulesCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'data' => new ScheduleResource($schedule),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified schedule.
     * GET /api/schedules/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $schedule = Schedule::forUser($this->getUserId($request))->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Schedule retrieved successfully',
                'data' => new ScheduleResource($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified schedule.
     * PUT /api/schedules/{id}
     */
    public function update(UpdateScheduleRequest $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $schedule = Schedule::forUser($userId)->findOrFail($id);

            // Check for conflicts (excluding current schedule, skip for assignment)
            if ($schedule->type !== 'assignment' && ($request->has('date') || $request->has('start_time') || $request->has('end_time'))) {
                $date = $request->date ?? $schedule->date->format('Y-m-d');
                $startTime = $request->start_time ?? date('H:i', strtotime($schedule->start_time));
                $endTime = $request->end_time ?? date('H:i', strtotime($schedule->end_time));

                $hasConflict = Schedule::checkConflict(
                    $userId,
                    $date,
                    $startTime,
                    $endTime,
                    $id
                );

                if ($hasConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule conflict detected',
                        'has_conflict' => true,
                    ], 422);
                }
            }

            // Get validated data
            $data = $request->validated();
            
            // Reset notification_sent if time/date/reminder changed
            $shouldResetNotification = 
                ($request->has('date') && $request->date != $schedule->date->format('Y-m-d')) ||
                ($request->has('start_time') && $request->start_time != date('H:i', strtotime($schedule->start_time))) ||
                ($request->has('reminder_minutes') && $request->reminder_minutes != $schedule->reminder_minutes);
            
            if ($shouldResetNotification) {
                $data['notification_sent'] = false;
            }

            // Update deadline for assignment if date changed
            if ($schedule->type === 'assignment' && $request->has('date')) {
                $data['deadline'] = Carbon::parse($request->date)->endOfDay();
            }

            $schedule->update($data);

            // Clear cache after updating
            $this->clearSchedulesCache($this->getUserId($request));

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'data' => new ScheduleResource($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified schedule.
     * DELETE /api/schedules/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $schedule = Schedule::forUser($userId)->findOrFail($id);
            $schedule->delete();

            // Clear cache after deleting
            $this->clearSchedulesCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get schedules by specific date.
     * GET /api/schedules/date/{date}
     */
    public function getByDate(Request $request, $date)
    {
        try {
            $schedules = Schedule::forUser($this->getUserId($request))
                ->onDate($date)
                ->orderBy('start_time')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Schedules retrieved successfully',
                'data' => ScheduleResource::collection($schedules),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get schedules by date range.
     * GET /api/schedules/range?start_date=xxx&end_date=xxx
     */
    public function getByDateRange(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $userId = $this->getUserId($request);
            $cacheKey = "schedules_range_user_{$userId}_" . md5($request->start_date . $request->end_date);
            
            // Cache for 30 seconds
            $schedules = Cache::remember($cacheKey, 30, function () use ($userId, $request) {
                return Schedule::forUser($userId)
                    ->betweenDates($request->start_date, $request->end_date)
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Schedules retrieved successfully',
                'data' => ScheduleResource::collection($schedules),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming schedules.
     * GET /api/schedules/upcoming?limit=5
     */
    public function getUpcoming(Request $request)
    {
        try {
            $limit = $request->input('limit', 5);
            $userId = $this->getUserId($request);
            $cacheKey = "schedules_upcoming_user_{$userId}_limit_{$limit}";

            // Cache for 30 seconds
            $schedules = Cache::remember($cacheKey, 30, function () use ($userId, $limit) {
                return Schedule::forUser($userId)
                    ->upcoming()
                    ->incomplete()
                    ->limit($limit)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Upcoming schedules retrieved successfully',
                'data' => ScheduleResource::collection($schedules),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming schedules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle schedule completion status.
     * PATCH /api/schedules/{id}/toggle-complete
     */
    public function toggleComplete(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $schedule = Schedule::forUser($userId)->findOrFail($id);
            
            $schedule->is_completed = $request->input('is_completed', !$schedule->is_completed);
            $schedule->save();

            // Clear cache after status change
            $this->clearSchedulesCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Schedule completion status updated',
                'data' => new ScheduleResource($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check schedule conflict.
     * POST /api/schedules/check-conflict
     */
    public function checkConflict(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'exclude_id' => 'nullable|exists:schedules,id',
            ]);

            $hasConflict = Schedule::checkConflict(
                $this->getUserId($request),
                $request->date,
                $request->start_time,
                $request->end_time,
                $request->exclude_id
            );

            return response()->json([
                'success' => true,
                'has_conflict' => $hasConflict,
                'message' => $hasConflict ? 'Schedule conflict detected' : 'No conflict',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check conflict',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get schedule statistics.
     * GET /api/schedules/stats
     */
    public function getStats(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            $today = Carbon::today();

            $stats = [
                'total' => Schedule::forUser($userId)->count(),
                'completed' => Schedule::forUser($userId)->completed()->count(),
                'upcoming' => Schedule::forUser($userId)->upcoming()->incomplete()->count(),
                'today' => Schedule::forUser($userId)->onDate($today)->count(),
                'this_week' => Schedule::forUser($userId)
                    ->betweenDates($today, $today->copy()->addDays(7))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all assignments with optional search.
     * GET /api/schedules/assignments?search=keyword
     */
    public function getAssignments(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            $query = Schedule::forUser($userId)->assignments();

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'pending') {
                    $query->where('is_done', false);
                } elseif ($request->status === 'done') {
                    $query->where('is_done', true);
                }
            }

            $assignments = $query->orderBy('deadline', 'asc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Assignments retrieved successfully',
                'data' => ScheduleResource::collection($assignments),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark assignment as done.
     * PATCH /api/schedules/{id}/mark-done
     */
    public function markAsDone(Request $request, $id)
    {
        try {
            $schedule = Schedule::forUser($this->getUserId($request))->findOrFail($id);

            if ($schedule->type !== 'assignment') {
                return response()->json([
                    'success' => false,
                    'message' => 'This schedule is not an assignment',
                ], 400);
            }

            $schedule->is_done = true;
            $schedule->is_completed = true;
            $schedule->save();

            return response()->json([
                'success' => true,
                'message' => 'Assignment marked as done',
                'data' => new ScheduleResource($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark assignment as done',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weekly assignment progress.
     * GET /api/schedules/assignments/weekly-progress
     */
    public function getWeeklyProgress(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            $totalAssignments = Schedule::forUser($userId)
                ->assignments()
                ->whereBetween('deadline', [$startOfWeek, $endOfWeek])
                ->count();

            $completedAssignments = Schedule::forUser($userId)
                ->assignments()
                ->whereBetween('deadline', [$startOfWeek, $endOfWeek])
                ->where('is_done', true)
                ->count();

            $percentage = $totalAssignments > 0 
                ? round(($completedAssignments / $totalAssignments) * 100, 2) 
                : 0;

            return response()->json([
                'success' => true,
                'message' => 'Weekly progress retrieved successfully',
                'data' => [
                    'total' => $totalAssignments,
                    'completed' => $completedAssignments,
                    'pending' => $totalAssignments - $completedAssignments,
                    'percentage' => $percentage,
                    'week_start' => $startOfWeek->toDateString(),
                    'week_end' => $endOfWeek->toDateString(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve weekly progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assignments by deadline status (overdue, due today, upcoming).
     * GET /api/schedules/assignments/by-status
     */
    public function getAssignmentsByStatus(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            $now = Carbon::now();

            $overdue = Schedule::forUser($userId)
                ->assignments()
                ->where('is_done', false)
                ->where('deadline', '<', $now)
                ->orderBy('deadline', 'asc')
                ->get();

            $dueToday = Schedule::forUser($userId)
                ->assignments()
                ->where('is_done', false)
                ->whereDate('deadline', $now->toDateString())
                ->orderBy('deadline', 'asc')
                ->get();

            $upcoming = Schedule::forUser($userId)
                ->assignments()
                ->where('is_done', false)
                ->where('deadline', '>', $now->endOfDay())
                ->orderBy('deadline', 'asc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Assignments retrieved successfully',
                'data' => [
                    'overdue' => ScheduleResource::collection($overdue),
                    'due_today' => ScheduleResource::collection($dueToday),
                    'upcoming' => ScheduleResource::collection($upcoming),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}