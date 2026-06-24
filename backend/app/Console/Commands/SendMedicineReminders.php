<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prescription;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendMedicineReminders extends Command
{
    protected $signature = 'prescriptions:send-reminders';

    protected $description = 'Send push and database notifications to patients for medicine intake based on prescription schedules';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $currentHourMin = Carbon::now()->format('H:i');
        $count = 0;

        // Fetch active prescriptions: start_date <= today and (end_date >= today OR is_ongoing = true)
        $prescriptions = Prescription::with(['patient.user', 'doctor'])
            ->where('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->where('end_date', '>=', $today)
                      ->orWhere('is_ongoing', true);
            })
            ->get();

        foreach ($prescriptions as $prescription) {
            if (!$prescription->patient || !$prescription->patient->user) {
                continue;
            }

            $times = $prescription->frequency_times;
            if (!is_array($times)) {
                continue;
            }

            foreach ($times as $time) {
                // Ensure time is formatted as HH:MM
                $formattedTime = Carbon::parse($time)->format('H:i');

                // If scheduled time matches current time
                if ($formattedTime === $currentHourMin) {
                    try {
                        // Double-send protection: check if reminder already sent today for this prescription at this time
                        $alreadySent = DB::table('notifications')
                            ->where('notifiable_id', $prescription->patient->user->id)
                            ->where('category', 'prescription')
                            ->where('event_type', 'medicine_reminder')
                            ->where('entity_id', $prescription->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('data', 'like', '%' . $formattedTime . '%')
                            ->exists();

                        if (!$alreadySent) {
                            NotificationService::notifyMedicineReminder($prescription, $formattedTime);
                            $count++;
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send medicine reminder for Prescription ID: {$prescription->id}", ['exception' => $e]);
                    }
                }
            }
        }

        $this->info("Sent {$count} medicine reminders.");
        return Command::SUCCESS;
    }
}
