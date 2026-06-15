<?php

namespace App\Filament\Resources\MedicalReports\Tables;

use App\Enums\MedicalReportStatus;
use Filament\Tables\Table;
use Filament\Actions\{
    BulkActionGroup,
    DeleteBulkAction,
    ForceDeleteBulkAction,
    RestoreBulkAction,
    ActionGroup,
    ViewAction,
    DeleteAction
};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Layout\{Stack, Split, Panel};
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ViewColumn;
use App\Models\MedicalReport;
use App\Filament\Resources\MedicalReports\MedicalReportResource;

class MedicalReportsTable
{
    public static function configure(Table $table): Table
    {
        // Always start from the proper model query instance, not a naked Builder
        return $table
            ->extraAttributes([
                'class' => 'medical-reports-grid-table ',
            ])
            ->query(function () {
                // Use MedicalReport::query() to avoid the "newQueryWithoutRelationships() on null" error.
                return MedicalReport::query()->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')
                        ->from('medical_reports')
                        ->whereNull('deleted_at')
                        ->groupBy('patient_id');
                });
            })
            // Use grid layout via ->grid() method for responsive cards in a grid, as per Filament docs.
            ->columns([
                Stack::make([
                    ViewColumn::make('medical_report_card')
                        ->view('filament.medical-reports.table.card-view')
                        ->label(''),
                ])->extraAttributes(['class' => 'relative']),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MedicalReportStatus::cases())
                        ->mapWithKeys(fn($status) => [$status->value => $status->label()])
                        ->toArray()),
                SelectFilter::make('type')
                    ->options([
                        'lab_report' => 'Lab Report',
                        'radiology' => 'Radiology',
                        'prescription' => 'Prescription',
                        'other' => 'Other',
                    ]),
            ])
            ->recordUrl(fn(MedicalReport $record): string => MedicalReportResource::getUrl('view', ['record' => $record]));
    }
}
