<?php

namespace App\Filament\Imports;

use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\MedicineType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MedicineImporter extends Importer
{
    protected static ?string $model = Medicine::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Medicine Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('category')
                ->label('Category')
                ->rules(['required'])
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('type')
                ->label('Type')
                ->rules(['required'])
                ->fillRecordUsing(fn() => null),
            ImportColumn::make('hospital_stock')
                ->label('Hospital Stock')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('batch_number')
                ->label('Batch Number')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('manufactured_date')
                ->label('Manufactured Date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('expiry_date')
                ->label('Expiry Date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('manufacturer')
                ->label('Manufacturer')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('price')
                ->label('Price')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('description')
                ->label('Description')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Medicine
    {
        $name = trim($this->data['name'] ?? $this->data['Medicine Name'] ?? '');

        if (empty($name)) {
            Log::warning('Import: Empty medicine name encountered.');
            return null;
        }

        // Try to find existing by name
        $record = Medicine::where('name', $name)->first();

        if ($record) {
            Log::info("Import: Found existing medicine: {$name}");
            return $record;
        }

        return new Medicine([
            'name' => $name,
        ]);
    }

    protected function beforeSave(): void
    {
        // Handle Category Lookup
        $categoryName = trim($this->data['category'] ?? $this->data['Category'] ?? '');
        // Log::info("Import: Processing category: '{$categoryName}'");

        if (!empty($categoryName)) {
            $category = MedicineCategory::where('name', $categoryName)
                ->orWhere('name', 'LIKE', $categoryName)
                ->first();

            if (!$category) {
                // Log::info("Import: Creating new category: {$categoryName}");
                $category = MedicineCategory::create([
                    'name' => $categoryName,
                    'created_by' => $this->import->user->id ?? null,
                ]);
            }
            $this->record->category_id = $category->id;
        }

        // Handle Type Lookup
        $typeName = trim($this->data['type'] ?? $this->data['Type'] ?? '');
        // Log::info("Import: Processing type: '{$typeName}'");

        if (!empty($typeName)) {
            $type = MedicineType::where('name', $typeName)
                ->orWhere('name', 'LIKE', $typeName)
                ->first();

            if (!$type) {
                // Log::info("Import: Creating new type: {$typeName}");
                $type = MedicineType::create([
                    'name' => $typeName,
                    'created_by' => $this->import->user->id ?? null,
                ]);
            }
            $this->record->type_id = $type->id;
        }

        // IMPORTANT: Explicitly remove phantom columns that cause SQL crashes
        unset($this->record->category);
        unset($this->record->type);

        // Ensure slug
        if (empty($this->record->slug)) {
            $this->record->slug = Str::slug($this->record->name);
        }

        // Set Audit Fields for background jobs
        if ($this->import->user) {
            if (!$this->record->exists) {
                $this->record->created_by = $this->import->user->id;
            }
            $this->record->updated_by = $this->import->user->id;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your medicine import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $failedNames = $import->failedRows()
                ->get()
                ->map(function ($failedRow) {
                    $data = $failedRow->data ?? [];
                    return $data['name'] ?? $data['Medicine Name'] ?? 'Unknown record';
                })
                ->filter()
                ->unique()
                ->take(5)
                ->join(', ');

            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';

            if ($failedNames) {
                $body .= " (Failed: {$failedNames}" . ($failedRowsCount > 5 ? '...' : '') . ")";
            }
        }

        return $body;
    }
}
