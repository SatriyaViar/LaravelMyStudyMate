<?php

namespace App\Contracts\Services;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

interface QuizServiceInterface
{
    public function getQuizById(int $id): ?Quiz;
    public function getQuizzesByStudyCard(int $studyCardId): Collection;
    
    // Method baru untuk generate quiz dari AI
    public function generateQuizFromAI(int $studyCardId, array $options = []): Quiz;
}