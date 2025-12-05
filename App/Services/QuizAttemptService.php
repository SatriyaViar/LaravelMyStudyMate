<?php

namespace App\Services;

use App\Contracts\Repositories\QuizAttemptRepositoryInterface;
use App\Contracts\Services\QuizAttemptServiceInterface;
use App\Models\UserQuizAttempt;
use Illuminate\Pagination\LengthAwarePaginator;

class QuizAttemptService implements QuizAttemptServiceInterface
{
    protected QuizAttemptRepositoryInterface $repository;

    public function __construct(QuizAttemptRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function startQuizAttempt(int $quizId, int $userId): UserQuizAttempt
    {
        // Bisa tambah logic jika butuh
        return $this->repository->create([
            'quiz_id' => $quizId,
            'user_id' => $userId,
            'status' => 'started',
        ]);
    }

    public function submitAnswer(int $attemptId, array $answerData): UserQuizAttempt
    {
        // Ditambah logic penyimpanan answer di repo (butuh method baru kalau ingin detil)
        return $this->repository->findById($attemptId);
    }

    public function completeAttempt(int $attemptId, int $userId): UserQuizAttempt
    {
        // Update status tekan tombol selesai
        $attempt = $this->repository->findById($attemptId);
        if ($attempt && $attempt->user_id === $userId) {
            // Bisa update skor, dsb.
            return $this->repository->findById($attemptId);
        }
        throw new \Exception('Unauthorized', 403);
    }

    public function getUserAttempts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getUserAttempts($userId, $perPage);
    }
}