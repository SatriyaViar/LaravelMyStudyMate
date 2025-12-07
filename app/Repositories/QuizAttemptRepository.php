<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuizAttemptRepositoryInterface;
use App\Models\UserQuizAttempt;
use Illuminate\Pagination\LengthAwarePaginator;

class QuizAttemptRepository implements QuizAttemptRepositoryInterface
{
    protected UserQuizAttempt $model;

    public function __construct(UserQuizAttempt $model)
    {
        $this->model = $model;
    }

    public function findById(int $id): ?UserQuizAttempt
    {
        return $this->model->with(['quiz.questions.answers', 'answers'])->find($id);
    }

    public function create(array $data): UserQuizAttempt
    {
        return $this->model->create($data);
    }

    public function getUserAttempts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['quiz'])
            ->latest()
            ->paginate($perPage);
    }
}