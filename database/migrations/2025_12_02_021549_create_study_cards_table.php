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
        Schema::create('study_cards', function (Blueprint $table) {
            // ✅ Menggunakan id() auto-increment
            $table->id();
            
            // Foreign key ke users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            
            // Material type: 'text' or 'file'
            $table->enum('material_type', ['text', 'file'])->default('text');
            
            // Material content
            $table->longText('material_content')->nullable();
            
            // Material file
            $table->string('material_file_url')->nullable();
            $table->string('material_file_name')->nullable();
            $table->string('material_file_type')->nullable();
            $table->integer('material_file_size')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_cards');
    }
};