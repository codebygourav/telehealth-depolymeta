<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PatientVaccination;
use App\Enums\VaccinationStatus;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendVaccinationReminders extends Command
{
    protected $signature = 'vaccinations:send-reminders';

    protected $description = 'Update vaccination statuses and send reminders to patients';

    public function handle()
    {
        $today = Carbon::today();
        $countUpdated = 0;
        $countNotifications = 0;

        // 1. Update statuses of all non-terminal vaccinations
        $vaccinationsToUpdate = PatientVaccination::whereNotIn('status', [
            VaccinationStatus::COMPLETED->value,
            VaccinationStatus::CANCELLED->value,
            VaccinationStatus::SKIPPED_BY_DOCTOR->value,
            VaccinationStatus::ON_HOLD->value,
            VaccinationStatus::PENDING_APPROVAL->value,
        ])->get();

        foreach ($vaccinationsToUpdate as $vac) {
            try {
                $newStatus = $vac->resolveStatusFromDates();
                // Compare values
                $currentStatusVal = $vac->status instanceof VaccinationStatus ? $vac->status->value : $vac->status;
                $newStatusVal = $newStatus instanceof VaccinationStatus ? $newStatus->value : $newStatus;

                if ($currentStatusVal !== $newStatusVal) {
                    $vac->status = $newStatus;
                    $vac->save(); // Save triggers updated hook -> sends overdue/missed alerts & logs
                    $countUpdated++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to update status for PatientVaccination ID: {$vac->id}", ['exception' => $e]);
            }
        }

        // 2. Scan and send upcoming notifications based on template offsets
        $vaccinations = PatientVaccination::with(['patient.user', 'vaccination', 'template'])
            ->whereNotIn('status', [
                VaccinationStatus::COMPLETED->value,
                VaccinationStatus::CANCELLED->value,
                VaccinationStatus::SKIPPED_BY_DOCTOR->value,
                VaccinationStatus::ON_HOLD->value,
                VaccinationStatus::PENDING_APPROVAL->value,
            ])
            ->get();

        foreach ($vaccinations as $vac) {
            try {
                if (!$vac->due_date) {
                    continue;
                }

                $dueDate = Carbon::parse($vac->due_date)->startOfDay();
                $diffInDays = $today->diffInDays($dueDate, false); // positive if due_date is in the future

                $template = $vac->template;
                $r1 = $template ? ($template->reminder_1_days_before ?? 7) : 7;
                $r2 = $template ? ($template->reminder_2_days_before ?? 3) : 3;
                $r3 = $template ? ($template->reminder_3_days_before ?? 1) : 1;

                $shouldNotify = false;
                $label = '';

                if ($diffInDays === $r1) {
                    $shouldNotify = true;
                    $label = "due in {$r1} days";
                } elseif ($diffInDays === $r2) {
                    $shouldNotify = true;
                    $label = "due in {$r2} days";
                } elseif ($diffInDays === $r3) {
                    $shouldNotify = true;
                    $label = "due tomorrow";
                } elseif ($diffInDays === 0) {
                    $shouldNotify = true;
                    $label = "due today";
                }

                if ($shouldNotify) {
                    NotificationService::notifyVaccinationDue($vac, $label);

                    // Mark reminder details
                    $vac->reminder_sent = true;
                    $vac->last_reminder_sent_at = now();
                    $vac->reminder_count = ($vac->reminder_count ?: 0) + 1;
                    $vac->saveQuietly();

                    $countNotifications++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send upcoming reminder for PatientVaccination ID: {$vac->id}", ['exception' => $e]);
            }
        }

        $this->info("Updated {$countUpdated} vaccination statuses and sent {$countNotifications} reminders.");
        return Command::SUCCESS;
    }
}
