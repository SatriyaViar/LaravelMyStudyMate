<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_quiz_answers', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('user_quiz_attempt_id')
                  ->constrained('user_quiz_attempts')
                  ->onDelete('cascade');
            
            $table->foreignId('quiz_question_id')
                  ->constrained('quiz_questions')
                  ->onDelete('cascade');
            
            // Selected answer (for multiple choice / true-false)
            $table->foreignId('selected_answer_id')
                  ->nullable()
                  ->constrained('quiz_answers')
                  ->onDelete('set null');
            
            // Text answer (for essay type)
            $table->text('answer_text')->nullable();
            
            // Result
            $table->boolean('is_correct')->nullable();
            $table->integer('points_earned')->default(0);
            
            $table->timestamp('answered_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_quiz_attempt_id');
            $table->index('quiz_question_id');
            $table->index(['user_quiz_attempt_id', 'quiz_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quiz_answers');
    }
};