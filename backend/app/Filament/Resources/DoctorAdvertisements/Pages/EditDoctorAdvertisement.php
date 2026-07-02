<?php

namespace App\Filament\Resources\DoctorAdvertisements\Pages;

use App\Filament\Resources\DoctorAdvertisements\DoctorAdvertisementResource;
use Filament\Resources\Pages\EditRecord;

class EditDoctorAdvertisement extends EditRecord
{
    protected static string $resource = DoctorAdvertisementResource::class;

    protected ?string $heading = 'Edit Display Content';
}
