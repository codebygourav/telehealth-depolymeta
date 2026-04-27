<?php

namespace App\Http\Resources\Common;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreviousAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof AppointmentStatus
            ? $this->status->value
            : $this->status;

        // If notes exists and is not empty, use notes; otherwise use instructions_by_doctor
        $notes = !empty($this->notes) ? $this->notes : 'No Reasoning provided';

        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->appointment_date)->format('D, M d'),
            'time' => Carbon::parse($this->appointment_time)->format('h:i A'),
            'consultation_type' => $this->consultation_type,
            'consultation_type_label' => $this->consultation_type === 'video'
                ? 'Video consultation'
                : 'Clinic Visit',
            'status' => $statusValue,
            'status_label' => ucfirst($statusValue),
            'notes' => is_array($notes) ? implode(', ', $notes) : $notes,
        ];
    }
}
