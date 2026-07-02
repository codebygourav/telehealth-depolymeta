<?php

namespace App\Filament\Resources\DoctorAdvertisements\Schemas;

use App\Enums\DisplayEventCategory;
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
                            if (in_array($state, [
                                DisplayEventCategory::ANNOUNCEMENT->value,
                                DisplayEventCategory::NOTICE->value,
                                DisplayEventCategory::EMERGENCY_ALERT->value,
                            ], true)) {
                                $set('media_type', 'note');
                            }

                            if (in_array($state, [
                                DisplayEventCategory::EVENT->value,
                                DisplayEventCategory::HEALTH_CAMP->value,
                                DisplayEventCategory::VACCINATION_CAMPAIGN->value,
                                DisplayEventCategory::BLOOD_DONATION_CAMP->value,
                            ], true)) {
                                $set('media_type', 'image');
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
                            ->label(fn (Get $get): string => self::titleLabel($get('category')))
                            ->placeholder(fn (Get $get): string => self::titlePlaceholder($get('category')))
                            ->helperText('Use a short heading that reads clearly on a public waiting-screen display.')
                            ->required(),
                        Select::make('media_type')
                            ->label('Media Type')
                            ->options(fn (Get $get): array => self::mediaTypeOptions($get('category')))
                            ->placeholder('Choose media type')
                            ->default('image')
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
                        ->visible(fn (Get $get): bool => self::showsImageField($get('category'), $get('media_type'))),
                    TextInput::make('link')
                        ->label(fn (Get $get): string => self::linkLabel($get('category'), $get('media_type')))
                        ->placeholder(fn (Get $get): string => self::linkPlaceholder($get('category'), $get('media_type')))
                        ->helperText('Used for video embeds, YouTube, registration links, and website targets.')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => self::showsLinkField($get('category'), $get('media_type'))),
                    RichEditor::make('description')
                        ->label(fn (Get $get): string => self::descriptionLabel($get('category')))
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
                            ->label(fn (Get $get): string => self::orderLabel($get('category')))
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
                            ->label(fn (Get $get): string => self::startLabel($get('category')))
                            ->seconds(false)
                            ->placeholder('Optional start date and time'),
                        DateTimePicker::make('ends_at')
                            ->label(fn (Get $get): string => self::endLabel($get('category')))
                            ->seconds(false)
                            ->placeholder('Optional end date and time'),
                    ])->visible(fn (Get $get): bool => self::showsScheduleFields($get('category'))),
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
                    ])->visible(fn (Get $get): bool => self::showsPlaybackOptions($get('category'), $get('media_type'))),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function titleLabel(?string $category): string
    {
        return match ($category) {
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value,
            DisplayEventCategory::VACCINATION_CAMPAIGN->value,
            DisplayEventCategory::BLOOD_DONATION_CAMP->value => 'Event Name',
            default => 'Title',
        };
    }

    protected static function titlePlaceholder(?string $category): string
    {
        return match ($category) {
            DisplayEventCategory::EVENT->value => 'Example: Free Cardiology Camp This Friday',
            DisplayEventCategory::HEALTH_AWARENESS->value => 'Example: Diabetes Awareness Week',
            DisplayEventCategory::ANNOUNCEMENT->value => 'Example: OPD Counter Shifted to Block B',
            DisplayEventCategory::EMERGENCY_ALERT->value => 'Example: Emergency Exit Drill in Progress',
            default => 'Example: Pregnancy Awareness Campaign',
        };
    }

    protected static function mediaTypeOptions(?string $category): array
    {
        return match ($category) {
            DisplayEventCategory::ANNOUNCEMENT->value,
            DisplayEventCategory::NOTICE->value,
            DisplayEventCategory::EMERGENCY_ALERT->value => [
                'note' => 'Text notice',
                'image' => 'Image banner',
            ],
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value,
            DisplayEventCategory::VACCINATION_CAMPAIGN->value,
            DisplayEventCategory::BLOOD_DONATION_CAMP->value => [
                'image' => 'Image banner',
                'video' => 'Video file / embed',
                'youtube' => 'YouTube link',
                'link' => 'Registration / website link',
                'note' => 'Text notice',
            ],
            default => [
                'image' => 'Image banner',
                'video' => 'Video file / embed',
                'youtube' => 'YouTube link',
                'instagram' => 'Instagram link',
                'link' => 'Website link',
                'note' => 'Text note',
            ],
        };
    }

    protected static function showsImageField(?string $category, ?string $mediaType): bool
    {
        if ($mediaType === 'image' || blank($mediaType)) {
            return true;
        }

        return in_array($category, [
            DisplayEventCategory::ADVERTISEMENT->value,
            DisplayEventCategory::HEALTH_AWARENESS->value,
            DisplayEventCategory::DOCTOR_PROMOTION->value,
            DisplayEventCategory::DEPARTMENT_PROMOTION->value,
        ], true);
    }

    protected static function showsLinkField(?string $category, ?string $mediaType): bool
    {
        if (in_array($mediaType, ['video', 'youtube', 'instagram', 'link'], true)) {
            return true;
        }

        return in_array($category, [
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value,
            DisplayEventCategory::INFO->value,
            DisplayEventCategory::VACCINATION_CAMPAIGN->value,
            DisplayEventCategory::BLOOD_DONATION_CAMP->value,
        ], true);
    }

    protected static function linkLabel(?string $category, ?string $mediaType): string
    {
        return match (true) {
            $mediaType === 'youtube' => 'YouTube URL',
            in_array($category, [
                DisplayEventCategory::EVENT->value,
                DisplayEventCategory::HEALTH_CAMP->value,
                DisplayEventCategory::VACCINATION_CAMPAIGN->value,
                DisplayEventCategory::BLOOD_DONATION_CAMP->value,
            ], true) => 'Registration / Event URL',
            default => 'Media URL / Link',
        };
    }

    protected static function linkPlaceholder(?string $category, ?string $mediaType): string
    {
        return match (true) {
            $mediaType === 'youtube' => 'https://www.youtube.com/watch?v=...',
            $mediaType === 'video' => 'https://example.com/video.mp4',
            in_array($category, [
                DisplayEventCategory::EVENT->value,
                DisplayEventCategory::HEALTH_CAMP->value,
                DisplayEventCategory::VACCINATION_CAMPAIGN->value,
                DisplayEventCategory::BLOOD_DONATION_CAMP->value,
            ], true) => 'https://example.com/register',
            default => 'Paste an external URL',
        };
    }

    protected static function descriptionLabel(?string $category): string
    {
        return match ($category) {
            DisplayEventCategory::ANNOUNCEMENT->value,
            DisplayEventCategory::NOTICE->value,
            DisplayEventCategory::EMERGENCY_ALERT->value => 'Message',
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value => 'Description',
            default => 'Content',
        };
    }

    protected static function orderLabel(?string $category): string
    {
        return $category === DisplayEventCategory::EMERGENCY_ALERT->value
            ? 'Priority Order'
            : 'Display Order';
    }

    protected static function startLabel(?string $category): string
    {
        return in_array($category, [
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value,
            DisplayEventCategory::VACCINATION_CAMPAIGN->value,
            DisplayEventCategory::BLOOD_DONATION_CAMP->value,
        ], true) ? 'Start Date / Time' : 'Starts At';
    }

    protected static function endLabel(?string $category): string
    {
        return in_array($category, [
            DisplayEventCategory::EVENT->value,
            DisplayEventCategory::HEALTH_CAMP->value,
            DisplayEventCategory::VACCINATION_CAMPAIGN->value,
            DisplayEventCategory::BLOOD_DONATION_CAMP->value,
        ], true) ? 'End Date / Time' : 'Ends At';
    }

    protected static function showsScheduleFields(?string $category): bool
    {
        return ! in_array($category, [
            DisplayEventCategory::ANNOUNCEMENT->value,
            DisplayEventCategory::NOTICE->value,
        ], true);
    }

    protected static function showsPlaybackOptions(?string $category, ?string $mediaType): bool
    {
        if (in_array($category, [
            DisplayEventCategory::NOTICE->value,
            DisplayEventCategory::ANNOUNCEMENT->value,
            DisplayEventCategory::EMERGENCY_ALERT->value,
        ], true)) {
            return false;
        }

        return in_array($mediaType, ['video', 'youtube', 'link', 'instagram', 'image', null, ''], true);
    }
}
