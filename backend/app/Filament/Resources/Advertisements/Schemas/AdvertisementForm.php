<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('link')
                    ->required(),
                RichEditor::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Advertisement Image')
                    ->disk('public')
                    ->directory('advertisements')
                    ->visibility('public')
                    ->image()
                    ->maxSize(10240) // 10 MB — matches Livewire temp upload limit
                    ->fetchFileInformation(false) // avoid flaky exists/mime checks on shared hosting
                    ->orientImagesFromExif(false) // EXIF extension often disabled on shared hosts
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true)
                    ->required(),
            ]);
    }
}
