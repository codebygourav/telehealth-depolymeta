<?php

namespace App\Filament\Resources\Advertisements\Tables;

use App\Filament\Resources\Advertisements\AdvertisementResource;
use function App\Helpers\getUserAuditColumn;
use Filament\Tables\Columns\{IconColumn, ImageColumn, TextColumn};
use Filament\Tables\Columns\Layout\{Stack, Split, Panel};
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\{Action, ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction, EditAction};

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'class' => 'advertisements-grid-table',
            ])
            ->columns([
                Stack::make([
                    Stack::make([
                        ImageColumn::make('image')
                            ->disk('public')
                            ->getStateUsing(fn($record) => storage_url($record->image ?? $record->advertisement))
                            ->height(150)
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-contain w-full rounded-t-lg']),
                    ])->extraAttributes(['class' => 'relative']),

                    Stack::make([
                        TextColumn::make('title')
                            ->weight(FontWeight::Bold)
                            ->size('md')
                            ->searchable()
                            ->limit(40)
                            ->extraAttributes(fn() => [
                                'class' => 'line-clamp-1', // Ensure this utility class exists in your Tailwind config
                                // You can debug Tailwind's generated output or check your build pipeline
                            ]),

                        TextColumn::make('description')
                            ->color('gray')
                            ->size('xs')
                            ->limit(60)
                            ->wrap()
                            ->extraAttributes(fn() => [
                                'class' => 'line-clamp-1',
                            ]),


                        Split::make([
                            TextColumn::make('link')
                                ->label('Action')
                                ->formatStateUsing(fn() => 'Visit ↗')
                                ->url(fn($record) => $record->link)
                                ->openUrlInNewTab()
                                ->color('primary')
                                ->weight(FontWeight::Medium)
                                ->size('xs')
                                ->grow(false),
                            TextColumn::make('created_at')
                                ->dateTime('M d, Y')
                                ->size('xs')
                                ->color('gray')
                                ->extraAttributes(['class' => 'text-right']),
                        ])->extraAttributes(['class' => 'mt-auto border-t border-gray-200 pt-2 flex justify-between items-center']),
                    ])->extraAttributes(['class' => 'p-3 flex-1 flex flex-col gap-1 ']),
                ])
                    ->extraAttributes([
                        'class' =>
                        'bg-white dark:bg-gray-900 relative cursor-pointer ', // Add cursor-pointer for UX
                    ])
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->filters([
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),
                    DeleteAction::make(),
                ])
                    ->extraAttributes([
                        'class' => 'hidden',
                    ])
            ])
            ->recordAction('edit')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
