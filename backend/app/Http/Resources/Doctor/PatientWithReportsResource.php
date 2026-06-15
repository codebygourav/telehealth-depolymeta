<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientWithReportsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAllFilter = $request->get('filter') === 'all';

        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            // Only include these keys if $isAllFilter is false
            ...(
                ! $isAllFilter
                ? [
                    'patient_id' => 'PT-' . substr((string) $this->id, 0, 4),
                    'avatar' => storage_url($this->avatar),
                    'blood_group' => $this->blood_group,
                    'email' => $this->user?->email,
                    'phone' => $this->user?->phone,
                    'address' => $this->address,
                    'pincode' => $this->pincode,
                    'area' => $this->area,
                    'landmark' => $this->landmark,
                    'city' => $this->city,
                    'state' => $this->state,
                    'age' => $this->age,
                    'gender' => \App\Enums\GenderOption::labels()[$this->gender] ?? ucfirst($this->gender ?? 'Unknown'),
                    'total_reports_count' => $this->when(! $isAllFilter, $this->total_reports_count ?? 0),
                ]
                : []
            ),

            'reports' => PatientReportResource::collection($this->whenLoaded('medicalReports') ?? collect()),
        ];
    }
}
