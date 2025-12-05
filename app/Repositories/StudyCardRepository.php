<?php

namespace App\Repositories;

use App\Contracts\Repositories\StudyCardRepositoryInterface;
use App\Models\StudyCard;
use Illuminate\Database\Eloquent\Collection;

class StudyCardRepository implements StudyCardRepositoryInterface
{
    public function create(array $data): StudyCard
    {
        return StudyCard::create($data);
    }

    public function update(int $id, array $data): StudyCard
    {
        $studyCard = $this->findById($id);
        
        if (!$studyCard) {
            throw new \Exception('Study Card not found', 404);
        }
        
        $studyCard->update($data);
        
        return $studyCard->fresh();
    }

    public function delete(int $id): bool
    {
        $studyCard = $this->findById($id);
        
        if (!$studyCard) {
            throw new \Exception('Study Card not found', 404);
        }
        
        return $studyCard->delete();
    }

    public function findById(int $id): ?StudyCard
    {
        return StudyCard::find($id);
    }

    public function findByUser(int $userId): Collection
    {
        return StudyCard::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}