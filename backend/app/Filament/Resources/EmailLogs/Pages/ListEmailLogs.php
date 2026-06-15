<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\EmailLogs\Widgets\EmailLogStatsWidget;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmailLogStatsWidget::class,
        ];
    }
}
