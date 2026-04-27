<?php

namespace App\Filament\Resources\Medicines\Pages;

use App\Filament\Resources\Medicines\MedicineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Medicines\Pages\ManageCategoriesTypes;
use App\Models\{MedicineCategory, MedicineType};
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use App\Filament\Exports\MedicineExporter;
use App\Filament\Imports\MedicineImporter;

class ListMedicines extends ListRecords
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ManageCategoriesTypes::manageCategoriesAction()
                ->visible(fn() => MedicineResource::canViewAny() || MedicineResource::canCreate()),

            ManageCategoriesTypes::manageTypesAction()
                ->visible(fn() => MedicineResource::canViewAny() || MedicineResource::canCreate()),
            ActionGroup::make([


                ExportAction::make()
                    ->exporter(MedicineExporter::class)
                    ->label('Export')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),

                ImportAction::make()
                    ->importer(MedicineImporter::class)
                    ->label('Import')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-tray'),

                Action::make('downloadExample')
                    ->label('Download Example')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function () {
                        $callback = function () {
                            $headers = ['name', 'category', 'type', 'hospital_stock', 'quantity', 'batch_number', 'manufactured_date', 'expiry_date', 'manufacturer', 'price', 'description'];
                            $exampleRow = ['Paracetamol', 'Analgesics', 'Tablet', '100', '10', 'BN123', '2023-01-01', '2025-01-01', 'GSK', '5.50', 'Fever and pain relief'];

                            $file = fopen('php://output', 'w');
                            fputcsv($file, $headers);
                            fputcsv($file, $exampleRow);
                            fclose($file);
                        };

                        return response()->streamDownload($callback, 'medicine_example.csv');
                    }),
            ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary')
                ->button(),

            CreateAction::make()
                ->slideOver()
                ->visible(fn() => MedicineResource::canCreate()),
        ];
    }

    public function setEditing(string $itemKey, int $recordKey, bool $isEditing): void
    {
        try {
            if (isset($this->mountedActionsData[0][$itemKey][$recordKey])) {
                $this->mountedActionsData[0][$itemKey][$recordKey]['is_editing'] = $isEditing;
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function toggleSelection(string $itemKey, string|int $itemId): void
    {
        try {
            if (!isset($this->mountedActionsData[0][$itemKey])) {
                return;
            }

            // Find the item by ID and toggle its selected state
            foreach ($this->mountedActionsData[0][$itemKey] as $key => &$formItem) {
                if (($formItem['id'] ?? null) == $itemId) {
                    $current = $formItem['selected'] ?? false;
                    $formItem['selected'] = !$current;
                    break;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function updateItemName(string $itemKey, int $recordKey, string $name): void
    {
        try {
            if (!isset($this->mountedActionsData[0][$itemKey])) {
                return;
            }

            // Try to get item by index first
            $item = $this->mountedActionsData[0][$itemKey][$recordKey] ?? null;

            // If not found by index, try to find by iterating
            if (!$item && !empty($this->mountedActionsData[0][$itemKey])) {
                $itemsArray = array_values($this->mountedActionsData[0][$itemKey]);
                $item = $itemsArray[$recordKey] ?? null;
            }

            if ($item) {
                // Find the item in the array and update it
                foreach ($this->mountedActionsData[0][$itemKey] as $key => &$formItem) {
                    if (($formItem['id'] ?? null) == ($item['id'] ?? null)) {
                        $formItem['name'] = $name;
                        $formItem['slug'] = Str::slug($name);
                        break;
                    }
                }
            } else {
                // Fallback: try direct index access
                if (isset($this->mountedActionsData[0][$itemKey][$recordKey])) {
                    $this->mountedActionsData[0][$itemKey][$recordKey]['name'] = $name;
                    $this->mountedActionsData[0][$itemKey][$recordKey]['slug'] = Str::slug($name);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function saveItem(string $itemKey, string|int $itemId, bool $isCategory, string $name): void
    {
        try {
            if (empty($itemId)) {
                Notification::make()
                    ->title('Error: Item ID is required')
                    ->danger()
                    ->send();
                return;
            }

            if (empty($name)) {
                Notification::make()
                    ->title('Error: Name is required')
                    ->danger()
                    ->send();
                return;
            }

            $modelClass = $isCategory ? MedicineCategory::class : MedicineType::class;
            $model = $modelClass::find($itemId);

            if ($model) {
                $model->update([
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);

                // Update form data - find the item by ID and update it
                if (isset($this->mountedActionsData[0][$itemKey])) {
                    foreach ($this->mountedActionsData[0][$itemKey] as $key => &$formItem) {
                        if (($formItem['id'] ?? null) == $itemId) {
                            $formItem['is_editing'] = false;
                            $formItem['name'] = $model->name;
                            $formItem['slug'] = $model->slug;
                            break;
                        }
                    }
                }

                Notification::make()
                    ->title($isCategory ? 'Category updated successfully' : 'Type updated successfully')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error: Record not found in database')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving item: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelEdit(string $itemKey, int $recordKey, bool $isCategory): void
    {
        try {
            $data = $this->mountedActionsData[0] ?? [];
            $items = $data[$itemKey] ?? [];

            // Try to get item by index first
            $item = $items[$recordKey] ?? null;

            // If not found by index, try to find by iterating
            if (!$item && !empty($items)) {
                $itemsArray = array_values($items);
                $item = $itemsArray[$recordKey] ?? null;
            }

            if (!$item) {
                return;
            }

            $itemId = $item['id'] ?? null;
            if (empty($itemId)) {
                return;
            }

            $modelClass = $isCategory ? MedicineCategory::class : MedicineType::class;
            $model = $modelClass::find($itemId);

            if ($model && isset($this->mountedActionsData[0][$itemKey])) {
                // Find and update the item by ID
                foreach ($this->mountedActionsData[0][$itemKey] as $key => &$formItem) {
                    if (($formItem['id'] ?? null) == $itemId) {
                        $formItem['name'] = $model->name;
                        $formItem['slug'] = $model->slug;
                        $formItem['is_editing'] = false;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail on cancel
        }
    }

    public function reloadFormData(string $itemKey, bool $isCategory): void
    {
        try {
            $modelClass = $isCategory ? MedicineCategory::class : MedicineType::class;
            $items = $modelClass::withoutTrashed()
                ->orderBy('name')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'selected' => false,
                    'is_editing' => false,
                ])
                ->toArray();

            // Update mountedActionsData
            if (isset($this->mountedActionsData[0])) {
                $this->mountedActionsData[0][$itemKey] = $items;
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function deleteItem(string $itemKey, string|int $itemId, bool $isCategory): void
    {
        try {
            if (empty($itemId)) {
                Notification::make()
                    ->title('Error: Item ID is required')
                    ->danger()
                    ->send();
                return;
            }

            $modelClass = $isCategory ? MedicineCategory::class : MedicineType::class;
            $model = $modelClass::find($itemId);

            if ($model) {
                // Force delete to permanently remove from database
                $model->forceDelete();

                // Remove item from form data by ID
                if (isset($this->mountedActionsData[0][$itemKey])) {
                    $this->mountedActionsData[0][$itemKey] = array_values(
                        array_filter($this->mountedActionsData[0][$itemKey], function ($i) use ($itemId) {
                            return ($i['id'] ?? null) != $itemId;
                        })
                    );
                }

                $this->dispatch('$refresh');

                Notification::make()
                    ->title($isCategory ? 'Category deleted successfully' : 'Type deleted successfully')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error: Record not found in database')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting item: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
