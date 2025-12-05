<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\UserQuizAttemptResource;
use App\Contracts\Services\UserQuizAttemptServiceInterface;
use App\Contracts\Services\QuizServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    protected UserQuizAttemptServiceInterface $attemptService;
    protected QuizServiceInterface $quizService;

    public function __construct(
        UserQuizAttemptServiceInterface $attemptService,
        QuizServiceInterface $quizService
    ) {
        $this->attemptService = $attemptService;
        $this->quizService = $quizService;
    }

    /**
     * Start a new quiz attempt
     * 
     * @group Quiz Attempts
     */
    public function start(Request $request, int $quizId): JsonResponse
    {
        try {
            $quiz = $this->quizService->getQuizById($quizId);

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found',
                ], 404);
            }

            // Check ownership
            if ($quiz->studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Start attempt
            $attempt = $this->attemptService->startQuizAttempt($request->user()->id, $quizId);

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt started successfully',
                'data' => new UserQuizAttemptResource($attempt),
            ], 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Submit answer for a question
     * 
     * @group Quiz Attempts
     */
    public function submitAnswer(SubmitAnswerRequest $request, int $attemptId): JsonResponse
    {
        try {
            $attempt = $this->attemptService->getAttemptById($attemptId);

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt not found',
                ], 404);
            }

            // Check ownership
            if ($attempt->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $result = $this->attemptService->submitAnswer($attemptId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Answer submitted successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Complete quiz attempt
     * 
     * @group Quiz Attempts
     */
    public function complete(Request $request, int $attemptId): JsonResponse
    {
        try {
            $attempt = $this->attemptService->getAttemptById($attemptId);

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt not found',
                ], 404);
            }

            // Check ownership
            if ($attempt->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $completedAttempt = $this->attemptService->completeAttempt($attemptId);

            return response()->json([
                'success' => true,
                'message' => 'Quiz completed successfully',
                'data' => new UserQuizAttemptResource($completedAttempt),
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get attempt detail
     * 
     * @group Quiz Attempts
     */
    public function show(Request $request, int $attemptId): JsonResponse
    {
        try {
            $attempt = $this->attemptService->getAttemptById($attemptId);

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt not found',
                ], 404);
            }

            // Check ownership
            if ($attempt->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attempt retrieved successfully',
                'data' => new UserQuizAttemptResource($attempt),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attempt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's quiz history
     * 
     * @group Quiz Attempts
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $attempts = $this->attemptService->getUserQuizHistory($request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Quiz history retrieved successfully',
                'data' => UserQuizAttemptResource::collection($attempts),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}