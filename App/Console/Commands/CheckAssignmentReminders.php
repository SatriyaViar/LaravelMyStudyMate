<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Services\FCMService;
use Carbon\Carbon;

class CheckAssignmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and send assignment reminders (H-3, D-day, H+3)';

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
        $this->info('Checking assignment reminders...');
        
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();
        
        // H-3 (3 days before deadline) - send at 08:00
        if ($now->hour >= 8) {
            $threeDaysBefore = $today->copy()->addDays(3);
            $this->sendReminders($threeDaysBefore, 'h_minus_3', '⏰ Assignment Due in 3 Days!', 
                'Don\'t forget: "{title}" is due in 3 days. Start working on it!');
        }
        
        // D-day (deadline today) - send at 08:00
        if ($now->hour >= 8) {
            $this->sendReminders($today, 'd_day', '🔥 Assignment Due Today!', 
                'Urgent: "{title}" is due today! Complete it before the deadline.');
        }
        
        // H+3 (3 days after deadline, if not done) - send at 09:00
        if ($now->hour >= 9) {
            $threeDaysAfter = $today->copy()->subDays(3);
            $this->sendOverdueReminders($threeDaysAfter);
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
            ->where(function($query) use ($notificationType) {
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

    /**
     * Send overdue reminders
     */
    private function sendOverdueReminders($targetDate)
    {
        $assignments = Assignment::where('is_done', false)
            ->whereDate('deadline', $targetDate->toDateString())
            ->where(function($query) {
                $query->whereNull('last_notification_type')
                      ->orWhere('last_notification_type', '!=', 'h_plus_3');
            })
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->user && $assignment->user->fcm_token) {
                try {
                    $this->fcmService->sendNotification(
                        $assignment->user->fcm_token,
                        '❗ Assignment Overdue!',
                        "Assignment \"{$assignment->title}\" is 3 days overdue. Please complete it as soon as possible!",
                        [
                            'type' => 'assignment_reminder',
                            'assignment_id' => (string) $assignment->id,
                            'notification_type' => 'h_plus_3',
                            'deadline' => $assignment->deadline->toIso8601String(),
                        ]
                    );
                    
                    // Update last notification type
                    $assignment->last_notification_type = 'h_plus_3';
                    $assignment->save();
                    
                    $sent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send overdue notification for assignment {$assignment->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent {$sent} overdue (H+3) reminders");
    }
}
