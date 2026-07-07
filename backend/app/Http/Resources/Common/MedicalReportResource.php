<?php

namespace App\Http\Resources\Common;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reportDate = Carbon::parse($this->report_date);

        // Get file from module_documents (via trait) or fallback to old file_path
        $file = $this->file ?? $this->file_path;
        $fileName = $this->file_name ?? ($file ? basename($file) : null);
        $fileType = $this->file_type ?? ($file ? pathinfo($file, PATHINFO_EXTENSION) : null);

        // Uploader name logic
        $uploaderName = 'Unknown';

        if ($this->uploader_type === 'Doctor' && $this->uploader_id) {
            $doctor = $this->uploader ?? ($this->uploader_id ? \App\Models\Doctor::find($this->uploader_id) : null);
            if ($doctor) {
                $firstName = $doctor->first_name ?? '';
                $lastName = $doctor->last_name ?? '';
                $uploaderName = trim($firstName . ' ' . $lastName);
            }
        } elseif (
            ($this->uploader_type === 'Patient' && $this->uploader_id) ||
            $this->patient_id
        ) {
            $patientId = $this->patient_id ?? $this->uploader_id;
            $patient = $this->uploader_type === 'Patient' && $this->uploader
                ? $this->uploader
                : ($patientId ? \App\Models\Patient::find($patientId) : null);
            if ($patient) {
                $firstName = $patient->first_name ?? '';
                $lastName = $patient->last_name ?? '';
                $uploaderName = trim($firstName . ' ' . $lastName);
            }
        }

        return [
            'id' => $this->id,
            'report_name' => $this->name,
            'report_type' => $this->type,
            'type_label' => str_contains($this->type, '_')
                ? ucwords(str_replace('_', ' ', $this->type))
                : $this->type,
            'report_date' => $reportDate->format('Y-m-d'),
            'report_date_formatted' => $reportDate->format('D, M d'),
            'file_url' => $this->file_url, // URL generated from file or file_path
            'file_name' => $fileName,
            'status' => $this->status,
            'report_notes' => $this->notes,
            'uploader_type' => $this->uploader_type,
            'uploader_name' => $uploaderName,
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->first_name . ' ' . $this->doctor->last_name,
                'first_name' => $this->doctor->first_name,
                'last_name' => $this->doctor->last_name,
                'appoinment_id' => $this->appointment?->id,
                'appoinment_status' => $this->appointment?->status === \App\Enums\AppointmentStatus::CONFIRMED ? 'upcoming' : 'past',
                'appointment_time_status' => $this->appointment
                    ? (
                        now()->toDateString() === $this->appointment->appointment_date->toDateString()
                        ? 'today'
                        : (now()->toDateString() < $this->appointment->appointment_date->toDateString()
                            ? 'upcoming'
                            : 'past')
                    )
                    : null,
            ] : null,
        ];
    }
}