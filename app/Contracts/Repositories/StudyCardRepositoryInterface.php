<?php

namespace App\Contracts\Repositories;

use App\Models\StudyCard;
use Illuminate\Database\Eloquent\Collection;

interface StudyCardRepositoryInterface
{
    public function create(array $data): StudyCard;
    
    public function update(int $id, array $data): StudyCard;
    
    public function delete(int $id): bool;
    
    public function findById(int $id): ?StudyCard;
    
    public function findByUser(int $userId): Collection;
}