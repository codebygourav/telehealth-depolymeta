<?php

namespace App\Http\Resources\Common;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $days = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end date

        // Format leave type for display
        $typeLabels = [
            'sick' => 'Sick Leave',
            'casual' => 'Casual Leave',
            'annual' => 'Annual Vacation',
            'telehealth' => 'Telehealth Leave',
        ];

        // Format status for display
        $statusLabels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancel' => 'Cancel',
        ];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'type_label' => $typeLabels[$this->type] ?? ucfirst($this->type),
            'start_date' => $startDate->format('Y-m-d'),
            'start_date_formatted' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('Y-m-d'),
            'end_date_formatted' => $endDate->format('d M Y'),
            'duration' => $days,
            'duration_text' => $days . ' day' . ($days > 1 ? 's' : ''),
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? ucfirst($this->status),
            'status_comment' => $this->status_comment,
            'applied_date' => $this->created_at->format('d M Y'),
            'applied_date_formatted' => $this->created_at->format('d M Y'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
