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
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'image/bmp',
                        'image/svg+xml',
                        'image/tiff',
                        'image/x-icon',
                        'image/heic',
                        'image/heif',
                    ])
                    ->columnSpanFull(),


                Toggle::make('is_active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true)
                    ->required(),
            ]);
    }
}
