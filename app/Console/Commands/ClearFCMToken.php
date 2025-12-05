<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ClearFCMToken extends Command
{
    protected $signature = 'fcm:clear {user_id?}';
    protected $description = 'Clear FCM token for a user (to force re-login)';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            // Clear specific user
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ User ID {$userId} not found");
                return;
            }

            $user->update(['fcm_token' => null]);
            $this->info("✅ FCM token cleared for user: {$user->name} (ID: {$user->id})");
            $this->warn("💡 User harus logout dan login lagi untuk mendapatkan token baru");
        } else {
            // Clear all users
            if (!$this->confirm('Clear FCM tokens untuk SEMUA user?', false)) {
                $this->info('Cancelled.');
                return;
            }

            $count = User::whereNotNull('fcm_token')->count();
            User::query()->update(['fcm_token' => null]);
            $this->info("✅ Cleared FCM tokens for {$count} users");
            $this->warn("💡 Semua user harus logout dan login lagi");
        }
    }
}
