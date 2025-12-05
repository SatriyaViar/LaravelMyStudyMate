<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Carbon\Carbon;

class SendReminderNotification extends Command
{
    // Nama command yang bisa dipanggil di artisan
    protected $signature = 'schedule:send-reminder';

    // Deskripsi singkat dari command
    protected $description = 'Send reminder notifications for upcoming schedules';

    // Menjalankan logic pengingat
    public function handle()
    {
        // Mendapatkan jadwal yang memiliki pengingat dan waktunya sudah dekat
        $schedules = Schedule::where('has_reminder', true)
            ->where('start_time', '>', Carbon::now()->subMinutes(30)) // Jadwal yang dimulai dalam 30 menit ke depan
            ->where('start_time', '<=', Carbon::now()->addMinutes(30)) // Waktu pengingat 30 menit sebelum kelas
            ->get();

        // Loop untuk mengirimkan notifikasi kepada setiap jadwal
        foreach ($schedules as $schedule) {
            // Cek jika waktu pengingat sudah tiba
            if ($schedule->reminder_datetime->lte(Carbon::now())) {
                // Kirim notifikasi menggunakan FCM atau sistem notifikasi lain
                $this->sendPushNotification($schedule);
            }
        }

        $this->info('Reminder notifications sent successfully');
    }

    // Fungsi untuk mengirim notifikasi push (misalnya menggunakan Firebase Cloud Messaging)
    // protected function sendPushNotification(Schedule $schedule)
    // {
    //     $messaging = app('firebase.messaging');
    //     $notification = Notification::create('Schedule Reminder', 'Your schedule starts in 30 minutes');
    //     $message = CloudMessage::withTarget('your-target-fcm-token') // Gantilah dengan token FCM target pengguna
    //         ->withNotification($notification);
    //     $messaging->send($message);
    // }
}
