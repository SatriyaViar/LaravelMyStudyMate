<?php

namespace App\Contracts\Repositories;

use App\Models\UserQuizAttempt;
use Illuminate\Database\Eloquent\Collection;

interface UserQuizAttemptRepositoryInterface
{
    public function create(array $data): UserQuizAttempt;
    
    public function update(int $id, array $data): UserQuizAttempt;
    
    public function findById(int $id): ?UserQuizAttempt;
    
    public function findByUser(int $userId): Collection;
    
    public function findOngoingAttempt(int $userId, int $quizId): ?UserQuizAttempt;
}