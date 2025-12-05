<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\QuizServiceInterface;
use App\Http\Resources\QuizResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    protected QuizServiceInterface $service;

    public function __construct(QuizServiceInterface $service)
    {
        $this->service = $service;
    }

    // List quiz dari study card tertentu
    public function index(Request $request): JsonResponse
    {
        $studyCardId = $request->get('study_card_id');
        if (!$studyCardId) {
            return response()->json(['success' => false, 'message' => 'study_card_id required'], 400);
        }
        
        $quizzes = $this->service->getQuizzesByStudyCard($studyCardId);
        return response()->json([
            'success' => true,
            'data'    => QuizResource::collection($quizzes),
        ]);
    }

    // Detail quiz
    public function show($id): JsonResponse
    {
        $quiz = $this->service->getQuizById($id);
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
        }
        return response()->json([
            'success' => true,
            'data'    => new QuizResource($quiz),
        ]);
    }

    // Generate quiz dari AI (endpoint utama)
    public function generateFromAI(Request $request, $studyCardId): JsonResponse
    {
        try {
            $options = [
                'num_questions'    => $request->input('num_questions', 5),
                'duration_minutes' => $request->input('duration_minutes', 30),
            ];

            $quiz = $this->service->generateQuizFromAI($studyCardId, $options);
            
            return response()->json([
                'success' => true,
                'message' => 'Quiz generated successfully',
                'data'    => new QuizResource($quiz),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}