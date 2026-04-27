<?php

namespace App\Filament\Exports;

use App\Models\Symptom;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SymptomExporter extends Exporter
{
    protected static ?string $model = Symptom::class;

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
        ];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Symptom Name'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('featured_image')
                ->label('Featured Image'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your symptom export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
