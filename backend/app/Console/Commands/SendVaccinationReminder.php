<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\VaccinationStatus;
use App\Models\PatientVaccination;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendVaccinationReminder extends Command
{
    protected $signature = 'vaccinations:send-reminders {--days=1 : Days before the scheduled date to send reminders}';

    protected $description = 'Send reminders for upcoming scheduled vaccinations';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $targetDate = now()->addDays($days)->toDateString();
        $count = 0;

        PatientVaccination::with(['patient.user', 'doctor.user', 'vaccination'])
            ->where('status', VaccinationStatus::SCHEDULED->value)
            ->whereDate('scheduled_date', $targetDate)
            ->where('reminder_sent', false)
            ->chunkById(100, function ($patientVaccinations) use (&$count, $days) {
                foreach ($patientVaccinations as $patientVaccination) {
                    try {
                        $patient = $patientVaccination->patient;
                        $user = $patient?->user;

                        if (! $user) {
                            continue;
                        }

                        $vaccineName = $patientVaccination->vaccination?->name ?? 'vaccination';
                        $scheduledDate = optional($patientVaccination->scheduled_date)?->format('M d, Y');

                        NotificationService::send(
                            user: $user,
                            type: NotificationType::VACCINATION_REMINDER->value,
                            title: 'Vaccination Reminder',
                            message: "Your {$vaccineName} dose is scheduled for {$scheduledDate}.",
                            category: 'vaccination',
                            entityType: 'patient_vaccination',
                            entityId: $patientVaccination->id,
                            meta: [
                                'vaccination_name' => $vaccineName,
                                'scheduled_date' => $scheduledDate,
                                'days_before_due' => $days,
                                'doctor_id' => $patientVaccination->doctor_id,
                            ]
                        );

                        $patientVaccination->update(['reminder_sent' => true]);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::error("Failed to send vaccination reminder for ID {$patientVaccination->id}", [
                            'exception' => $e,
                        ]);
                    }
                }
            });

        $this->info("Sent {$count} vaccination reminders.");

        return Command::SUCCESS;
    }
}
