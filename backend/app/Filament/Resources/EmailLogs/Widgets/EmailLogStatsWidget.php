<?php

namespace App\Filament\Resources\EmailLogs\Widgets;

use App\Models\EmailLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailLogStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $sentToday   = EmailLog::sent()->today()->count();
        $failedToday = EmailLog::failed()->today()->count();
        $sentMonth   = EmailLog::sent()->thisMonth()->count();
        $failedMonth = EmailLog::failed()->thisMonth()->count();

        // 7-day trend for sent emails
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $trend[] = EmailLog::sent()->whereDate('created_at', now()->subDays($i))->count();
        }

        // 7-day trend for failed emails
        $failTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $failTrend[] = EmailLog::failed()->whereDate('created_at', now()->subDays($i))->count();
        }

        $totalToday = $sentToday + $failedToday;
        $successRate = $totalToday > 0
            ? round(($sentToday / $totalToday) * 100, 1)
            : 100;

        return [
            Stat::make('Sent Today', $sentToday)
                ->description('Emails delivered today')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success')
                ->chart($trend),

            Stat::make('Failed Today', $failedToday)
                ->description($failedToday > 0 ? 'Needs attention' : 'All clear')
                ->descriptionIcon($failedToday > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($failedToday > 0 ? 'danger' : 'success')
                ->chart($failTrend),

            Stat::make('Success Rate (Today)', $successRate . '%')
                ->description("{$sentToday} sent of {$totalToday} total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Sent This Month', $sentMonth)
                ->description("{$failedMonth} failed this month")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
