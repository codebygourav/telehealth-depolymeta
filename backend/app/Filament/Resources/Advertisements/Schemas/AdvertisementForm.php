<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Troubleshooting File Upload + Error on Ad create:
 *
 * If there is an "Error while loading page" or upload issue, ensure:
 * - The 'public' disk is correctly configured and uses the intended base URL. See AppServiceProvider.
 * - Storage permissions are set correctly for 'storage/app/public/advertisements' and 'storage/app/livewire-tmp'.
 * - The webserver can write to storage and symbolic links (`php artisan storage:link`) are configured.
 * - Livewire temporary upload settings (config/livewire.php) match maxSize.
 * - Use safe FileUpload options for shared hosting.
 */

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
                    ->panelAspectRatio('16:9') // Safer aspect ratio for consistent preview
                    ->panelLayout('integrated') // Simpler thumbnail style to avoid render bugs
                    ->removeUploadedFileButtonPosition('top-end') // best UX for preview overlays
                    ->uploadButtonPosition('center')
                    ->uploadProgressIndicatorPosition('center')
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