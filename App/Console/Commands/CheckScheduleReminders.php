<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Services\FCMService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckScheduleReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for schedules that need reminder notifications';

    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for schedules that need reminders...');

        $now = Carbon::now();

        // Get schedules that:
        // 1. Have reminder enabled
        // 2. Haven't been notified yet
        // 3. Start time is between now and (now + reminder_minutes)
        $schedules = Schedule::where('has_reminder', true)
            ->where('notification_sent', false)
            ->where('date', '>=', $now->toDateString())
            ->get()
            ->filter(function ($schedule) use ($now) {
                // Parse date (Y-m-d) and time (H:i:s) separately
                $dateString = Carbon::parse($schedule->date)->format('Y-m-d');
                $timeString = Carbon::parse($schedule->start_time)->format('H:i:s');
                $scheduleDateTime = Carbon::parse($dateString . ' ' . $timeString);
                $reminderMinutes = $schedule->reminder_minutes ?? 30;
                $reminderTime = $scheduleDateTime->copy()->subMinutes($reminderMinutes);
                
                // Check if current time is past reminder time and before schedule start
                return $now->greaterThanOrEqualTo($reminderTime) && $now->lessThan($scheduleDateTime);
            });

        if ($schedules->isEmpty()) {
            $this->info('✅ No schedules need reminders at this time.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$schedules->count()} schedule(s) to notify.");

        foreach ($schedules as $schedule) {
            $this->sendReminder($schedule);
        }

        $this->info('✅ Reminder check completed!');
        return Command::SUCCESS;
    }

    protected function sendReminder(Schedule $schedule)
    {
        // Get user's FCM token
        $user = DB::table('users')->where('id', $schedule->user_id)->first();

        if (!$user || empty($user->fcm_token)) {
            $this->warn("⚠️ No FCM token for user {$schedule->user_id}");
            return;
        }

        // Format notification
        $scheduleTime = Carbon::parse($schedule->start_time)->format('H:i');
        $reminderMinutes = $schedule->reminder_minutes ?? 30;
        $title = '⏰ Kelas Akan Dimulai!';
        $body = "{$schedule->title} dimulai dalam {$reminderMinutes} menit ({$scheduleTime})";
        
        if ($schedule->location) {
            $body .= "\n📍 {$schedule->location}";
        }

        $data = [
            'type' => 'schedule_reminder',
            'schedule_id' => (string) $schedule->id,
            'title' => $schedule->title,
            'start_time' => $schedule->start_time,
            'location' => $schedule->location ?? '',
        ];

        // Send notification
        $sent = $this->fcmService->sendNotification(
            $user->fcm_token,
            $title,
            $body,
            $data
        );

        if ($sent) {
            // Mark as notified
            $schedule->update(['notification_sent' => true]);
            $this->info("✅ Notification sent: {$schedule->title} for user {$user->name}");
        } else {
            $this->error("❌ Failed to send notification for: {$schedule->title}");
        }
    }
}
