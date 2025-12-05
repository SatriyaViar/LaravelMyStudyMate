<?php

namespace App\Services;

use App\Contracts\Repositories\UserQuizAttemptRepositoryInterface;
use App\Contracts\Services\UserQuizAttemptServiceInterface;
use App\Models\UserQuizAttempt;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Collection;

class UserQuizAttemptService implements UserQuizAttemptServiceInterface
{
    protected UserQuizAttemptRepositoryInterface $repository;

    public function __construct(UserQuizAttemptRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function startQuizAttempt(int $userId, int $quizId): UserQuizAttempt
    {
        // Check if there's an ongoing attempt
        $ongoingAttempt = $this->repository->findOngoingAttempt($userId, $quizId);
        
        if ($ongoingAttempt) {
            throw new \Exception('You already have an ongoing attempt for this quiz', 400);
        }

        // Get quiz data
        $quiz = Quiz::with('questions')->findOrFail($quizId);
        $totalPointsPossible = $quiz->questions->sum('points');

        // Create new attempt
        return $this->repository->create([
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'started_at' => now(),
            'total_questions' => $quiz->total_questions,
            'total_points_possible' => $totalPointsPossible,
            'status' => 'in_progress',
        ]);
    }

    public function submitAnswer(int $attemptId, array $data): array
    {
        $attempt = $this->repository->findById($attemptId);

        if (!$attempt) {
            throw new \Exception('Attempt not found', 404);
        }

        if ($attempt->status !== 'in_progress') {
            throw new \Exception('This quiz attempt is no longer active', 400);
        }

        $question = QuizQuestion::with('answers')->findOrFail($data['quiz_question_id']);

        // Check if question belongs to this quiz
        if ($question->quiz_id !== $attempt->quiz_id) {
            throw new \Exception('Question does not belong to this quiz', 400);
        }

        // Check if answer already exists
        $existingAnswer = $attempt->answers()
            ->where('quiz_question_id', $data['quiz_question_id'])
            ->first();

        if ($existingAnswer) {
            throw new \Exception('You have already answered this question', 400);
        }

        // Get correct answer
        $correctAnswer = $question->answers()->where('is_correct', true)->first();
        $isCorrect = $correctAnswer && $correctAnswer->id == $data['selected_answer_id'];
        $pointsEarned = $isCorrect ? $question->points : 0;

        // Save user answer
        $attempt->answers()->create([
            'quiz_question_id' => $data['quiz_question_id'],
            'selected_answer_id' => $data['selected_answer_id'],
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'answered_at' => now(),
            'time_spent_seconds' => $data['time_spent_seconds'] ?? 0,
        ]);

        // Update attempt statistics
        $attempt->increment('total_correct', $isCorrect ? 1 : 0);
        $attempt->increment('total_incorrect', $isCorrect ? 0 : 1);
        $attempt->increment('total_points_earned', $pointsEarned);

        return [
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'correct_answer_id' => $correctAnswer?->id,
            'explanation' => $question->explanation,
        ];
    }

    public function completeAttempt(int $attemptId): UserQuizAttempt
    {
        $attempt = $this->repository->findById($attemptId);

        if (!$attempt) {
            throw new \Exception('Attempt not found', 404);
        }

        if ($attempt->status !== 'in_progress') {
            throw new \Exception('This quiz attempt is already completed', 400);
        }

        // Calculate final score
        $score = $attempt->total_points_possible > 0
            ? ($attempt->total_points_earned / $attempt->total_points_possible) * 100
            : 0;

        // Calculate time spent
        $timeSpent = now()->diffInSeconds($attempt->started_at);

        // Update attempt
        return $this->repository->update($attemptId, [
            'completed_at' => now(),
            'score' => round($score, 2),
            'time_spent_seconds' => $timeSpent,
            'status' => 'completed',
        ]);
    }

    public function getAttemptById(int $attemptId): ?UserQuizAttempt
    {
        return $this->repository->findById($attemptId);
    }

    public function getUserQuizHistory(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }

    public function getOngoingAttempt(int $userId, int $quizId): ?UserQuizAttempt
    {
        return $this->repository->findOngoingAttempt($userId, $quizId);
    }
}