<?php

namespace App\Exports;

use App\Models\DoctorAvailability;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityExport implements FromCollection, WithHeadings, ShouldQueue
{
    protected $doctorId;


    public function __construct($doctorId)
    {
        $this->doctorId = $doctorId;
    }

    public function collection()
    {
        return DoctorAvailability::where('doctor_id', $this->doctorId)
            ->get([
                'day_of_week',
                'date',
                'start_time',
                'end_time',
                'capacity',
                'consultation_type',
                'opd_type',
                'doctor_room',
                'consultation_fee',
                'is_recurring',
                'recurring_start_date',
                'recurring_end_date',

                // ✅ ADD THIS (calculate months)
                DB::raw('TIMESTAMPDIFF(MONTH, recurring_start_date, recurring_end_date) as recurring_months'),
            ]);
    }

    public function headings(): array
    {
        return [
            'day_of_week',
            'date',
            'start_time',
            'end_time',
            'capacity',
            'consultation_type',
            'opd_type',
            'doctor_room',
            'consultation_fee',
            'is_recurring',
            'recurring_start_date',
            'recurring_end_date',
            'recurring_months',
        ];
    }
}
