<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\Api\StudyCardController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// routes/api.php TEST
Route::get('/test', fn() => response()->json(['message' => 'Laravel reachable!']));

// Database connection test
Route::get('/test-db', function () {
    try {
        $response = [
            'status' => 'checking',
            'timestamp' => now()->toDateTimeString(),
            'tests' => []
        ];
        
        // Test 1: Check if DB connection works
        try {
            $pdo = DB::connection()->getPdo();
            $response['tests']['connection'] = [
                'status' => 'success',
                'message' => 'Database connection successful',
                'driver' => DB::connection()->getDriverName()
            ];
        } catch (Exception $e) {
            $response['tests']['connection'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            $response['status'] = 'failed';
            return response()->json($response);
        }
        
        // Test 2: Check tables exist
        try {
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
            $tableNames = array_map(fn($t) => $t->table_name, $tables);
            
            $response['tests']['tables'] = [
                'status' => 'success',
                'count' => count($tableNames),
                'tables' => $tableNames
            ];
        } catch (Exception $e) {
            $response['tests']['tables'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
        
        // Test 3: Check migrations table
        try {
            $migrations = DB::table('migrations')->count();
            $response['tests']['migrations'] = [
                'status' => 'success',
                'count' => $migrations
            ];
        } catch (Exception $e) {
            $response['tests']['migrations'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
        
        // Test 4: Check users table
        try {
            $users = DB::table('users')->count();
            $response['tests']['users'] = [
                'status' => 'success',
                'count' => $users
            ];
        } catch (Exception $e) {
            $response['tests']['users'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
        
        // Test 5: Check database info
        try {
            $dbConfig = [
                'connection' => config('database.default'),
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database')
            ];
            $response['tests']['config'] = [
                'status' => 'success',
                'info' => $dbConfig
            ];
        } catch (Exception $e) {
            $response['tests']['config'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
        
        $response['status'] = 'success';
        return response()->json($response);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
});

Route::get('/test-ai', function () {
    try {
        $aiService = app(\App\Services\AIService::class);
        $result = $aiService->generateQuiz('Laravel', 'easy', 2);
        
        return response()->json([
            'status' => 'success',
            'message' => 'AI Service working!',
            'test_result' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/current-user', [AuthController::class, 'getCurrentUser']);
Route::post('/save-fcm-token', [AuthController::class, 'saveFCMToken']);
Route::put('/update-profile', [AuthController::class, 'updateProfile']);
Route::post('/change-password', [AuthController::class, 'changePassword']);
Route::post('/upload-profile-photo', [AuthController::class, 'uploadProfilePhoto']);
Route::post('/record-streak', [AuthController::class, 'recordStreak']);
Route::get('/get-streak', [AuthController::class, 'getStreak']);

// 🔒 Assignment routes → prefix: /assignments (separate table)
Route::prefix('assignments')->group(function () {
    Route::get('/', [AssignmentController::class, 'index']); // GET /api/assignments?search=keyword&status=pending|done
    Route::post('/', [AssignmentController::class, 'store']); // POST /api/assignments
    Route::get('/weekly-progress', [AssignmentController::class, 'getWeeklyProgress']); // GET /api/assignments/weekly-progress
    Route::get('/by-status', [AssignmentController::class, 'getByStatus']); // GET /api/assignments/by-status
    Route::get('/{id}', [AssignmentController::class, 'show']); // GET /api/assignments/{id}
    Route::put('/{id}', [AssignmentController::class, 'update']); // PUT /api/assignments/{id}
    Route::patch('/{id}/mark-done', [AssignmentController::class, 'markAsDone']); // PATCH /api/assignments/{id}/mark-done
    Route::delete('/{id}', [AssignmentController::class, 'destroy']); // DELETE /api/assignments/{id}
});

// 🔒 Schedule routes → prefix: /schedules
Route::prefix('schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']); // GET /api/schedules
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/stats', [ScheduleController::class, 'getStats']);
    Route::get('/upcoming', [ScheduleController::class, 'getUpcoming']);
    Route::get('/date/{date}', [ScheduleController::class, 'getByDate']);
    Route::get('/range', [ScheduleController::class, 'getByDateRange']);
    Route::post('/check-conflict', [ScheduleController::class, 'checkConflict']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::patch('/{id}/toggle-complete', [ScheduleController::class, 'toggleComplete']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
});

// 🔒 Task routes → prefix: /tasks
Route::prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']); // GET /api/tasks
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/stats', [TaskController::class, 'getStats']);
    Route::get('/upcoming', [TaskController::class, 'getUpcoming']);
    Route::get('/range', [TaskController::class, 'getByDeadlineRange']);
    Route::get('/{id}', [TaskController::class, 'show']);
    Route::put('/{id}', [TaskController::class, 'update']);
    Route::patch('/{id}/toggle-complete', [TaskController::class, 'toggleComplete']);
    Route::delete('/{id}', [TaskController::class, 'destroy']);
});

// 🔒 Study Cards & Quiz routes → prefix: /study-cards
Route::middleware('auth:sanctum')->prefix('study-cards')->group(function () {
    Route::get('/', [StudyCardController::class, 'index']); // GET /api/study-cards
    Route::post('/', [StudyCardController::class, 'store']); // POST /api/study-cards
    Route::get('/{id}', [StudyCardController::class, 'show']); // GET /api/study-cards/{id}
    Route::put('/{id}', [StudyCardController::class, 'update']); // PUT /api/study-cards/{id}
    Route::post('/{id}/generate-quiz', [StudyCardController::class, 'generateQuiz']); // POST /api/study-cards/{id}/generate-quiz
    Route::delete('/{id}', [StudyCardController::class, 'destroy']); // DELETE /api/study-cards/{id}
});

// Quiz routes → prefix: /quizzes
Route::middleware('auth:sanctum')->prefix('quizzes')->group(function () {
    Route::get('/{id}', [StudyCardController::class, 'getQuiz']); // GET /api/quizzes/{id}
    Route::post('/{id}/submit', [StudyCardController::class, 'submitQuiz']); // POST /api/quizzes/{id}/submit
    Route::get('/{id}/attempts', [StudyCardController::class, 'getQuizAttempts']); // GET /api/quizzes/{id}/attempts
});
