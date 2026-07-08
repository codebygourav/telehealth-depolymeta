<?php

namespace App\Filament\Resources\VoiceTranscriptionLogs\Pages;

use App\Filament\Resources\VoiceTranscriptionLogs\VoiceTranscriptionLogResource;
use App\Filament\Resources\VoiceTranscriptionLogs\Widgets\VoiceTranscriptionStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListVoiceTranscriptionLogs extends ListRecords
{
    protected static string $resource = VoiceTranscriptionLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VoiceTranscriptionStatsWidget::class];
    }

    protected function getActions(): array
    {
        return [];
    }
}
