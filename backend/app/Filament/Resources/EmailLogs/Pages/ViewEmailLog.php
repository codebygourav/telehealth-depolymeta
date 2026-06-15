<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Email Logs')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(EmailLogResource::getUrl('index')),
        ];
    }
}
