<?php

namespace App\Filament\Resources\DoctorReviews\Pages;

use App\Filament\Resources\DoctorReviews\DoctorReviewResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDoctorReview extends ViewRecord
{
    protected static string $resource = DoctorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
