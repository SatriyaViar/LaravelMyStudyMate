<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Study Card
        $this->app->bind(
            \App\Contracts\Repositories\StudyCardRepositoryInterface::class,
            \App\Repositories\StudyCardRepository::class
        );

        $this->app->bind(
            \App\Contracts\Services\StudyCardServiceInterface::class,
            \App\Services\StudyCardService::class
        );

        // Quiz
        $this->app->bind(
            \App\Contracts\Repositories\QuizRepositoryInterface::class,
            \App\Repositories\QuizRepository::class
        );

        $this->app->bind(
            \App\Contracts\Services\QuizServiceInterface::class,
            \App\Services\QuizService::class
        );

        // ⭐ User Quiz Attempt (BARU)
        $this->app->bind(
            \App\Contracts\Repositories\UserQuizAttemptRepositoryInterface::class,
            \App\Repositories\UserQuizAttemptRepository::class
        );

        $this->app->bind(
            \App\Contracts\Services\UserQuizAttemptServiceInterface::class,
            \App\Services\UserQuizAttemptService::class
        );
    }

    public function boot(): void
    {
        //
    }
}