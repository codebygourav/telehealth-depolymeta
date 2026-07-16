<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class DoctorConsultationChart extends ChartWidget
{
    protected ?string $heading = 'Consultation Split';
        protected ?string $maxHeight = '300px';


    #[Reactive]
    public ?array $filters = null;

    protected function getData(): array
    {
        $doctorId = $this->filters['reportDoctor'] ?? 'all';
        $period = $this->filters['reportPeriod'] ?? 'monthly';
        $date = $this->filters['reportDate'] ?? now()->format('Y-m-d');
        $selectedDate = Carbon::parse($date);

        $start = match ($period) {
            'weekly' => $selectedDate->copy()->startOfWeek(),
            'monthly' => $selectedDate->copy()->startOfMonth(),
            default => $selectedDate->copy()->startOfYear(),
        };
        $end = match ($period) {
            'weekly' => $selectedDate->copy()->endOfWeek(),
            'monthly' => $selectedDate->copy()->endOfMonth(),
            default => $selectedDate->copy()->endOfYear(),
        };

        $doctorId = ($doctorId !== 'all') ? $doctorId : null;

        $typesCount = Appointment::query()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->whereBetween('appointment_date', [$start, $end])
            ->select('consultation_type', DB::raw('count(*) as total'))
            ->groupBy('consultation_type')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Appointments',
                    'data' => $typesCount->pluck('total')->toArray(),
                    'backgroundColor' => ['var(--primary)', 'var(--primary)', 'var(--primary)', 'var(--primary)', 'var(--primary)'],
                ],
            ],
            'labels' => $typesCount->map(fn ($t) => str_replace(['_', '-'], ' ', $t->consultation_type ?: 'General'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}