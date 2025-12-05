<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     * GET /api/tasks
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id() ?? 1;
            
            $tasks = Task::forUser($userId)
                ->orderBy('deadline', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => TaskResource::collection($tasks),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created task.
     * POST /api/tasks
     */
    public function store(StoreTaskRequest $request)
    {
        try {
            $userId = Auth::id();

            $task = Task::create([
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'category' => $request->category,
                'priority' => $request->priority ?? 'medium',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => new TaskResource($task),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified task.
     * GET /api/tasks/{id}
     */
    public function show($id)
    {
        try {
            $task = Task::forUser(Auth::id() ?? 1)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => new TaskResource($task),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified task.
     * PUT /api/tasks/{id}
     */
    public function update(UpdateTaskRequest $request, $id)
    {
        try {
            $task = Task::forUser(Auth::id() ?? 1)->findOrFail($id);
            $task->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => new TaskResource($task),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified task.
     * DELETE /api/tasks/{id}
     */
    public function destroy($id)
    {
        try {
            $task = Task::forUser(Auth::id() ?? 1)->findOrFail($id);
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle task completion status.
     * PATCH /api/tasks/{id}/toggle-complete
     */
    public function toggleComplete(Request $request, $id)
    {
        try {
            $task = Task::forUser(Auth::id() ?? 1)->findOrFail($id);
            
            $task->is_completed = $request->input('is_completed', !$task->is_completed);
            $task->save();

            return response()->json([
                'success' => true,
                'message' => 'Task completion status updated',
                'data' => new TaskResource($task),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tasks by deadline range (for calendar integration).
     * GET /api/tasks/range?start_date=xxx&end_date=xxx
     */
    public function getByDeadlineRange(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $tasks = Task::forUser(Auth::id() ?? 1)
                ->withDeadline()
                ->betweenDeadlines($request->start_date, $request->end_date)
                ->orderBy('deadline')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => TaskResource::collection($tasks),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming tasks.
     * GET /api/tasks/upcoming?limit=10
     */
    public function getUpcoming(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $tasks = Task::forUser(Auth::id() ?? 1)
                ->withDeadline()
                ->where('deadline', '>=', now())
                ->incomplete()
                ->orderBy('deadline')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Upcoming tasks retrieved successfully',
                'data' => TaskResource::collection($tasks),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get task statistics.
     * GET /api/tasks/stats
     */
    public function getStats()
    {
        try {
            $userId = Auth::id() ?? 1;
            $today = Carbon::today();

            $stats = [
                'total' => Task::forUser($userId)->count(),
                'completed' => Task::forUser($userId)->completed()->count(),
                'incomplete' => Task::forUser($userId)->incomplete()->count(),
                'overdue' => Task::forUser($userId)
                    ->withDeadline()
                    ->where('deadline', '<', now())
                    ->incomplete()
                    ->count(),
                'today' => Task::forUser($userId)
                    ->withDeadline()
                    ->whereDate('deadline', $today)
                    ->count(),
                'this_week' => Task::forUser($userId)
                    ->withDeadline()
                    ->betweenDeadlines($today, $today->copy()->addDays(7))
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
}
