<?php

namespace App\Filament\Resources\Medicines\Schemas;

use Filament\Forms\Components\{TextInput, Select};
use Filament\Schemas\Schema;
use App\Models\{MedicineCategory, MedicineType};

class MedicineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                // Fix: Use options() for Select, because relationship() requires the resource to be registered and Eloquent relationships to be defined properly.
                // If it doesn't work, manually fetch options from the models.
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn() => MedicineCategory::all()->pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        $category = MedicineCategory::create($data);
                        return $category->id;
                    }),

                Select::make('type_id')
                    ->label('Type')
                    ->options(fn() => MedicineType::all()->pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Type Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        $type = MedicineType::create($data);
                        return $type->id;
                    }),

                TextInput::make('hospital_stock')
                    ->label('Hospital Stock')
                    ->integer()
                    ->default(0)
                    ->numeric(),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->integer()
                    ->default(1)
                    ->numeric(),

                TextInput::make('batch_number')
                    ->label('Batch Number')
                    ->maxLength(255),

                TextInput::make('manufactured_date')
                    ->label('Manufactured Date')
                    ->type('date'),

                TextInput::make('expiry_date')
                    ->label('Expiry Date')
                    ->type('date'),

                TextInput::make('manufacturer')
                    ->label('Manufacturer')
                    ->maxLength(255),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(1000),
            ]);
    }
}
