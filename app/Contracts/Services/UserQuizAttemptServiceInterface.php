<?php

namespace App\Contracts\Services;

use App\Models\UserQuizAttempt;
use Illuminate\Database\Eloquent\Collection;

interface UserQuizAttemptServiceInterface
{
    public function startQuizAttempt(int $userId, int $quizId): UserQuizAttempt;
    
    public function submitAnswer(int $attemptId, array $data): array;
    
    public function completeAttempt(int $attemptId): UserQuizAttempt;
    
    public function getAttemptById(int $attemptId): ?UserQuizAttempt;
    
    public function getUserQuizHistory(int $userId): Collection;
    
    public function getOngoingAttempt(int $userId, int $quizId): ?UserQuizAttempt;
}