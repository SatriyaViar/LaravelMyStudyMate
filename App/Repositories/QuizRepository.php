<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuizRepositoryInterface;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

class QuizRepository implements QuizRepositoryInterface
{
    public function create(array $data): Quiz
    {
        return Quiz::create($data);
    }

    public function update(int $id, array $data): Quiz
    {
        $quiz = $this->findById($id);
        
        if (!$quiz) {
            throw new \Exception('Quiz not found', 404);
        }
        
        $quiz->update($data);
        
        return $quiz->fresh();
    }

    public function delete(int $id): bool
    {
        $quiz = $this->findById($id);
        
        if (!$quiz) {
            throw new \Exception('Quiz not found', 404);
        }
        
        return $quiz->delete();
    }

    public function findById(int $id): ?Quiz
    {
        return Quiz::with(['studyCard', 'questions.answers'])->find($id);
    }

    public function findByStudyCard(int $studyCardId): Collection
    {
        return Quiz::with(['questions.answers'])
            ->where('study_card_id', $studyCardId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}