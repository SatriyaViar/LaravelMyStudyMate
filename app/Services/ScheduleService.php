<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class ScheduleService
{
    /**
     * Menambahkan jadwal baru ke dalam database setelah memeriksa konflik.
     * 
     * @param int $userId
     * @param array $data
     * @return Schedule
     * @throws Exception
     */
    public function createSchedule(int $userId, array $data): Schedule
    {
        // Mengecek konflik jadwal
        $hasConflict = Schedule::checkConflict(
            $userId,
            $data['date'],
            $data['start_time'],
            $data['end_time']
        );

        if ($hasConflict) {
            throw new Exception('Schedule conflict detected. You already have a schedule at this time.');
        }

        // Membuat jadwal baru jika tidak ada konflik
        return Schedule::create([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'location' => $data['location'],
            'color' => $data['color'],
            'type' => $data['type'],
            'has_reminder' => $data['has_reminder'] ?? true,
            'reminder_minutes' => $data['reminder_minutes'] ?? 30,
        ]);
    }

    /**
     * Mengupdate jadwal berdasarkan ID dan data yang diberikan setelah memeriksa konflik.
     * 
     * @param int $scheduleId
     * @param array $data
     * @return Schedule
     * @throws Exception
     */
    public function updateSchedule(int $scheduleId, array $data): Schedule
    {
        $schedule = Schedule::findOrFail($scheduleId);

        // Mengecek konflik dengan pengecualian jadwal yang sedang diperbarui
        $hasConflict = Schedule::checkConflict(
            $data['user_id'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $schedule->id // Mengecualikan jadwal yang sedang diperbarui
        );

        if ($hasConflict) {
            throw new Exception('Schedule conflict detected. You already have a schedule at this time.');
        }

        // Mengupdate jadwal
        $schedule->update($data);

        return $schedule;
    }

    /**
     * Menandai jadwal sebagai selesai atau tidak selesai.
     * 
     * @param int $scheduleId
     * @param bool $isCompleted
     * @return Schedule
     */
    public function toggleScheduleCompletion(int $scheduleId, bool $isCompleted): Schedule
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $schedule->is_completed = $isCompleted;
        $schedule->save();

        return $schedule;
    }

    /**
     * Mendapatkan jadwal berdasarkan ID pengguna.
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection|Schedule[]
     */
    public function getSchedulesForUser(int $userId)
    {
        return Schedule::forUser($userId)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Mendapatkan jadwal berdasarkan tanggal.
     * 
     * @param int $userId
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Collection|Schedule[]
     */
    public function getSchedulesByDate(int $userId, string $date)
    {
        return Schedule::forUser($userId)
            ->onDate($date)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Mendapatkan jadwal berdasarkan rentang tanggal.
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Collection|Schedule[]
     */
    public function getSchedulesByDateRange(int $userId, string $startDate, string $endDate)
    {
        return Schedule::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Mendapatkan jadwal yang akan datang (upcoming).
     * 
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection|Schedule[]
     */
    public function getUpcomingSchedules(int $userId, int $limit = 5)
    {
        return Schedule::forUser($userId)
            ->upcoming()
            ->incomplete()
            ->limit($limit)
            ->get();
    }

    /**
     * Mengecek konflik jadwal.
     * 
     * @param int $userId
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeId
     * @return bool
     */
    public static function checkScheduleConflict(int $userId, string $date, string $startTime, string $endTime, int $excludeId = null): bool
    {
        return Schedule::checkConflict($userId, $date, $startTime, $endTime, $excludeId);
    }

    /**
     * Mengambil statistik jadwal.
     * 
     * @param int $userId
     * @return array
     */
    public function getScheduleStats(int $userId): array
    {
        $today = Carbon::today();

        return [
            'total' => Schedule::forUser($userId)->count(),
            'completed' => Schedule::forUser($userId)->completed()->count(),
            'upcoming' => Schedule::forUser($userId)->upcoming()->incomplete()->count(),
            'today' => Schedule::forUser($userId)->onDate($today)->count(),
            'this_week' => Schedule::forUser($userId)
                ->betweenDates($today, $today->copy()->addDays(7))
                ->count(),
        ];
    }
}
