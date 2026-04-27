<?php

namespace App\Filament\Resources\DoctorReviews\Schemas;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class DoctorReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('review')
                    ->view('filament.doctor-reviews.review-infolist')
                    ->state(fn($record) => $record)
                    ->columnSpanFull(),
            ]);
    }
}
