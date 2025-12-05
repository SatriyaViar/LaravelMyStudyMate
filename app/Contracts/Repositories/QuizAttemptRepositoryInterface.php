<?php

namespace App\Contracts\Repositories;

use App\Models\UserQuizAttempt;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuizAttemptRepositoryInterface
{
    public function findById(int $id): ?UserQuizAttempt;
    public function create(array $data): UserQuizAttempt;
    public function getUserAttempts(int $userId, int $perPage = 15): LengthAwarePaginator;
}