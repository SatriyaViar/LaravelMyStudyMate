<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class ScheduleReminderNotification extends Notification
{
    public function __construct()
    {
        // Konstruktor untuk notifikasi, jika ada data tambahan
    }

    // Mengatur saluran notifikasi (misalnya, FCM)
    public function via($notifiable)
    {
        return ['database', 'fcm']; // Menentukan saluran yang digunakan (misalnya push notification via FCM)
    }

    // Format pesan untuk push notification
    public function toFcm($notifiable)
    {
        return CloudMessage::new()
            ->withNotification(FirebaseNotification::create(
                'Reminder: Your schedule starts soon',
                'You have a schedule starting in 30 minutes.'
            ));
    }

    // Simpan notifikasi di database (opsional)
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your schedule starts in 30 minutes!',
            'schedule_id' => $notifiable->id,
        ];
    }
}
