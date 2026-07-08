<?php

namespace App\Filament\Resources\VoiceTranscriptionLogs\Pages;

use App\Filament\Resources\VoiceTranscriptionLogs\VoiceTranscriptionLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVoiceTranscriptionLog extends ViewRecord
{
    protected static string $resource = VoiceTranscriptionLogResource::class;

    protected function getActions(): array
    {
        return [];
    }
}
