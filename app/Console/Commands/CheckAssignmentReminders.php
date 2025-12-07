<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Services\FCMService;
use Carbon\Carbon;

class CheckAssignmentReminders extends Command
{
    protected $signature = 'assignments:check-reminders';
    protected $description = 'Check and send assignment reminders (H-3, H-2, H-1, D-day, H+1, H+2, H+3)';
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    public function handle()
    {
        $this->info('Checking assignment reminders...');

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();

        // H-3 (3 days before deadline) - send at 08:00
        if ($now->hour == 8 && $now->minute < 5) {
            $threeDaysBefore = $today->copy()->addDays(3);
            $this->sendReminders(
                $threeDaysBefore,
                'h_minus_3',
                '⏰ Assignment Due in 3 Days!',
                'Don\'t forget: "{title}" is due in 3 days. Start working on it!'
            );
        }

        // H-2 (2 days before deadline) - send at 08:00
        if ($now->hour == 8 && $now->minute < 5) {
            $twoDaysBefore = $today->copy()->addDays(2);
            $this->sendReminders(
                $twoDaysBefore,
                'h_minus_2',
                '⏰ Assignment Due in 2 Days!',
                'Don\'t forget: "{title}" is due in 2 days. Keep working on it!'
            );
        }

        // H-1 (1 day before deadline) - send at 08:00
        if ($now->hour == 8 && $now->minute < 5) {
            $oneDayBefore = $today->copy()->addDays(1);
            $this->sendReminders(
                $oneDayBefore,
                'h_minus_1',
                '⚠️ Assignment Due Tomorrow!',
                'Reminder: "{title}" is due tomorrow! Make sure to complete it on time.'
            );
        }

        // D-day (deadline today) - send at 08:00
        if ($now->hour == 8 && $now->minute < 5) {
            $this->sendReminders(
                $today,
                'd_day',
                '🔥 Assignment Due Today!',
                'Urgent: "{title}" is due today! Complete it before the deadline.'
            );
        }

        // H+1 (1 day after deadline, if not done) - send at 09:00
        if ($now->hour == 9 && $now->minute < 5) {
            $oneDayAfter = $today->copy()->subDays(1);
            $this->sendReminders(
                $oneDayAfter,
                'h_plus_1',
                '❌ Assignment Overdue (1 Day)!',
                '"{title}" is now 1 day overdue. Please submit as soon as possible!'
            );
        }

        // H+2 (2 days after deadline, if not done) - send at 09:00
        if ($now->hour == 9 && $now->minute < 5) {
            $twoDaysAfter = $today->copy()->subDays(2);
            $this->sendReminders(
                $twoDaysAfter,
                'h_plus_2',
                '❌ Assignment Overdue (2 Days)!',
                '"{title}" is now 2 days overdue. Please submit immediately!'
            );
        }

        // H+3 (3 days after deadline, if not done) - send at 09:00
        if ($now->hour == 9 && $now->minute < 5) {
            $threeDaysAfter = $today->copy()->subDays(3);
            $this->sendReminders(
                $threeDaysAfter,
                'h_plus_3',
                '🚨 Assignment Overdue (3 Days)!',
                '"{title}" is now 3 days overdue. Please contact your instructor!'
            );
        }

        $this->info('Assignment reminders checked successfully!');
    }

    /**
     * Send reminders for specific date
     */
    private function sendReminders($targetDate, $notificationType, $title, $bodyTemplate)
    {
        $assignments = Assignment::where('is_done', false)
            ->whereDate('deadline', $targetDate->toDateString())
            ->where(function ($query) use ($notificationType) {
                $query->whereNull('last_notification_type')
                    ->orWhere('last_notification_type', '!=', $notificationType);
            })
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($assignments as $assignment) {
            if ($assignment->user && $assignment->user->fcm_token) {
                $body = str_replace('{title}', $assignment->title, $bodyTemplate);

                try {
                    $this->fcmService->sendNotification(
                        $assignment->user->fcm_token,
                        $title,
                        $body,
                        [
                            'type' => 'assignment_reminder',
                            'assignment_id' => (string) $assignment->id,
                            'notification_type' => $notificationType,
                            'deadline' => $assignment->deadline->toIso8601String(),
                        ]
                    );

                    // Update last notification type
                    $assignment->last_notification_type = $notificationType;
                    $assignment->save();

                    $sent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send notification for assignment {$assignment->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent {$sent} {$notificationType} reminders");
    }
}