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
        Schema::table('assignments', function (Blueprint $table) {
            // Add indexes on frequently queried columns
            $table->index('user_id');
            $table->index('deadline');
            $table->index('is_done');
            $table->index(['user_id', 'deadline']); // Composite index
            $table->index(['user_id', 'is_done']); // Composite index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['user_id']);
            $table->dropIndex(['deadline']);
            $table->dropIndex(['is_done']);
            $table->dropIndex(['user_id', 'deadline']);
            $table->dropIndex(['user_id', 'is_done']);
        });
    }
};
