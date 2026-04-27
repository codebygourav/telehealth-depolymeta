<?php

namespace App\Filament\Resources\ContactUs\Pages;

use App\Filament\Resources\ContactUs\ContactUsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactUs extends ViewRecord
{
    protected static string $resource = ContactUsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
