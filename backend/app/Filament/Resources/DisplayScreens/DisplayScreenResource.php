<?php

namespace App\Filament\Resources\DisplayScreens;

use App\Filament\Resources\DisplayScreens\Pages\CreateDisplayScreen;
use App\Filament\Resources\DisplayScreens\Pages\EditDisplayScreen;
use App\Filament\Resources\DisplayScreens\Pages\ListDisplayScreens;
use App\Models\DisplayScreen;
use App\Models\Doctor;
use App\Traits\HasCustomSidebar;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisplayScreenResource extends Resource
{
    use HasCustomSidebar;

    protected static ?string $model = DisplayScreen::class;

    protected static ?string $navigationLabel = 'Display Screens';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tv';

    protected static string|\UnitEnum|null $navigationGroup = 'Token Queue Display';

    protected static ?int $navigationSort = 96;

    protected static ?string $modelLabel = 'Display Screen';

    protected static ?string $pluralModelLabel = 'Display Screens';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Display Screens',
            'icon' => 'heroicon-o-tv',
            'sort' => 96,
            'group' => 'Token Queue Display',
            'visible' => true,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Screen Profile')
                ->description('Basic identification for this display screen.')
                ->schema([
                    TextInput::make('name')
                        ->label('Screen Name')
                        ->placeholder('e.g. Building A Reception')
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->columnSpan(1),
                    TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->placeholder('e.g. building-a-reception')
                        ->helperText('Screen public URL: <b>/opd-token/{slug}</b>. Only letters, numbers, and dashes.')
                        ->columnSpan(1),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->placeholder('Details about this display, location, or other notes...')
                        ->columnSpan(2),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline()
                        ->helperText('Inactive screens are hidden from display list.'),
                ])
                ->columns([
                    'sm' => 2,
                    'md' => 2,
                    'xl' => 2,
                ])
                ->columnSpan('full')
                ->compact(),

            Section::make('Access Control')
                ->description('Authentication and location for this screen.')
                ->schema([
                    TextInput::make('settings.password')
                        ->label('Screen Password')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->placeholder('••••••••')
                        ->helperText('Required for protected displays. Leave blank for public access.'),
                    TextInput::make('settings.screen_location')
                        ->label('Screen Location')
                        ->placeholder('e.g. Ground Floor OPD'),
                ])
                ->columns([
                    'sm' => 2,
                    'md' => 2,
                ])
                ->compact(),

            Section::make('Doctor Scope')
                ->description('Select which doctors are visible on this display.')
                ->schema([
                    Select::make('settings.doctor_mode')
                        ->label('Doctor Selection')
                        ->options([
                            'all' => 'All Active Doctors',
                            'single' => 'Single Doctor Only',
                            'multiple' => 'Hand-picked Doctors',
                        ])
                        ->default('all')
                        ->native(false)
                        ->required(),
                    Toggle::make('settings.show_doctor_list_from_appointments')
                        ->label('Show Only With Appointments Today')
                        ->onColor('primary')
                        ->offColor('gray')
                        ->default(false)
                        ->inline()
                        ->helperText('Only show doctors who have active appointments today.'),
                    Select::make('settings.selected_doctors')
                        ->label('Select Doctor(s)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => static::doctorOptions())
                        ->helperText('Choose one or more doctors to limit visibility.'),
                ])
                ->columns([
                    'sm' => 2,
                    'md' => 2,
                ])
                ->compact(),

            Section::make('Display Behavior')
                ->description('Set layout and automatic screen actions.')
                ->schema([
                    Select::make('settings.display_mode')
                        ->label('Screen Layout Mode')
                        ->options([
                            'auto' => 'Auto Detect',
                            'split_ads' => '50/50 Doctor Card + Ads',
                            'grid_modal_ads' => 'Doctor Grid + Modal Ads',
                            'doctor_schedule_sidebar' => 'Doctor OPD + Schedule Sidebar',
                            'events_only' => 'Events / Announcements Only',
                        ])
                        ->default('auto')
                        ->native(false)
                        ->required(),
                    Select::make('settings.same_time_card_columns')
                        ->label('Max Doctor Grid Columns')
                        ->options([
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                        ])
                        ->default('2')
                        ->native(false),
                    TextInput::make('settings.refresh_seconds')
                        ->label('Live Refresh (sec)')
                        ->numeric()
                        ->minValue(10)
                        ->maxValue(300)
                        ->default(30)
                        ->helperText('How often the screen auto-refreshes.'),
                    TextInput::make('settings.doctor_rotation_seconds')
                        ->label('Doctor Rotation (sec)')
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(300)
                        ->default(12)
                        ->helperText('Time before switching to next doctor.'),
                    Toggle::make('settings.popup_enabled')
                        ->label('Patient Popup')
                        ->inline()
                        ->default(true),
                    TextInput::make('settings.popup_duration_seconds')
                        ->label('Patient Popup Duration (sec)')
                        ->numeric()
                        ->minValue(3)
                        ->maxValue(30)
                        ->default(8)
                        ->helperText('How long the next-patient popup stays visible.'),
                    Toggle::make('settings.ad_popup_enabled')
                        ->label('Ad Popup')
                        ->inline()
                        ->default(true),
                    Toggle::make('settings.show_ads_panel')
                        ->label('Show Ads Panel')
                        ->inline()
                        ->default(true),
                    Toggle::make('settings.voice_enabled')
                        ->label('Voice Announcement')
                        ->inline()
                        ->default(true),
                ])
                ->columns([
                    'sm' => 2,
                    'md' => 2,
                ])
                ->compact(),

            Section::make('Screen Copy')
                ->description('Text message shown at the bottom or top of the screen.')
                ->schema([
                    Textarea::make('settings.default_notice')
                        ->label('Default Notice')
                        ->rows(2)
                        ->placeholder('e.g. Please keep your token ready. Wait near your assigned OPD room.')
                        ->columnSpanFull(),
                ])
                ->compact(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('settings.screen_location')
                    ->label('Location')
                    ->default('-')
                    ->toggleable(),
                TextColumn::make('settings.doctor_mode')
                    ->label('Doctor Scope')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'single' => 'Single Doctor',
                        'multiple' => 'Selected Doctors',
                        default => 'All Active Doctors',
                    }),
                TextColumn::make('selected_doctor_count')
                    ->label('Selected')
                    ->state(fn (DisplayScreen $record): int => count($record->settings['selected_doctors'] ?? [])),
                IconColumn::make('settings.show_doctor_list_from_appointments')
                    ->label('Today Only')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('display_url')
                    ->label('Display Screen')
                    ->state(fn (DisplayScreen $record): string => 'Open Screen →')
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (DisplayScreen $record): string => route('opd-token.screen.display', ['screen' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('is_active', (bool) $value);
                    }),
                SelectFilter::make('doctor_scope')
                    ->label('Doctor Scope')
                    ->options([
                        'all' => 'All Active Doctors',
                        'single' => 'Single Doctor',
                        'multiple' => 'Selected Doctors',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->where('settings->doctor_mode', $value);
                    }),
                TrashedFilter::make()
                    ->label('Deleted records'),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisplayScreens::route('/'),
            'create' => CreateDisplayScreen::route('/create'),
            'edit' => EditDisplayScreen::route('/{record}/edit'),
        ];
    }

    protected static function doctorOptions(): array
    {
        return Doctor::query()
            ->with('user:id,name')
            ->active()
            ->withoutTestDoctors()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Doctor $doctor) => [
                $doctor->id => 'Dr. ' . ($doctor->user?->name ?: trim($doctor->first_name . ' ' . $doctor->last_name)),
            ])
            ->toArray();
    }
}