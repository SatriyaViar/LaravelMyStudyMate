<?php

namespace App\Services;

use App\Contracts\Repositories\StudyCardRepositoryInterface;
use App\Contracts\Services\StudyCardServiceInterface;
use App\Models\StudyCard;
use Illuminate\Database\Eloquent\Collection;

class StudyCardService implements StudyCardServiceInterface
{
    protected StudyCardRepositoryInterface $repository;

    public function __construct(StudyCardRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createStudyCard(array $data): StudyCard
    {
        return $this->repository->create($data);
    }

    public function updateStudyCard(int $id, array $data): StudyCard
    {
        return $this->repository->update($id, $data);
    }

    public function deleteStudyCard(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getStudyCardById(int $id): ?StudyCard
    {
        return $this->repository->findById($id);
    }

    public function getUserStudyCards(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }
}