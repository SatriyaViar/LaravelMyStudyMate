<?php

namespace App\Contracts\Services;

use App\Models\UserQuizAttempt;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuizAttemptServiceInterface
{
    public function startQuizAttempt(int $quizId, int $userId): UserQuizAttempt;
    public function submitAnswer(int $attemptId, array $answerData): UserQuizAttempt;
    public function completeAttempt(int $attemptId, int $userId): UserQuizAttempt;
    public function getUserAttempts(int $userId, int $perPage = 15): LengthAwarePaginator;
}