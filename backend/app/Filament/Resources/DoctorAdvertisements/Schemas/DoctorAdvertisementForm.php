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
            Hidden::make('placement')
                ->default('doctor_display')
                ->dehydrated(true),
            Hidden::make('autoplay')
                ->default(true)
                ->dehydrated(true),
            Hidden::make('loop')
                ->default(true)
                ->dehydrated(true),
            Hidden::make('muted')
                ->default(true)
                ->dehydrated(true),
            Hidden::make('open_in_new_tab')
                ->default(true)
                ->dehydrated(true),
            Section::make('Essential Content')
                ->description('Keep this simple. Add the content that should appear on the waiting-room screen and only the required media fields will be shown.')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('category')
                            ->label('Content Type')
                            ->options(DisplayEventCategory::options())
                            ->default(DisplayEventCategory::ADVERTISEMENT->value)
                            ->required()
                            ->live()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Choose content type')
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state && ($category = DisplayEventCategory::tryFrom($state))) {
                                    $set('media_type', $category->defaultMediaType()->value);
                                }
                            })
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Published')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('gray'),
                    ]),
                    TextInput::make('title')
                        ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->titleLabel() ?? 'Title')
                        ->placeholder(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->titlePlaceholder() ?? 'Example: Pregnancy Awareness Campaign')
                        ->helperText('Use a short heading that is easy to read from a distance.')
                        ->required()
                        ->columnSpanFull(),
                    RichEditor::make('description')
                        ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->descriptionLabel() ?? 'Content')
                        ->placeholder('Add the notice, event details, or awareness message to show on the display.')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'link',
                        ])
                        ->helperText('Keep the message short and screen-friendly.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Media')
                ->description('Only show the media input that matches this content type.')
                ->visible(fn (Get $get): bool => ! in_array(
                    DisplayEventCategory::tryFrom((string) $get('category')),
                    [
                        DisplayEventCategory::ANNOUNCEMENT,
                        DisplayEventCategory::NOTICE,
                        DisplayEventCategory::EMERGENCY_ALERT,
                    ],
                    true,
                ))
                ->schema([
                    Select::make('media_type')
                        ->label('Display Format')
                        ->options(DisplayMediaType::options())
                        ->placeholder('Choose media type')
                        ->default(DisplayEventCategory::ADVERTISEMENT->defaultMediaType()->value)
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->required(),
                    FileUpload::make('image')
                        ->label('Banner / Visual')
                        ->helperText('Upload an image only when this content needs a banner or poster.')
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
                        ->helperText('Use this only for video, website, Instagram, or registration links.')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => DisplayEventCategory::tryFrom((string) $get('category'))?->showsLinkField(DisplayMediaType::normalize((string) $get('media_type'))) ?? true),
                ])
                ->columns(1),
            Section::make('Optional Visibility Rules')
                ->description('Use these only when the content should target specific doctors or run during a fixed time window.')
                ->collapsible()
                ->collapsed()
                ->schema([
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
                        ->helperText('Leave empty to show this content for every doctor on the selected display screen.')
                        ->columnSpanFull(),
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
                    TextInput::make('display_order')
                        ->label(fn (Get $get): string => DisplayEventCategory::tryFrom((string) $get('category'))?->orderLabel() ?? 'Display Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Optional. Lower numbers appear earlier in the rotation.'),
                ])
                ->columns(2),
        ]);
    }
}
