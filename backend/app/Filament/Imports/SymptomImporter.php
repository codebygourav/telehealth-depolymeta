<?php

namespace App\Filament\Imports;

use App\Models\Symptom;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SymptomImporter extends Importer
{
    protected static ?string $model = Symptom::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Symptom Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description')
                ->label('Description')
                ->rules(['nullable']),
            ImportColumn::make('slug')
                ->label('Slug')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('featured_image')
                ->label('Featured Image')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Symptom
    {
        $name = trim($this->data['name'] ?? $this->data['Symptom Name'] ?? '');
        $slug = trim($this->data['slug'] ?? $this->data['Slug'] ?? '');

        if (empty($name)) {
            Log::warning('Import: Empty symptom name encountered.');
            return null;
        }

        // Try to find existing by name or slug
        $record = Symptom::where('name', $name)
            ->orWhere('slug', $slug ?: Str::slug($name))
            ->first();

        if ($record) {
            Log::info("Import: Found existing symptom: {$name}");
            return $record;
        }

        return new Symptom([
            'name' => $name,
        ]);
    }

    protected function beforeSave(): void
    {
        // Ensure slug
        if (empty($this->record->slug)) {
            $this->record->slug = Str::slug($this->record->name);
        }

        // Set Audit Fields
        if ($this->import->user) {
            if (!$this->record->exists) {
                $this->record->created_by = $this->import->user->id;
            }
            $this->record->updated_by = $this->import->user->id;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your symptom import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $failedNames = $import->failedRows()
                ->get()
                ->map(function ($failedRow) {
                    $data = $failedRow->data ?? [];
                    return $data['name'] ?? $data['Symptom Name'] ?? 'Unknown record';
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
