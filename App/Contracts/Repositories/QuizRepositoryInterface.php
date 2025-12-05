<?php

namespace App\Contracts\Repositories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

interface QuizRepositoryInterface
{
    public function create(array $data): Quiz;
    
    public function update(int $id, array $data): Quiz;
    
    public function delete(int $id): bool;
    
    public function findById(int $id): ?Quiz;
    
    public function findByStudyCard(int $studyCardId): Collection;
}