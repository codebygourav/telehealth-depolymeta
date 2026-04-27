<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\ViewEntry;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('custom_view')
                    ->view('filament.appointments.appointment-view')
                    ->state(fn($record) => $record)
                    ->columnSpanFull(),
            ]);
    }
}