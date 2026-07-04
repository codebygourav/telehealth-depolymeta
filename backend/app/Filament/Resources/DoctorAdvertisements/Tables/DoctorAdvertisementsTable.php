<?php

namespace App\Filament\Resources\DoctorAdvertisements\Tables;

use BackedEnum;
use App\Enums\DisplayEventCategory;
use App\Enums\DisplayMediaType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DoctorAdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => storage_url($record->image))
                    ->height(80)
                    ->width(120)
                    ->extraImgAttributes(['class' => 'object-cover rounded-lg']),
                TextColumn::make('title')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->limit(40),
                TextColumn::make('media_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        return DisplayMediaType::normalize((string) $state)?->label() ?? 'Not set';
                    })
                    ->colors([
                        'primary' => 'image',
                        'warning' => 'video',
                        'success' => 'youtube',
                        'info' => 'instagram',
                        'gray' => 'link',
                        'secondary' => 'note',
                    ]),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof BackedEnum ? $state->value : (string) $state;
                        return DisplayEventCategory::tryFrom($value)?->label() ?? str($value)->replace('_', ' ')->title()->toString();
                    })
                    ->colors([
                        'primary' => 'advertisement',
                        'success' => 'event',
                        'info' => 'info',
                        'warning' => 'announcement',
                        'gray' => 'notice',
                    ]),
                TextColumn::make('doctors')
                    ->label('Doctors')
                    ->formatStateUsing(fn ($record) => $record->doctors->isNotEmpty()
                        ? $record->doctors->map(fn ($doctor) => trim('Dr. ' . trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''))))->implode(', ')
                        : 'All Doctors')
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Published'),
                TextColumn::make('display_order')
                    ->sortable()
                    ->label('Order'),
                TextColumn::make('starts_at')
                    ->dateTime('M d, Y')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->label('Updated'),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(collect(DisplayEventCategory::cases())
                        ->mapWithKeys(fn (DisplayEventCategory $category) => [$category->value => $category->label()])
                        ->toArray()),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Published',
                        '0' => 'Draft',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('is_active', (bool) $value);
                    }),
                TrashedFilter::make()
                    ->label('Deleted records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),
                    ViewAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->recordAction('edit')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
