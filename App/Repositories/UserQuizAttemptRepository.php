<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserQuizAttemptRepositoryInterface;
use App\Models\UserQuizAttempt;
use Illuminate\Database\Eloquent\Collection;

class UserQuizAttemptRepository implements UserQuizAttemptRepositoryInterface
{
    public function create(array $data): UserQuizAttempt
    {
        return UserQuizAttempt::create($data);
    }

    public function update(int $id, array $data): UserQuizAttempt
    {
        $attempt = $this->findById($id);
        $attempt->update($data);
        return $attempt->fresh();
    }

    public function findById(int $id): ?UserQuizAttempt
    {
        return UserQuizAttempt::with([
            'quiz.questions.answers',
            'quiz.studyCard',
            'answers.question',
            'answers.selectedAnswer'
        ])->find($id);
    }

    public function findByUser(int $userId): Collection
    {
        return UserQuizAttempt::with(['quiz.studyCard'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findOngoingAttempt(int $userId, int $quizId): ?UserQuizAttempt
    {
        return UserQuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'in_progress')
            ->first();
    }
}