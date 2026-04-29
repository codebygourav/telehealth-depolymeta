<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('custom_view')
                    ->view('filament.patients.patient-infolist')
                    ->state(fn ($record) => $record)
                    ->columnSpanFull(),
            ]);
    }
}

