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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke study_cards
            $table->foreignId('study_card_id')->constrained('study_cards')->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->integer('total_questions')->default(0);
            $table->integer('duration_minutes')->nullable();
            
            $table->boolean('generated_by_ai')->default(true);
            $table->string('ai_model')->nullable();
            
            // Quiz settings
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('study_card_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};