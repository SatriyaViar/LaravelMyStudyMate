<?php

namespace App\Contracts\Services;

use App\Models\StudyCard;
use Illuminate\Database\Eloquent\Collection;

interface StudyCardServiceInterface
{
    public function createStudyCard(array $data): StudyCard;
    
    public function updateStudyCard(int $id, array $data): StudyCard;
    
    public function deleteStudyCard(int $id): bool;
    
    public function getStudyCardById(int $id): ?StudyCard;
    
    public function getUserStudyCards(int $userId): Collection;
}