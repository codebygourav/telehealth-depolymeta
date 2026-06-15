<?php

namespace App\Services;

use App\Models\PaidAppointment;

class PaidAppointmentSyncService
{
    public function syncExternalPaidAppointments(): array
    {
        $rows = PaidAppointment::query()
            ->orderBy('updated_at')
            ->get()
            ->map(fn (PaidAppointment $appointment): array => [
                'id' => $appointment->source_row_id ?: $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor_name,
                'patient_name' => $appointment->patient_name,
                'patient_unit_number' => $appointment->patient_unit_number,
                'patient_email' => $appointment->patient_email,
                'mobile' => $appointment->mobile,
                'appointment_date' => $appointment->appointment_date,
                'time_slot' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'consultation_type' => $appointment->consultation_type ?: 'in-person',
                'opd_type' => $appointment->opd_type ?: 'private',
                'track_upload_status' => $appointment->track_upload_status,
                'stack_upload_status' => $appointment->stack_upload_status,
                'created_at' => $appointment->source_created_at ?: $appointment->created_at,
                'payment_id' => $appointment->payment_id,
            ]);

        return app(ExternalBookingSyncService::class)->syncRows(
            rows: $rows,
            syncExisting: true,
            source: 'paid_appointment',
            preferProvidedSourceId: true,
            updateExisting: true,
        );
    }
}
