<?php

namespace App\Http\Resources\Common;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDate = $this->start_date ? Carbon::parse($this->start_date) : null;
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        // Format frequency times
        $frequencyTimes = '';
        if ($this->frequency_times && is_array($this->frequency_times)) {
            $frequencyTimes = implode(', ', $this->frequency_times);
        }

        // Format duration
        $durationText = 'Daily, ongoing';
        if (!$this->is_ongoing) {
            if ($this->duration_value && $this->duration_type) {
                $durationText = $this->duration_value . ' ' . $this->duration_type;
            } elseif ($this->duration) {
                $durationText = $this->duration;
            }
        }

        return [
            'id' => $this->id,
            'medicine_name' => $this->medicine_name,
            'medicine_type' => $this->medicine_type,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'meal' => $this->meal_timing,
            'frequency_times' => $this->frequency_times,
            'frequency_display' => $this->frequency ? ($this->frequency . ($frequencyTimes ? ' (' . $frequencyTimes . ')' : '')) : null,
            'duration' => $this->duration,
            'duration_type' => $this->duration_type,
            'duration_value' => $this->duration_value,
            'duration_text' => $durationText,
            'instructions' => $this->instructions,
            'quantity' => $this->quantity,
            'is_ongoing' => $this->is_ongoing,
            'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
            'start_date_formatted' => $startDate ? $startDate->format('Y-m-d H:i') : null,
            'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
            'end_date_formatted' => $endDate ? $endDate->format('Y-m-d') : null,
            'order' => $this->order,
            'status' => $this->status,
            'use_type' => $this->use_type ?? 'regular',
            'take_when' => $this->take_when,
            'min_gap' => $this->min_gap,
            'max_doses_per_day' => $this->max_doses_per_day,
            'patient_instruction' => $this->patient_instruction,
        ];
    }
}
