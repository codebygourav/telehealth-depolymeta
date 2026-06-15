<?php

namespace App\Filament\Resources\DoctorReviews\Pages;

use App\Filament\Resources\DoctorReviews\DoctorReviewResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListDoctorReviews extends ListRecords
{
    protected static string $resource = DoctorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
