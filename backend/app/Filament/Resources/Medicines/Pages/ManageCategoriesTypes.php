<?php

namespace App\Filament\Resources\Medicines\Pages;

use Filament\Schemas\Components\{Section, Grid, Actions};
use Filament\Actions\Action;
use Filament\Forms\Components\{
    Checkbox,
    TextInput,
    Hidden,
    Repeater,
    Placeholder,
};
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use App\Models\MedicineCategory;
use App\Models\MedicineType;
use App\Forms\Components\CategoryTypeTableRow;
use Illuminate\Support\Str;

class ManageCategoriesTypes
{
    /**
     * Create a reusable table schema for categories/types using custom Blade component
     */
    private static function getTableSchema(string $itemKey, string $selectedField = 'selected'): array
    {
        $isCategory = $itemKey === 'categories';

        return [
            Hidden::make('id'),
            Hidden::make('is_editing')->default(false),
            Hidden::make('slug'),
            Hidden::make('name'),

            CategoryTypeTableRow::make('table_row')
                ->viewData([
                    'itemKey' => $itemKey,
                    'selectedField' => $selectedField,
                    'isCategory' => $isCategory,
                ])
                ->columnSpanFull(),
        ];
    }

    public static function manageCategoriesAction(): Action
    {
        return Action::make('manageCategories')
            ->label('Manage Categories')
            ->icon('heroicon-o-tag')
            ->color('primary')
            ->slideOver()

            ->modalWidth('6xl')
            ->form([
                Section::make('')
                    ->description('Category is the main section of a medicine, like Fever, Diabetes, or Vitamins.')
                    ->headerActions([
                        Action::make('add_category')
                            ->label('Add New Category')
                            ->icon('heroicon-o-plus')
                            ->color('primary')
                            ->button()
                            ->action(fn($set) => $set('show_add_form', true))
                            ->visible(fn($get) => ! $get('show_add_form')),
                    ])
                    ->schema([

                        Grid::make(2)
                            ->visible(fn($get) => $get('show_add_form'))
                            ->schema([
                                TextInput::make('new_category_name')
                                    ->label('Category Name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn($state, $set) =>
                                        $set('new_category_slug', Str::slug($state))
                                    ),
                            ]),
                        Repeater::make('categories')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible(false)
                            ->itemLabel(fn(array $state): string => '')
                            ->columns(12)
                            ->extraAttributes(['class' => 'category-type-table-wrapper'])
                            ->schema(self::getTableSchema('categories', 'selected')),
                    ]),
            ])
            ->fillForm(fn() => [
                'show_add_form' => false,
                'categories' => MedicineCategory::withoutTrashed()
                    ->orderBy('name')
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'selected' => false,
                        'is_editing' => false,
                    ])
                    ->toArray(),
            ])
            ->action(function (array $data) {
                if (! empty($data['new_category_name'])) {
                    MedicineCategory::create([
                        'name' => $data['new_category_name'],
                    ]);
                }

                Notification::make()
                    ->title('Categories updated successfully')
                    ->success()
                    ->send();
            });
    }

    public static function manageTypesAction(): Action
    {
        return Action::make('manageTypes')
            ->label('Manage Types')
            ->icon('heroicon-o-squares-2x2')
            ->color('primary')
            ->slideOver()
            ->modalWidth('6xl')
            ->form([
                Section::make('')
                    ->description('Type shows the form of the medicine, such as Tablet, Syrup, or Injection.')
                    ->headerActions([
                        Action::make('add_type')
                            ->label('Add New Type')
                            ->icon('heroicon-o-plus')
                            ->color('primary')
                            ->button()
                            ->action(fn($set) => $set('show_add_form', true))
                            ->visible(fn($get) => ! $get('show_add_form')),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->visible(fn($get) => $get('show_add_form'))
                            ->schema([
                                TextInput::make('new_type_name')
                                    ->label('Type Name')
                                    ->required()
                                    ->live(onBlur: true),

                            ]),

                        Checkbox::make('select_all')
                            ->label('')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $types = $get('types') ?? [];
                                foreach ($types as $i => $row) {
                                    if (isset($row['id'])) {
                                        $set("types.$i.selected", $state);
                                    }
                                }
                            }),

                        Repeater::make('types')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible(false)
                            ->itemLabel(fn(array $state): string => '')
                            ->columns(12)
                            ->extraAttributes(['class' => 'category-type-table-wrapper'])
                            ->schema(self::getTableSchema('types', 'selected')),

                        Actions::make([
                            Action::make('bulk_delete')
                                ->label('Delete Selected')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->visible(function ($get) {
                                    $types = $get('types') ?? [];
                                    return collect($types)->contains(fn($t) => !empty($t['selected']));
                                })
                                ->action(function ($get, $set) {
                                    $types = $get('types') ?? [];
                                    $ids = collect($types)
                                        ->filter(fn($t) => !empty($t['selected']))
                                        ->pluck('id')
                                        ->filter();

                                    if ($ids->isNotEmpty()) {
                                        // Force delete to permanently remove from database
                                        MedicineType::whereIn('id', $ids)->forceDelete();

                                        // Remove deleted items from form data
                                        $types = $get('types') ?? [];
                                        $remainingTypes = collect($types)
                                            ->filter(fn($t) => !in_array($t['id'] ?? null, $ids->toArray()))
                                            ->values()
                                            ->toArray();

                                        $set('types', $remainingTypes);

                                        Notification::make()
                                            ->title('Types deleted successfully')
                                            ->success()
                                            ->send();
                                    }
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Delete Selected Types')
                                ->modalDescription('Are you sure you want to permanently delete the selected types? This action cannot be undone.')
                                ->modalSubmitActionLabel('Yes, Delete')
                                ->modalCancelActionLabel('Cancel'),
                        ]),
                    ]),
            ])
            ->fillForm(fn() => [
                'show_add_form' => false,
                'types' => MedicineType::withoutTrashed()
                    ->orderBy('name')
                    ->get()
                    ->map(fn($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                        'slug' => $t->slug,
                        'selected' => false,
                        'is_editing' => false,
                    ])
                    ->toArray(),
            ])
            ->action(function (array $data) {
                if (! empty($data['new_type_name'])) {
                    MedicineType::create([
                        'name' => $data['new_type_name'],
                    ]);
                }

                Notification::make()
                    ->title('Types updated successfully')
                    ->success()
                    ->send();
            });
    }
}