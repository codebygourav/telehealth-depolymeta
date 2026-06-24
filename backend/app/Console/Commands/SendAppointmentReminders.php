<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send push notifications to patients and doctors before their appointments';

    public function handle()
    {
        $intervals = [20, 15, 30, 45, 60, 1440];
        $now = Carbon::now();
        $count = 0;

        foreach ($intervals as $minutes) {
            $targetTime = $now->copy()->addMinutes($minutes);
            $targetDateStr = $targetTime->toDateString();
            
            // To be safe with different time formats (H:i vs H:i:s), we can use whereRaw or whereTime
            // In MySQL, `whereTime` works well for TIME / DATETIME columns
            $targetTimeStr = $targetTime->format('H:i');

            $appointments = Appointment::with(['doctor.user', 'patient.user'])
                ->where('status', AppointmentStatus::CONFIRMED->value)
                ->whereDate('appointment_date', $targetDateStr)
                // Match the time up to the minute (e.g. 14:30:%)
                ->where('appointment_time', 'LIKE', $targetTimeStr . '%')
                ->get();

            foreach ($appointments as $appointment) {
                try {
                    NotificationService::notifyAppointmentReminder($appointment, $minutes);
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to send appointment reminder for Appointment ID: {$appointment->id}", ['exception' => $e]);
                }
            }
        }

        $this->info("Sent {$count} appointment reminders.");
        return Command::SUCCESS;
    }
}
