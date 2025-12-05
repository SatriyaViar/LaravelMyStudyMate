<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing schedules for test users to avoid duplicates
        $testEmails = ['satriya@example.com', 'azaria@example.com'];
        $testUserIds = User::whereIn('email', $testEmails)->pluck('id');
        Schedule::whereIn('user_id', $testUserIds)->delete();

        // Create test users if they don't exist
        $user1 = User::firstOrCreate(
            ['email' => 'satriya@example.com'],
            [
                'name' => 'Satriya',
                'username' => 'satriya',
                'password' => bcrypt('password123'),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'azaria@example.com'],
            [
                'name' => 'Azaria',
                'username' => 'azaria',
                'password' => bcrypt('password123'),
            ]
        );

        $this->command->info("User 1 (Satriya) ID: {$user1->id}");
        $this->command->info("User 2 (Azaria) ID: {$user2->id}");

        // Schedules for User 1 (Satriya)
        $schedulesUser1 = [
            [
                'title' => 'Mobile Development',
                'description' => 'Flutter and React Native development',
                'date' => Carbon::today(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'location' => 'Room A101',
                'lecturer' => 'Dr. Budi Santoso',
                'type' => 'lecture',
                'color' => '#5B9FED',
            ],
            [
                'title' => 'Database Systems Lab',
                'description' => 'MySQL and PostgreSQL practical session',
                'date' => Carbon::today(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'location' => 'Lab 201',
                'lecturer' => 'Prof. Siti Aminah',
                'type' => 'lab',
                'color' => '#10B981',
            ],
            [
                'title' => 'UI/UX Design',
                'description' => 'Figma and prototyping techniques',
                'date' => Carbon::tomorrow(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'location' => 'Design Studio',
                'lecturer' => 'Ir. Ahmad Wijaya',
                'type' => 'lecture',
                'color' => '#8B5CF6',
            ],
            [
                'title' => 'Team Meeting',
                'description' => 'Project progress discussion',
                'date' => Carbon::tomorrow(),
                'start_time' => '14:00',
                'end_time' => '15:30',
                'location' => 'Meeting Room 3',
                'type' => 'meeting',
                'color' => '#F59E0B',
            ],
            [
                'title' => 'Web Programming',
                'description' => 'Laravel and Vue.js development',
                'date' => Carbon::today()->addDays(2),
                'start_time' => '08:30',
                'end_time' => '10:30',
                'location' => 'Room B202',
                'lecturer' => 'Dr. Rina Susanti',
                'type' => 'lecture',
                'color' => '#EC4899',
            ],
        ];

        foreach ($schedulesUser1 as $schedule) {
            Schedule::create(array_merge($schedule, [
                'user_id' => $user1->id,
                'has_reminder' => true,
                'reminder_minutes' => 30,
                'is_completed' => false,
            ]));
        }

        // Schedules for User 2 (Azaria) - Different schedules
        $schedulesUser2 = [
            [
                'title' => 'Data Structures',
                'description' => 'Arrays, Linked Lists, and Trees',
                'date' => Carbon::today(),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'location' => 'Room C301',
                'lecturer' => 'Dr. Hendra Pratama',
                'type' => 'lecture',
                'color' => '#3B82F6',
            ],
            [
                'title' => 'Algorithm Analysis',
                'description' => 'Time and space complexity',
                'date' => Carbon::tomorrow(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'location' => 'Room D401',
                'lecturer' => 'Prof. Lisa Anggraini',
                'type' => 'lecture',
                'color' => '#10B981',
            ],
            [
                'title' => 'Networking Lab',
                'description' => 'TCP/IP and routing protocols',
                'date' => Carbon::today()->addDays(2),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'location' => 'Network Lab',
                'lecturer' => 'Ir. Doni Setiawan',
                'type' => 'lab',
                'color' => '#8B5CF6',
            ],
        ];

        foreach ($schedulesUser2 as $schedule) {
            Schedule::create(array_merge($schedule, [
                'user_id' => $user2->id,
                'has_reminder' => true,
                'reminder_minutes' => 30,
                'is_completed' => false,
            ]));
        }

        $this->command->info('✅ Schedules seeded successfully for multiple users!');
        $this->command->info('User 1 (Satriya): ' . count($schedulesUser1) . ' schedules');
        $this->command->info('User 2 (Azaria): ' . count($schedulesUser2) . ' schedules');
    }
}
