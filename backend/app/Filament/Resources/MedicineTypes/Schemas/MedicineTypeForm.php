<?php

namespace App\Filament\Resources\MedicineTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MedicineTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
