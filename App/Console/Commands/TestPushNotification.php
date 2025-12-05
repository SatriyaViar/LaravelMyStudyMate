<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\FCMService;

class TestPushNotification extends Command
{
    protected $signature = 'fcm:test';
    protected $description = 'Test sending FCM push notification';

    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    public function handle()
    {
        $this->info('🔍 Finding user with FCM token...');
        
        $user = User::whereNotNull('fcm_token')->first();
        
        if (!$user) {
            $this->error('❌ No user with FCM token found!');
            return Command::FAILURE;
        }

        $this->info("✅ Found user: {$user->name} (ID: {$user->id})");
        $this->info("📱 FCM Token: " . substr($user->fcm_token, 0, 30) . "...");
        
        $this->info('📤 Sending test notification...');
        
        $sent = $this->fcmService->sendNotification(
            $user->fcm_token,
            'Test Notifikasi',
            'Ini adalah test push notification dari MyStudyMate!',
            [
                'type' => 'test',
                'message' => 'Hello from backend!'
            ]
        );

        if ($sent) {
            $this->info('✅ Notification sent successfully!');
            $this->info('📱 Check your Samsung device now!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to send notification!');
            return Command::FAILURE;
        }
    }
}
