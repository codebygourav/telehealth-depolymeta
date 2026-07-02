<?php

namespace App\Filament\Resources\DoctorAdvertisements\Pages;

use App\Filament\Resources\DoctorAdvertisements\DoctorAdvertisementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorAdvertisement extends CreateRecord
{
    protected static string $resource = DoctorAdvertisementResource::class;

    protected ?string $heading = 'Create Display Content';
}
