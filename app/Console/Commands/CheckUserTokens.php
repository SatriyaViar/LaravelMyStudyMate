<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Schedule;

class CheckUserTokens extends Command
{
    protected $signature = 'user:check-tokens';
    protected $description = 'Check which users have FCM tokens';

    public function handle()
    {
        $this->info('👥 Checking FCM Tokens...');
        $this->newLine();
        
        $withToken = User::whereNotNull('fcm_token')->get();
        $withoutToken = User::whereNull('fcm_token')->get();
        
        $this->info("✅ Users WITH FCM token ({$withToken->count()}):");
        foreach ($withToken as $user) {
            $this->line("  - ID: {$user->id}, Name: {$user->name}, Email: {$user->email}");
        }
        
        $this->newLine();
        $this->warn("❌ Users WITHOUT FCM token ({$withoutToken->count()}):");
        foreach ($withoutToken as $user) {
            $scheduleCount = Schedule::where('user_id', $user->id)->count();
            $this->line("  - ID: {$user->id}, Name: {$user->name}, Schedules: {$scheduleCount}");
        }
        
        $this->newLine();
        $this->comment('💡 Tip: User tanpa FCM token tidak akan menerima notifikasi. Mereka harus login di app untuk generate token.');
        
        return 0;
    }
}
