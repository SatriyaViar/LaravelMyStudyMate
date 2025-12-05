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
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('is_done')->default(false)->after('is_completed');
            $table->timestamp('deadline')->nullable()->after('is_done');
            
            // Add index for deadline
            $table->index('deadline');
            $table->index(['user_id', 'is_done']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['schedules_user_id_is_done_index']);
            $table->dropIndex(['schedules_deadline_index']);
            $table->dropColumn(['is_done', 'deadline']);
        });
    }
};
