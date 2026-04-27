<?php

namespace App\Filament\Exports;

use App\Models\Medicine;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class MedicineExporter extends Exporter
{
    protected static ?string $model = Medicine::class;

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
                ->label('Medicine Name'),
            ExportColumn::make('category.name')
                ->label('Category'),
            ExportColumn::make('type.name')
                ->label('Type'),
            ExportColumn::make('hospital_stock')
                ->label('Hospital Stock'),
            ExportColumn::make('quantity')
                ->label('Quantity'),
            ExportColumn::make('batch_number')
                ->label('Batch Number'),
            ExportColumn::make('manufactured_date')
                ->label('Manufactured Date'),
            ExportColumn::make('expiry_date')
                ->label('Expiry Date'),
            ExportColumn::make('manufacturer')
                ->label('Manufacturer'),
            ExportColumn::make('price')
                ->label('Price'),
            ExportColumn::make('description')
                ->label('Description'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your medicine export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
