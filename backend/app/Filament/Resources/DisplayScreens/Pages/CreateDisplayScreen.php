<?php

namespace App\Filament\Resources\DisplayScreens\Pages;

use App\Filament\Resources\DisplayScreens\DisplayScreenResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateDisplayScreen extends CreateRecord
{
    protected static string $resource = DisplayScreenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

        return $data;
    }
}
