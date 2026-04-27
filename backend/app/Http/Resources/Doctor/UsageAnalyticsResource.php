<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => [
                'total_appointments_this_month' => $this['summary']['current_month_count'],
                'compare_to_last_month' => [
                    'percentage_change' => $this['summary']['percentage_change'],
                    'is_positive' => $this['summary']['is_positive'],
                    'last_month_count' => $this['summary']['last_month_count'],
                ],
            ],
            'chart_data' => $this['chart_data'],
        ];
    }
}