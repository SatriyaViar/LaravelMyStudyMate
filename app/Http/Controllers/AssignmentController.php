<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Get authenticated user ID with fallback
     */
    private function getUserId(Request $request)
    {
        if (Auth::id()) {
            return Auth::id();
        }
        
        if ($request->header('X-User-Id')) {
            return (int) $request->header('X-User-Id');
        }
        
        if ($request->query('user_id')) {
            return (int) $request->query('user_id');
        }
        
        return 1;
    }

    /**
     * Clear user's assignments cache
     */
    private function clearAssignmentsCache($userId)
    {
        Cache::forget("assignments_pending_user_{$userId}");
        Cache::forget("assignments_all_user_{$userId}");
    }

    /**
     * Display a listing of assignments
     * GET /api/assignments
     */
    public function index(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            $status = $request->get('status', 'all');
            $search = $request->get('search', '');

            // Create cache key based on user, status, and search
            $cacheKey = "assignments_{$status}_user_{$userId}";
            if (!empty($search)) {
                $cacheKey .= "_search_" . md5($search);
            }

            // Cache for 30 seconds (adjust as needed)
            $assignments = Cache::remember($cacheKey, 30, function () use ($userId, $status, $request, $search) {
                $query = Assignment::forUser($userId);

                // Filter by status
                if ($status === 'pending') {
                    $query->pending();
                } elseif ($status === 'done') {
                    $query->completed();
                }

                // Search by title or description
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('title', 'ILIKE', "%{$search}%")
                          ->orWhere('description', 'ILIKE', "%{$search}%");
                    });
                }

                // Select only needed columns for better performance
                return $query->select([
                    'id', 'user_id', 'title', 'description', 'deadline', 
                    'is_done', 'color', 'has_reminder', 'reminder_minutes',
                    'last_notification_type', 'created_at', 'updated_at'
                ])->orderBy('deadline', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Assignments retrieved successfully',
                'data' => $assignments,
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
     * Store a newly created assignment
     * POST /api/assignments
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'required|date',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'has_reminder' => 'nullable|boolean',
                'reminder_minutes' => 'nullable|integer|min:1|max:1440',
            ]);

            $userId = $this->getUserId($request);

            $assignment = Assignment::create([
                'user_id' => $userId,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'deadline' => Carbon::parse($validated['deadline'])->endOfDay(),
                'color' => $validated['color'] ?? '#5B9FED',
                'has_reminder' => $validated['has_reminder'] ?? true,
                'reminder_minutes' => $validated['reminder_minutes'] ?? 30,
                'is_done' => false,
            ]);

            // Clear cache after creating new assignment
            $this->clearAssignmentsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully',
                'data' => $assignment,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified assignment
     * GET /api/assignments/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $assignment = Assignment::forUser($this->getUserId($request))->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Assignment retrieved successfully',
                'data' => $assignment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified assignment
     * PUT/PATCH /api/assignments/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $assignment = Assignment::forUser($userId)->findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'sometimes|required|date',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'has_reminder' => 'nullable|boolean',
                'reminder_minutes' => 'nullable|integer|min:1|max:1440',
                'is_done' => 'nullable|boolean',
            ]);

            if (isset($validated['deadline'])) {
                $validated['deadline'] = Carbon::parse($validated['deadline'])->endOfDay();
            }

            $assignment->update($validated);

            // Clear cache after updating
            $this->clearAssignmentsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => $assignment->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark assignment as done
     * PATCH /api/assignments/{id}/mark-done
     */
    public function markAsDone(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $assignment = Assignment::forUser($userId)->findOrFail($id);
            
            $assignment->update(['is_done' => true]);

            // Clear cache after marking as done
            $this->clearAssignmentsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Assignment marked as done',
                'data' => $assignment->fresh(),
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
     * Delete the specified assignment
     * DELETE /api/assignments/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);
            $assignment = Assignment::forUser($userId)->findOrFail($id);
            $assignment->delete();

            // Clear cache after deleting
            $this->clearAssignmentsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weekly progress
     * GET /api/assignments/weekly-progress
     */
    public function getWeeklyProgress(Request $request)
    {
        try {
            $userId = $this->getUserId($request);
            
            // Cache weekly progress for 60 seconds
            $data = Cache::remember("weekly_progress_user_{$userId}", 60, function () use ($userId) {
                $total = Assignment::forUser($userId)->weekly()->count();
                $completed = Assignment::forUser($userId)->weekly()->completed()->count();
                $pending = $total - $completed;
                $percentage = $total > 0 ? ($completed / $total) * 100 : 0;

                return [
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'percentage' => round($percentage, 2),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Weekly progress retrieved successfully',
                'data' => $data,
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
     * Get assignments by status (overdue, due today, upcoming)
     * GET /api/assignments/by-status
     */
    public function getByStatus(Request $request)
    {
        try {
            $userId = $this->getUserId($request);

            // Cache for 30 seconds
            $data = Cache::remember("assignments_by_status_user_{$userId}", 30, function () use ($userId) {
                return [
                    'overdue' => Assignment::forUser($userId)->overdue()->get(),
                    'due_today' => Assignment::forUser($userId)->dueToday()->get(),
                    'upcoming' => Assignment::forUser($userId)->upcoming()->get(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Assignments by status retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments by status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
