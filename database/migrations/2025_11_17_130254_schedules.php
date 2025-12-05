<?php
// database/migrations/2024_01_01_000001_create_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->string('color', 7)->nullable(); // Hex color
            $table->enum('type', ['lecture', 'lab', 'meeting', 'event', 'assignment', 'other'])->default('other');
            $table->boolean('has_reminder')->default(true);
            $table->integer('reminder_minutes')->default(30);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes(); // Optional: soft delete

            // Indexes for better performance
            $table->index('user_id');
            $table->index('date');
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};