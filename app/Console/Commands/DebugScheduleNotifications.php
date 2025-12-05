<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebugScheduleNotifications extends Command
{
    protected $signature = 'schedule:debug';
    protected $description = 'Debug schedule notifications system';

    public function handle()
    {
        $this->info('🔍 DEBUGGING SCHEDULE NOTIFICATIONS SYSTEM');
        $this->info('==========================================');
        $this->newLine();

        // 1. Check Current Time
        $now = Carbon::now();
        $this->info("⏰ Current Time: {$now->format('Y-m-d H:i:s')}");
        $this->info("🌍 Timezone: " . config('app.timezone'));
        $this->newLine();

        // 2. Check Database Columns
        $this->info('📊 CHECKING DATABASE COLUMNS:');
        $columns = DB::select("SELECT column_name, data_type, is_nullable, column_default 
                               FROM information_schema.columns 
                               WHERE table_name = 'schedules' 
                               AND column_name IN ('has_reminder', 'reminder_minutes', 'notification_sent')
                               ORDER BY ordinal_position");
        
        if (empty($columns)) {
            $this->error('❌ Required columns not found in schedules table!');
            $this->warn('Run: php artisan migrate');
            return Command::FAILURE;
        }

        foreach ($columns as $col) {
            $this->line("  ✓ {$col->column_name} ({$col->data_type}) - Default: {$col->column_default}");
        }
        $this->newLine();

        // 3. Check Users with FCM Tokens
        $this->info('👥 USERS WITH FCM TOKENS:');
        $usersWithTokens = DB::table('users')
            ->whereNotNull('fcm_token')
            ->select('id', 'name', 'email')
            ->get();

        if ($usersWithTokens->isEmpty()) {
            $this->warn('⚠️ No users have FCM tokens! Users need to login first.');
        } else {
            foreach ($usersWithTokens as $user) {
                $this->line("  ✓ ID: {$user->id}, Name: {$user->name}");
            }
        }
        $this->newLine();

        // 4. Check Schedules Status
        $this->info('📅 SCHEDULES ANALYSIS:');
        
        $totalSchedules = Schedule::count();
        $withReminder = Schedule::where('has_reminder', true)->count();
        $notificationSent = Schedule::where('notification_sent', true)->count();
        $pendingNotification = Schedule::where('has_reminder', true)
            ->where('notification_sent', false)
            ->count();

        $this->line("  Total Schedules: {$totalSchedules}");
        $this->line("  With Reminder Enabled: {$withReminder}");
        $this->line("  Notification Already Sent: {$notificationSent}");
        $this->line("  Pending Notification: {$pendingNotification}");
        $this->newLine();

        // 5. Check Schedules That Should Send Notification NOW
        $this->info('🔔 SCHEDULES THAT SHOULD NOTIFY NOW:');
        
        $eligibleSchedules = Schedule::where('has_reminder', true)
            ->where('notification_sent', false)
            ->where('date', '>=', $now->toDateString())
            ->get()
            ->filter(function ($schedule) use ($now) {
                $dateString = Carbon::parse($schedule->date)->format('Y-m-d');
                $timeString = Carbon::parse($schedule->start_time)->format('H:i:s');
                $scheduleDateTime = Carbon::parse($dateString . ' ' . $timeString);
                $reminderMinutes = $schedule->reminder_minutes ?? 30;
                $reminderTime = $scheduleDateTime->copy()->subMinutes($reminderMinutes);
                
                return $now->greaterThanOrEqualTo($reminderTime) && $now->lessThan($scheduleDateTime);
            });

        if ($eligibleSchedules->isEmpty()) {
            $this->warn('  ⚠️ No schedules need notification at this time.');
        } else {
            foreach ($eligibleSchedules as $schedule) {
                $scheduleDateTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->start_time);
                $reminderMinutes = $schedule->reminder_minutes ?? 30;
                $reminderTime = $scheduleDateTime->copy()->subMinutes($reminderMinutes);
                
                $this->line("  🔔 [{$schedule->id}] {$schedule->title}");
                $this->line("     User ID: {$schedule->user_id}");
                $this->line("     Schedule Time: {$scheduleDateTime->format('Y-m-d H:i')}");
                $this->line("     Reminder Time: {$reminderTime->format('Y-m-d H:i')} ({$reminderMinutes} min before)");
                $this->line("     Should send: YES ✅");
                $this->newLine();
            }
        }

        // 6. Check Upcoming Schedules (Next 24 hours)
        $this->info('📆 UPCOMING SCHEDULES (Next 24 hours):');
        
        $upcomingSchedules = Schedule::where('has_reminder', true)
            ->where('notification_sent', false)
            ->where('date', '>=', $now->toDateString())
            ->where('date', '<=', $now->copy()->addDay()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        if ($upcomingSchedules->isEmpty()) {
            $this->warn('  ⚠️ No upcoming schedules in the next 24 hours.');
        } else {
            foreach ($upcomingSchedules as $schedule) {
                $scheduleDateTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->start_time);
                $reminderMinutes = $schedule->reminder_minutes ?? 30;
                $reminderTime = $scheduleDateTime->copy()->subMinutes($reminderMinutes);
                $diffFromNow = $reminderTime->diffForHumans($now, true);
                
                $this->line("  📌 [{$schedule->id}] {$schedule->title}");
                $this->line("     User ID: {$schedule->user_id}");
                $this->line("     Schedule: {$scheduleDateTime->format('Y-m-d H:i')}");
                $this->line("     Will notify at: {$reminderTime->format('Y-m-d H:i')} ({$diffFromNow})");
                
                // Check if user has FCM token
                $userHasToken = DB::table('users')
                    ->where('id', $schedule->user_id)
                    ->whereNotNull('fcm_token')
                    ->exists();
                
                if ($userHasToken) {
                    $this->line("     FCM Token: ✅ Available");
                } else {
                    $this->line("     FCM Token: ❌ Missing (user needs to login)");
                }
                $this->newLine();
            }
        }

        // 7. Check Scheduler Status
        $this->info('⚙️ SCHEDULER STATUS:');
        
        // Check if schedule:work is running
        $this->line('  To run scheduler automatically:');
        $this->line('    php artisan schedule:work');
        $this->newLine();
        $this->line('  Or manually trigger:');
        $this->line('    php artisan schedule:check-reminders');
        $this->newLine();

        // 8. Firebase Service Account
        $this->info('🔥 FIREBASE CONFIGURATION:');
        $serviceAccountPath = storage_path('app/mystudymate-acfbe-firebase-adminsdk-fbsvc-435c4c6bb6.json');
        
        if (file_exists($serviceAccountPath)) {
            $this->line('  ✅ Service Account File: Found');
            $fileSize = filesize($serviceAccountPath);
            $this->line("     Size: " . number_format($fileSize) . " bytes");
        } else {
            $this->error('  ❌ Service Account File: NOT FOUND!');
            $this->warn('     Expected: ' . $serviceAccountPath);
            $this->warn('     Download from Firebase Console → Project Settings → Service Accounts');
        }
        $this->newLine();

        // 9. Summary
        $this->info('📋 SUMMARY:');
        
        $issues = [];
        
        if ($usersWithTokens->isEmpty()) {
            $issues[] = '❌ No users with FCM tokens';
        }
        
        if (!file_exists($serviceAccountPath)) {
            $issues[] = '❌ Missing Firebase service account file';
        }
        
        if ($eligibleSchedules->isEmpty() && $upcomingSchedules->isEmpty()) {
            $issues[] = '⚠️ No schedules to notify (now or upcoming)';
        }

        if (empty($issues)) {
            $this->info('  ✅ System is ready to send notifications!');
            if (!$eligibleSchedules->isEmpty()) {
                $this->info('  🔔 Run: php artisan schedule:check-reminders');
            }
        } else {
            $this->warn('  Issues found:');
            foreach ($issues as $issue) {
                $this->line("    {$issue}");
            }
        }

        $this->newLine();
        $this->info('==========================================');
        $this->info('✅ Debug completed!');

        return Command::SUCCESS;
    }
}
