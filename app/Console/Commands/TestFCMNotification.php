<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FCMService;
use App\Models\User;

class TestFCMNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FCM notification delivery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Finding user with FCM token...');
        
        $user = User::whereNotNull('fcm_token')->first();
        
        if (!$user) {
            $this->error('❌ No user with FCM token found!');
            return Command::FAILURE;
        }

        $this->info("✅ Found user: {$user->name} (ID: {$user->id})");
        $this->info("📱 FCM Token: " . substr($user->fcm_token, 0, 50) . "...");
        
        $this->info('📤 Sending test notification...');
        
        $fcmService = app(FCMService::class);
        $sent = $fcmService->sendNotification(
            $user->fcm_token,
            '🧪 Test Notification',
            'Ini adalah test notifikasi dari MyStudyMate!',
            ['type' => 'test']
        );

        if ($sent) {
            $this->info('✅ Notification sent successfully!');
            $this->info('📱 Check your device now!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to send notification!');
            $this->error('💡 Check storage/logs/laravel.log for details');
            return Command::FAILURE;
        }
    }
}
