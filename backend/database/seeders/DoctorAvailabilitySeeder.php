<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Doctor, DoctorAvailability};
use Carbon\Carbon;

class DoctorAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = Doctor::all();

        if ($doctors->isEmpty()) {
            return;
        }

        foreach ($doctors as $doctor) {
            // 1. Recurring video slot
            $this->createRecurringSlots($doctor, [1], '09:00', '12:00', 'video');

            // 2. In-person general and private OPD slots
            $futureDate = Carbon::tomorrow();
            $this->createOneOffSlot($doctor, $futureDate, '14:00', '16:00', 'in-person', 'general');
            $this->createOneOffSlot($doctor, $futureDate->copy()->addDay(), '10:00', '12:00', 'in-person', 'private');
        }
    }

    private function createRecurringSlots(Doctor $doctor, array $days, string $start, string $end, string $type, ?string $opd = null): void
    {
        foreach ($days as $dayOfWeek) {
            // Find the first occurrence of this day of week starting from today
            $startDate = Carbon::today();
            if ($startDate->dayOfWeek !== $dayOfWeek) {
                $startDate->next($dayOfWeek);
            }

            DoctorAvailability::create([
                'doctor_id' => $doctor->id,
                'date' => null, // Recurring
                'day_of_week' => null, // Requirement: empty if recurring
                'start_time' => $start,
                'end_time' => $end,
                'capacity' => 10,
                'consultation_type' => $type,
                'is_recurring' => true,
                'opd_type' => $opd,
                'consultation_fee' => 1,
                'is_available' => true,
                'recurring_start_date' => $startDate->format('Y-m-d'),
                'recurring_end_date' => $startDate->copy()->addMonths(3)->format('Y-m-d'),
            ]);
        }
    }

    private function createOneOffSlot(Doctor $doctor, Carbon $date, string $start, string $end, string $type, ?string $opd = null): void
    {
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'day_of_week' => strtolower($date->format('l')),
            'start_time' => $start,
            'end_time' => $end,
            'capacity' => 5,
            'consultation_type' => $type,
            'is_recurring' => false,
            'opd_type' => $type === 'in-person' ? $opd : null,
            'consultation_fee' => 1,
            'is_available' => true,
        ]);
    }
}
