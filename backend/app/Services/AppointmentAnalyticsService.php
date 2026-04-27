<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentAnalyticsService
{
    /**
     * Get appointment counts for a doctor in different statuses
     * Uses Laravel optimized queries instead of raw SQL
     *
     * @param string $doctorId
     * @param array|null $patientIds Optional patient IDs to filter
     * @return array
     */
    public function getAppointmentSummary(string $doctorId, ?array $patientIds = null): array
    {
        $today = Carbon::today();

        $query = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->select('id', 'appointment_date', 'status');

        if ($patientIds && count($patientIds) > 0) {
            $query->whereIn('patient_id', $patientIds);
        }

        // Clone for different date queries
        $todayQuery = clone $query;
        $upcomingQuery = clone $query;
        $cancelledQuery = clone $query;

        // Get today's appointments
        $todaysAppointments = $todayQuery
            ->whereDate('appointment_date', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();

        // Get upcoming appointments
        $upcomingAppointments = $upcomingQuery
            ->whereDate('appointment_date', '>', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();

        // Get cancelled appointments
        $cancelledAppointments = $cancelledQuery
            ->where('status', 'cancelled')
            ->count();

        return [
            'todays_appointments' => $todaysAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'cancelled_appointments' => $cancelledAppointments,
        ];
    }

    /**
     * Get appointment comparison between current and previous month
     * Returns count comparison with percentage change
     */
    public function getMonthComparison(string $doctorId): array
    {
        if ($fakeSummary = $this->getFakeAnalyticsSummary()) {
            return $fakeSummary;
        }

        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Get current month count
        $currentMonthCount = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$currentMonth, $currentMonthEnd])
            ->whereIn('status', ['confirmed', 'rescheduled', 'completed', 'cancelled'])
            ->count();

        // Get last month count
        $lastMonthCount = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$lastMonth, $lastMonthEnd])
            ->whereIn('status', ['confirmed', 'rescheduled', 'completed', 'cancelled'])
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($lastMonthCount > 0) {
            $percentageChange = (($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100;
        } elseif ($currentMonthCount > 0) {
            $percentageChange = 100;
        }

        return [
            'current_month_count' => $currentMonthCount,
            'last_month_count' => $lastMonthCount,
            'percentage_change' => round($percentageChange, 1),
            'is_positive' => $percentageChange >= 0,
        ];
    }

    /**
     * Get chart data for appointments by day
     * Returns array of days with appointment counts
     */
    public function getWeeklyChartData(string $doctorId): array
    {
        if ($fakeChartData = $this->getFakeChartData('week')) {
            return $fakeChartData;
        }

        $now = Carbon::now();
        $data = [];

        // Get all appointments for the current week (Mon-Sun)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();

        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['confirmed', 'rescheduled', 'completed', 'cancelled'])
            ->select('id', 'appointment_date')
            ->whereBetween('appointment_date', [
                $startOfWeek->copy()->startOfDay(),
                $endOfWeek->copy()->endOfDay()
            ])
            ->get()
            ->groupBy(function ($item) {
                $dateVal = $item->appointment_date;
                if (is_string($dateVal)) {
                    // Parse string to Carbon
                    $dateVal = Carbon::parse($dateVal);
                }
                return $dateVal->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->count();
            });

        // Build data for each day of the current week (7 days)
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $count = $appointments->get($dateStr, 0);

            $data[] = [
                'label' => $date->format('D'),
                'date' => $dateStr,
                'value' => (int) $count,
            ];
        }

        return $data;
    }

    /**
     * Get chart data for appointments by month for current year (2026)
     * Returns array of months with appointment counts
     */
    public function getMonthlyChartData(string $doctorId): array
    {
        if ($fakeChartData = $this->getFakeChartData('month')) {
            return $fakeChartData;
        }

        $now = Carbon::now();
        $data = [];

        // Get all appointments for the current year (starting from Jan 2026)
        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['confirmed', 'rescheduled', 'completed', 'cancelled'])
            ->select('id', 'appointment_date')
            ->whereBetween('appointment_date', [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ])
            ->get()
            ->groupBy(function ($item) {
                $dateVal = $item->appointment_date;
                if (is_string($dateVal)) {
                    $dateVal = Carbon::parse($dateVal);
                }
                return $dateVal->format('Y-m');
            })
            ->map(function ($group) {
                return $group->count();
            });

        // Build data for each month of the current year (Jan-Dec 2026)
        for ($i = 1; $i <= 12; $i++) {
            $date = $now->copy()->month($i);
            $monthStr = $date->format('Y-m');
            $count = $appointments->get($monthStr, 0);

            $data[] = [
                'label' => $date->format('M'),
                'date' => $monthStr,
                'value' => (int) $count,
            ];
        }

        return $data;
    }

    /**
     * Get chart data for appointments by year (Starting from 2020)
     * Returns array of years with appointment counts
     */
    public function getYearlyChartData(string $doctorId): array
    {
        if ($fakeChartData = $this->getFakeChartData('year')) {
            return $fakeChartData;
        }

        $now = Carbon::now();
        $data = [];

        $startYear = 2020;
        $endYear = $now->year;

        // Get all appointments from 2020 to current year
        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['confirmed', 'rescheduled', 'completed', 'cancelled'])
            ->select('id', 'appointment_date')
            ->whereBetween('appointment_date', [
                Carbon::create($startYear, 1, 1),
                $now->copy()->endOfYear()
            ])
            ->get()
            ->groupBy(function ($item) {
                $dateVal = $item->appointment_date;
                if (is_string($dateVal)) {
                    $dateVal = Carbon::parse($dateVal);
                }
                return $dateVal->year;
            })
            ->map(function ($group) {
                return $group->count();
            });

        // Build data for each year from 2020 to present
        for ($year = $startYear; $year <= $endYear; $year++) {
            $count = $appointments->get($year, 0);

            $data[] = [
                'label' => (string) $year,
                'date' => (string) $year,
                'value' => (int) $count,
            ];
        }

        return $data;
    }

    /**
     * Get chart data based on period
     */
    public function getChartData(string $doctorId, string $period = 'month'): array
    {
        return match ($period) {
            'week' => $this->getWeeklyChartData($doctorId),
            'month' => $this->getMonthlyChartData($doctorId),
            'year' => $this->getYearlyChartData($doctorId),
            default => $this->getMonthlyChartData($doctorId),
        };
    }

    private function getFakeAnalyticsSummary(): ?array
    {
        $summary = $this->getFakeAnalyticsData()['summary'] ?? null;

        if (!is_array($summary)) {
            return null;
        }

        return [
            'current_month_count' => (int) ($summary['current_month_count'] ?? $summary['total_appointments_this_month'] ?? 0),
            'last_month_count' => (int) ($summary['last_month_count'] ?? data_get($summary, 'compare_to_last_month.last_month_count', 0)),
            'percentage_change' => (float) ($summary['percentage_change'] ?? data_get($summary, 'compare_to_last_month.percentage_change', 0)),
            'is_positive' => (bool) ($summary['is_positive'] ?? data_get($summary, 'compare_to_last_month.is_positive', true)),
        ];
    }

    private function getFakeChartData(string $period): ?array
    {
        $chartData = $this->getFakeAnalyticsData()['chart_data'][$period] ?? null;

        return is_array($chartData) ? $chartData : null;
    }

    private function getFakeAnalyticsData(): array
    {
        if (!$this->shouldUseFakeAnalyticsData()) {
            return [];
        }

        $pathData = $this->getFakeAnalyticsDataFromPath();

        if ($pathData !== null) {
            return $pathData;
        }

        $configData = config('usage_analytics.fake_data.data', []);

        return is_array($configData) ? $configData : [];
    }

    private function shouldUseFakeAnalyticsData(): bool
    {
        return (bool) config('usage_analytics.fake_data.enabled')
            || filled(config('usage_analytics.fake_data.path'));
    }

    private function getFakeAnalyticsDataFromPath(): ?array
    {
        $path = config('usage_analytics.fake_data.path');

        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $resolvedPath = $this->resolveFakeAnalyticsPath($path);

        if (!is_file($resolvedPath)) {
            return null;
        }

        $data = require $resolvedPath;

        return is_array($data) ? $data : null;
    }

    private function resolveFakeAnalyticsPath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
