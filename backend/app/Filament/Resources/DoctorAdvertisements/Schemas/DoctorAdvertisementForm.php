<?php

namespace App\Filament\Resources\DoctorAdvertisements\Schemas;

use App\Enums\DisplayEventCategory;
use App\Enums\DisplayMediaType;
use App\Models\Doctor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class DoctorAdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Choose Category')
                ->description('Select the screen content type first. The remaining fields adapt to the selected category.')
                ->schema([
                    Select::make('category')
                        ->label('Category')
                        ->options(DisplayEventCategory::options())
                        ->default(DisplayEventCategory::ADVERTISEMENT->value)
                        ->required()
                        ->live()
                        ->searchable()
                        ->placeholder('Choose category')
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state && ($category = DisplayEventCategory::tryFrom($state))) {
                                $set('media_type', $category->defaultMediaType()->value);
                            }
                        }),
                ])
                ->columnSpanFull(),
            Section::make('Content Details')
                ->description('Configure the display content, targeting, scheduling, and playback rules.')
                ->schema([
                    Hidden::make('placement')
                        ->default('doctor_display')
                        ->dehydrated(true),
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->titleLabel() ?? 'Title')
                            ->placeholder(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->titlePlaceholder() ?? 'Example: Pregnancy Awareness Campaign')
                            ->helperText('Use a short heading that reads clearly on a public waiting-screen display.')
                            ->required(),
                        Select::make('media_type')
                            ->label('Media Type')
                            ->options(DisplayMediaType::options())
                            ->placeholder('Choose media type')
                            ->default(DisplayEventCategory::ADVERTISEMENT->defaultMediaType()->value)
                            ->searchable()
                            ->live()
                            ->required(),
                    ]),
                    FileUpload::make('image')
                        ->label('Banner / Visual')
                        ->helperText('Upload the visual shown on the display for image-based content.')
                        ->disk('public')
                        ->directory('display_events')
                        ->visibility('public')
                        ->image()
                        ->maxSize(10240)
                        ->fetchFileInformation(false)
                        ->orientImagesFromExif(false)
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => DisplayEventCategory::tryFrom((string) $get('category'))?->showsImageField(DisplayMediaType::normalize((string) $get('media_type'))) ?? true),
                    TextInput::make('link')
                        ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->linkLabel(DisplayMediaType::normalize((string) $get('media_type'))) ?? 'Media URL / Link')
                        ->placeholder(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->linkPlaceholder(DisplayMediaType::normalize((string) $get('media_type'))) ?? 'Paste an external URL')
                        ->helperText('Used for video embeds, YouTube, registration links, and website targets.')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => DisplayEventCategory::tryFrom((string) $get('category'))?->showsLinkField(DisplayMediaType::normalize((string) $get('media_type'))) ?? true),
                    RichEditor::make('description')
                        ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->descriptionLabel() ?? 'Content')
                        ->placeholder('Add the notice, event details, instructions, or awareness copy to show on the screen.')
                        ->columnSpanFull(),
                    Select::make('doctors')
                        ->label('Target Doctors')
                        ->multiple()
                        ->placeholder('Leave empty for all doctors on the display')
                        ->relationship('doctors', 'first_name')
                        ->getOptionLabelFromRecordUsing(function (Doctor $record): string {
                            $name = trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? ''));
                            $label = $name !== '' ? $name : ($record->user?->name ?? 'Doctor');

                            return 'Dr. ' . $label . ($record->doctor_code ? " ({$record->doctor_code})" : '');
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty to show this content for all doctors on the display screen.')
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextInput::make('display_order')
                            ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->orderLabel() ?? 'Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear earlier in the rotation. Use this as priority ordering.'),
                        Toggle::make('is_active')
                            ->label('Published')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('gray'),
                    ]),
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->startLabel() ?? 'Starts At')
                            ->seconds(false)
                            ->placeholder('Optional start date and time'),
                        DateTimePicker::make('ends_at')
                            ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->endLabel() ?? 'Ends At')
                            ->seconds(false)
                            ->placeholder('Optional end date and time'),
                    ])->visible(fn (Get $get): bool => DisplayEventCategory::tryFrom((string) $get('category'))?->showsScheduleFields() ?? true),
                    Grid::make(4)->schema([
                        Toggle::make('autoplay')
                            ->label('Autoplay')
                            ->default(true),
                        Toggle::make('loop')
                            ->label('Loop')
                            ->default(true),
                        Toggle::make('muted')
                            ->label('Muted')
                            ->default(true),
                        Toggle::make('open_in_new_tab')
                            ->label('Open Link New Tab')
                            ->default(true),
                    ])->visible(fn (Get $get): bool => DisplayEventCategory::tryFrom((string) $get('category'))?->showsPlaybackOptions(DisplayMediaType::normalize((string) $get('media_type'))) ?? false),
                ])
                ->columnSpanFull(),
        ]);
    }
}
