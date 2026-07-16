<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class DoctorRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Performance';
    protected ?string $maxHeight = '300px';

    protected string $color = 'success';

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

        // Revenue Data
        $revenueDataRaw = Payment::where('payments.status', 'paid')
            ->join('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->when($doctorId, fn($q) => $q->where('appointments.doctor_id', $doctorId))
            ->whereBetween('appointments.appointment_date', [$start, $end])
            ->selectRaw(match ($period) {
                'weekly' => 'DATE(appointments.appointment_date) as period_key',
                'monthly' => 'DAY(appointments.appointment_date) as period_key',
                default => 'MONTH(appointments.appointment_date) as period_key',
            } . ', SUM(payments.amount) as total')
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        // Retained (Loss) Data
        $retainedDataRaw = Appointment::whereIn('status', ['cancelled', 'no_show'])
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereBetween('appointment_date', [$start, $end])
            ->selectRaw(match ($period) {
                'weekly' => 'DATE(appointment_date) as period_key',
                'monthly' => 'DAY(appointment_date) as period_key',
                default => 'MONTH(appointment_date) as period_key',
            } . ', SUM(fee_amount) as total')
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        $labels = [];
        $revenueData = [];
        $retainedData = [];

        $iterations = match ($period) {
            'weekly' => 7,
            'monthly' => $selectedDate->daysInMonth,
            default => 12,
        };

        for ($i = 0; $i < $iterations; $i++) {
            $periodKey = null;
            $label = null;

            if ($period === 'weekly') {
                $day = $start->copy()->addDays($i);
                $periodKey = $day->format('Y-m-d');
                $label = $day->format('D');
            } elseif ($period === 'monthly') {
                $periodKey = $i + 1;
                $label = $i + 1;
            } else {
                $month = $start->copy()->addMonths($i);
                $periodKey = $i + 1;
                $label = $month->format('M');
            }

            $labels[] = $label;
            $revenueData[] = (float) ($revenueDataRaw[$periodKey] ?? 0);
            $retainedData[] = (float) ($retainedDataRaw[$periodKey] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₹)',
                    'data' => $revenueData,
                    'backgroundColor' => 'var(--primary).6',
                    'borderRadius' => 4,
                    'borderColor' => 'transparent',
                ],
                [
                    'label' => 'Lost Revenue (₹)',
                    'data' => $retainedData,
                    'backgroundColor' => '#94a3b8',
                    'borderRadius' => 4,
                    'border' => '2px solid #94a3b8',
                    'borderColor' => 'transparent',

                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxTicksLimit' => 10,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}