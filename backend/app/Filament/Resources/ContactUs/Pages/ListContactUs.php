<?php

namespace App\Filament\Resources\ContactUs\Pages;

use App\Filament\Resources\ContactUs\ContactUsResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListContactUs extends ListRecords
{
    protected static string $resource = ContactUsResource::class;
    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?string $title = 'All Inquiries';

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make(),
    //     ];
    // }
}
