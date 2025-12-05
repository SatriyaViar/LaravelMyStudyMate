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
            $table->string('color')->default('#5B9FED')->after('is_done');
            $table->boolean('has_reminder')->default(true)->after('color');
            $table->integer('reminder_minutes')->default(30)->after('has_reminder');
            $table->string('last_notification_type')->nullable()->after('reminder_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['color', 'has_reminder', 'reminder_minutes', 'last_notification_type']);
        });
    }
};
