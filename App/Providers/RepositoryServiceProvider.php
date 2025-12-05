<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Contracts
use App\Contracts\Repositories\StudyCardRepositoryInterface;
use App\Contracts\Repositories\QuizRepositoryInterface;
use App\Contracts\Repositories\QuizAttemptRepositoryInterface;

// Repository Implementations
use App\Repositories\StudyCardRepository;
use App\Repositories\QuizRepository;
use App\Repositories\QuizAttemptRepository;

// Service Contracts
use App\Contracts\Services\StudyCardServiceInterface;
use App\Contracts\Services\QuizServiceInterface;
use App\Contracts\Services\QuizAttemptServiceInterface;

// Service Implementations
use App\Services\StudyCardService;
use App\Services\QuizService;
use App\Services\QuizAttemptService;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository Bindings
        $this->app->bind(StudyCardRepositoryInterface::class, StudyCardRepository::class);
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->bind(QuizAttemptRepositoryInterface::class, QuizAttemptRepository::class);

        // Service Bindings
        $this->app->bind(StudyCardServiceInterface::class, StudyCardService::class);
        $this->app->bind(QuizServiceInterface::class, QuizService::class);
        $this->app->bind(QuizAttemptServiceInterface::class, QuizAttemptService::class);
    }
}