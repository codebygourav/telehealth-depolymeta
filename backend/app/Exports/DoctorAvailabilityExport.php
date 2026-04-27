<?php

namespace App\Exports;

use App\Models\DoctorAvailability;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityExport implements FromCollection, WithHeadings, WithMapping, ShouldQueue
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

    public function map($row): array
    {
        return [
            $row->day_of_week,
            $row->date,
            $row->start_time,
            $row->end_time,
            $row->capacity,
            $row->consultation_type,
            $row->consultation_type === 'video' ? '' : ($row->opd_type ?? 'general'),
            $row->doctor_room,
            $row->consultation_fee,
            $row->is_recurring,
            $row->recurring_start_date,
            $row->recurring_end_date,
            $row->recurring_months,
        ];
    }
}
