<?php

namespace App\Filament\Resources\MedicalReports\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\ViewEntry;

class MedicalReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('report_details')
                    ->view('filament.medical-reports.medical-report-view')
                    ->state(fn($record) => $record)
                    ->columnSpanFull(),
            ]);
    }
}
