<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Mail\PatientNextAppointmentMail;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class PatientNotificationController extends Controller
{
    /**
     * Send an appointment reminder notification for the next upcoming booking.
     */
    public function notifyNextAppointment(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc'],
            'doctor_id' => ['required', Rule::exists('doctors', 'id')],
            'slot_key' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedSlot = collect($this->availableSlots($data['doctor_id']))
            ->firstWhere('key', $data['slot_key']);
        $previousAppointment = $this->previousAppointment($patient);

        if (! $selectedSlot) {
            return back()->with('patient_notification_error', 'Selected slot is no longer available.');
        }

        Mail::to($data['email'])->send(new PatientNextAppointmentMail(
            patient: $patient,
            previousAppointment: $previousAppointment,
            nextSlot: $selectedSlot,
            adminMessage: $data['message'] ?? null,
        ));

        return back()->with('patient_notification_sent', $data['email']);
    }

    private function previousAppointment(Patient $patient): ?Appointment
    {
        return $patient->appointments()
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESCHEDULED->value,
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->with(['doctor.user', 'patient.user'])
            ->first();
    }

    private function availableSlots(string $doctorId): array
    {
        $startDate = now()->startOfDay();
        $endDate = now()->copy()->addMonths(2)->endOfDay();

        return DoctorAvailability::query()
            ->with('doctor.user')
            ->where('doctor_id', $doctorId)
            ->where('is_available', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($query) use ($startDate) {
                    $query->where('is_recurring', false)
                        ->whereDate('date', '>=', $startDate->toDateString());
                })->orWhere(function ($query) use ($startDate, $endDate) {
                    $query->where('is_recurring', true)
                        ->where(function ($query) use ($endDate) {
                            $query->whereNull('recurring_start_date')
                                ->orWhereDate('recurring_start_date', '<=', $endDate->toDateString());
                        })
                        ->where(function ($query) use ($startDate) {
                            $query->whereNull('recurring_end_date')
                                ->orWhereDate('recurring_end_date', '>=', $startDate->toDateString());
                        });
                });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->flatMap(fn (DoctorAvailability $availability) => $this->expandAvailability($availability, $startDate, $endDate))
            ->sortBy(['date', 'start_time'])
            ->values()
            ->all();
    }

    private function expandAvailability(DoctorAvailability $availability, Carbon $startDate, Carbon $endDate): array
    {
        if (! $availability->is_recurring) {
            $date = $availability->date ? Carbon::parse($availability->date) : null;

            if (! $date || $date->lt($startDate)) {
                return [];
            }

            return [$this->slotPayload($availability, $date)];
        }

        $slots = [];
        $cursor = $startDate->copy();
        $recurringStart = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date) : $startDate;
        $recurringEnd = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date) : $endDate;
        $dayOfWeek = strtolower((string) $availability->day_of_week);
        $blockedDates = collect($availability->blocked_dates ?? [])->map(fn ($date) => Carbon::parse($date)->toDateString())->all();

        while ($cursor->lte($endDate)) {
            if (
                $cursor->gte($recurringStart) &&
                $cursor->lte($recurringEnd) &&
                strtolower($cursor->format('l')) === $dayOfWeek &&
                ! in_array($cursor->toDateString(), $blockedDates, true)
            ) {
                $slots[] = $this->slotPayload($availability, $cursor);
            }

            $cursor->addDay();
        }

        return $slots;
    }

    private function slotPayload(DoctorAvailability $availability, Carbon $date): array
    {
        $doctorName = $availability->doctor
            ? trim(($availability->doctor->first_name ?? '').' '.($availability->doctor->last_name ?? ''))
            : 'Doctor';
        $startTime = Carbon::parse($availability->start_time)->format('H:i:s');
        $endTime = Carbon::parse($availability->end_time)->format('H:i:s');

        return [
            'key' => implode('|', [$availability->id, $date->toDateString(), $startTime, $endTime]),
            'doctor_id' => $availability->doctor_id,
            'doctor_name' => $doctorName,
            'date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'consultation_type' => $availability->consultation_type,
            'opd_type' => $availability->opd_type,
        ];
    }
}