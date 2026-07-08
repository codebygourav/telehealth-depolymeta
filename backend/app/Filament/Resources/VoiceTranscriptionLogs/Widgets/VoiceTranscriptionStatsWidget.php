<?php

namespace App\Filament\Resources\VoiceTranscriptionLogs\Widgets;

use App\Models\VoiceTranscriptionLog;
use App\Services\DeepgramService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VoiceTranscriptionStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $stats = DeepgramService::getUsageStats();

        $totalToday   = VoiceTranscriptionLog::today()->count();
        $successToday = VoiceTranscriptionLog::today()->success()->count();
        $failedToday  = VoiceTranscriptionLog::today()->failed()->count();

        // 7-day trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $trend[] = VoiceTranscriptionLog::success()
                ->whereDate('created_at', now()->subDays($i))
                ->count();
        }

        $budgetMinutes    = $stats['budget_minutes'];
        $usedMinutes      = $stats['monthly_duration_minutes'];
        $remainingMinutes = $stats['remaining_minutes'];

        $budgetDescription = $budgetMinutes > 0
            ? "{$usedMinutes} min used of {$budgetMinutes} min budget"
            : "{$usedMinutes} min used this month";

        $budgetColor = match (true) {
            $budgetMinutes <= 0                             => 'info',
            ($usedMinutes / max($budgetMinutes, 1)) < 0.7  => 'success',
            ($usedMinutes / max($budgetMinutes, 1)) < 0.9  => 'warning',
            default                                         => 'danger',
        };

        return [
            Stat::make('Recordings Today', $successToday)
                ->description($failedToday > 0 ? "{$failedToday} failed today" : 'All successful today')
                ->descriptionIcon($failedToday > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($failedToday > 0 ? 'warning' : 'success')
                ->chart($trend),

            Stat::make('Total Recordings', $stats['total_recordings'])
                ->description("{$stats['total_failed']} failed total")
                ->descriptionIcon('heroicon-m-microphone')
                ->color('info'),

            Stat::make('Monthly Usage', $usedMinutes . ' min')
                ->description($budgetDescription)
                ->descriptionIcon('heroicon-m-clock')
                ->color($budgetColor),

            Stat::make('Monthly Cost Estimate', '$' . $stats['monthly_credits_usd'])
                ->description('$' . $stats['total_credits_usd'] . ' total all-time')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),
        ];
    }
}
