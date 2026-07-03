<?php

namespace App\Filament\Resources\DoctorAdvertisements\Schemas;

use BackedEnum;
use App\Enums\DisplayEventCategory;
use App\Enums\DisplayMediaType;
use App\Models\DisplayEvent;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DoctorAdvertisementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('category')
                ->badge()
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof BackedEnum ? $state->value : (string) $state;

                    return DisplayEventCategory::tryFrom($value)?->label() ?? str($value)->replace('_', ' ')->title()->toString();
                })
                ->placeholder('-'),
            TextEntry::make('media_type')
                ->label('Media Type')
                ->badge()
                ->formatStateUsing(fn ($state): string => DisplayMediaType::normalize((string) $state)?->label() ?? '-')
                ->placeholder('-'),
            TextEntry::make('description')
                ->placeholder('-')
                ->html()
                ->columnSpanFull(),
            TextEntry::make('link')
                ->label('Media URL / Link')
                ->placeholder('-'),
            TextEntry::make('doctors')
                ->label('Target Doctors')
                ->formatStateUsing(function (DisplayEvent $record): string {
                    $doctors = $record->doctors->map(function ($doctor): string {
                        $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
                        $label = $name !== '' ? $name : ($doctor->user?->name ?? 'Doctor');

                        return 'Dr. ' . $label;
                    });

                    return $doctors->isNotEmpty() ? $doctors->implode(', ') : 'All Doctors';
                })
                ->columnSpanFull(),
            IconEntry::make('is_active')->boolean(),
            TextEntry::make('display_order')->placeholder('-'),
            TextEntry::make('starts_at')->dateTime()->placeholder('-'),
            TextEntry::make('ends_at')->dateTime()->placeholder('-'),
            IconEntry::make('autoplay')->boolean(),
            IconEntry::make('loop')->boolean(),
            IconEntry::make('muted')->boolean(),
            TextEntry::make('created_at')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->dateTime()->placeholder('-'),
            TextEntry::make('deleted_at')
                ->dateTime()
                ->visible(fn (DisplayEvent $record): bool => $record->trashed()),
        ]);
    }
}
