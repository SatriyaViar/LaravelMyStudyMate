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
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke quiz_questions
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');
            
            $table->text('answer_text');
            $table->boolean('is_correct')->default(false);
            
            $table->integer('order_number')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('quiz_question_id');
            $table->index(['quiz_question_id', 'is_correct']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};