<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class UpdateAppointmentsStatus extends Command
{
    protected $signature = 'appointments:update-status';

    protected $description = 'Mark past appointments as completed';

    public function handle()
    {
        $now = Carbon::now();

        // Combine appointment_date + appointment_end_time and compare
        $appointments = Appointment::whereNotIn('status', [
            AppointmentStatus::CANCELLED->value,
            AppointmentStatus::FAILED->value,
            AppointmentStatus::NO_SHOW->value,
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::PENDING->value,
        ])
            ->whereRaw("
                TIMESTAMP(appointment_date, appointment_end_time) <= ?
            ", [$now])
            ->get();

        $count = 0;

        foreach ($appointments as $appointment) {
            $appointment->status = AppointmentStatus::COMPLETED->value;
            $appointment->save();
            $count++;
        }

        $this->info("Updated {$count} appointments to completed.");
        return Command::SUCCESS;
    }
}