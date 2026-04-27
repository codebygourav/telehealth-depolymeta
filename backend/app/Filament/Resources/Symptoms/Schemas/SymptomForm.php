<?php

namespace App\Filament\Resources\Symptoms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SymptomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('featured_image')
                    ->label('Featured Image')
                    ->image()
                    ->disk('public')
                    ->directory(fn($record) => 'symtoms/' . ($record->id ?? 'temp'))
                    ->maxSize(2048) // 2MB max file size
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    // Note: optimize() and imageResize methods don't exist in Filament v4
                    // Use imageEditor() if you need image editing capabilities
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
