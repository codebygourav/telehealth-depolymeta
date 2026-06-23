<?php

namespace App\Filament\Resources\MedicineTemplates;

use App\Filament\Resources\MedicineTemplates\Pages\CreateMedicineTemplate;
use App\Filament\Resources\MedicineTemplates\Pages\EditMedicineTemplate;
use App\Filament\Resources\MedicineTemplates\Pages\ListMedicineTemplates;
use App\Filament\Resources\MedicineTemplates\Pages\ViewMedicineTemplate;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\MedicineTemplate;
use App\Models\MedicineTemplateItem;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class MedicineTemplateResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = MedicineTemplate::class;

    protected static ?string $navigationLabel = 'Medicine Templates';

    protected static ?string $slug = 'medicine-templates';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Medicine Templates',
            'icon' => 'heroicon-o-clipboard-document-list',
            'sort' => 4,
            'group' => 'Medicine',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission(['medicine-templates.view_any', 'medicine-templates.view', 'medicine-templates.manage_own'])
            || static::hasMedicineTemplateRole();
    }

    public static function canViewAny(): bool
    {
        return check_permission(['medicine-templates.view_any', 'medicine-templates.view', 'medicine-templates.manage_own'])
            || static::hasMedicineTemplateRole();
    }

    public static function canCreate(): bool
    {
        return check_permission(['medicine-templates.create', 'medicine-templates.manage_own'])
            || static::hasMedicineTemplateRole();
    }

    public static function canEdit($record): bool
    {
        return check_permission('medicine-templates.update')
            || (check_permission('medicine-templates.manage_own') && static::isOwnRecord($record))
            || static::hasMedicineTemplateRole();
    }

    public static function canDelete($record): bool
    {
        return check_permission('medicine-templates.delete_any')
            || (check_permission(['medicine-templates.delete', 'medicine-templates.manage_own']) && static::isOwnRecord($record))
            || static::hasMedicineTemplateAdminRole();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')
                ->description('Create a reusable prescription template for all doctors or one specific doctor.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Template Name')
                                ->placeholder('Fever / Post-op antibiotics / Diabetes follow-up')
                                ->required()
                                ->maxLength(255),
                            Select::make('scope_type')
                                ->label('Scope')
                                ->options(static::scopeOptions())
                                ->default(MedicineTemplate::SCOPE_GLOBAL)
                                ->required()
                                ->live()
                                ->afterStateHydrated(function ($state, callable $set, ?MedicineTemplate $record): void {
                                    if (! $state && $record) {
                                        $set('scope_type', $record->doctor_id ? MedicineTemplate::SCOPE_DOCTOR : MedicineTemplate::SCOPE_GLOBAL);
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    if ($state === MedicineTemplate::SCOPE_GLOBAL) {
                                        $set('department_id', null);
                                        $set('doctor_id', null);
                                    }

                                    if ($state === MedicineTemplate::SCOPE_DEPARTMENT) {
                                        $set('doctor_id', null);
                                    }
                                }),
                            Select::make('department_id')
                                ->label('Department')
                                ->placeholder('Select department')
                                ->options(fn() => static::departmentOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn($get): bool => in_array($get('scope_type'), [MedicineTemplate::SCOPE_DOCTOR, MedicineTemplate::SCOPE_DEPARTMENT], true))
                                ->required(fn($get): bool => $get('scope_type') === MedicineTemplate::SCOPE_DEPARTMENT)
                                ->dehydrated(fn($get): bool => $get('scope_type') !== MedicineTemplate::SCOPE_GLOBAL)
                                ->afterStateUpdated(fn(callable $set) => $set('doctor_id', null))
                                ->helperText(fn($get): string => $get('scope_type') === MedicineTemplate::SCOPE_DOCTOR
                                    ? 'Optional filter: select a department to show doctors from that department.'
                                    : 'Doctors in this department will see this template.'),
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->placeholder('Select doctor')
                                ->helperText('Only the selected doctor can use this template.')
                                ->options(fn($get) => static::doctorOptions($get('department_id')))
                                ->searchable()
                                ->preload()
                                ->default(fn() => static::defaultDoctorId())
                                ->visible(fn($get): bool => $get('scope_type') === MedicineTemplate::SCOPE_DOCTOR)
                                ->required(fn($get): bool => $get('scope_type') === MedicineTemplate::SCOPE_DOCTOR)
                                ->disabled(fn() => static::isDoctorOnlyUser())
                                ->dehydrated(fn($get): bool => $get('scope_type') === MedicineTemplate::SCOPE_DOCTOR),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Medicines')
                ->description('Add one or more medicines that will be created as prescriptions when the doctor applies this template.')
                ->view('filament.resources.medicine-templates.partials.medicines-section')
                ->schema([
                    Repeater::make('items')
                        ->label('Template Medicines')
                        ->relationship('items')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->cloneable()
                        ->addActionLabel('Add medicine')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            // Resolve duration_value from the duration_preset virtual field
                            $preset = $data['duration_preset'] ?? null;
                            if ($preset === 'none' || $preset === null) {
                                $data['duration_value'] = null;
                            } elseif ($preset !== 'custom' && $preset !== null) {
                                $data['duration_value'] = (int) $preset;
                            }
                            // Remove virtual/helper fields not in DB
                            unset($data['duration_preset'], $data['dosage_custom'], $data['use_type_custom'], $data['take_when_custom'], $data['min_gap_custom'], $data['max_doses_custom']);
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $preset = $data['duration_preset'] ?? null;
                            if ($preset === 'none' || $preset === null) {
                                $data['duration_value'] = null;
                            } elseif ($preset !== 'custom' && $preset !== null) {
                                $data['duration_value'] = (int) $preset;
                            }
                            unset($data['duration_preset'], $data['dosage_custom'], $data['use_type_custom'], $data['take_when_custom'], $data['min_gap_custom'], $data['max_doses_custom']);
                            return $data;
                        })
                        ->itemLabel(fn(array $state): ?string => static::medicineItemLabel($state))
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    Select::make('medicine_id')
                                        ->label('Medicine')
                                        ->placeholder('Select from medicine inventory')
                                        ->options(fn() => static::medicineOptions())
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            $medicine = $state ? Medicine::with('type')->find($state) : null;

                                            if ($medicine) {
                                                $set('medicine_name', $medicine->name);
                                                $set('medicine_type', $medicine->type?->name);
                                                // Trigger dosage options reload
                                                $set('dosage', null);
                                            }
                                        }),
                                    TextInput::make('medicine_name')
                                        ->label('Medicine Name')
                                        ->helperText('Auto-filled from inventory, but editable for custom template medicines.')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('medicine_type')
                                        ->label('Type')
                                        ->placeholder('Tablet / Syrup / Drops')
                                        ->maxLength(255)
                                        ->live(),
                                      Select::make('dosage')
                                        ->label('Dosage')
                                        ->placeholder('Select dosage')
                                        ->options(fn ($get) => \App\Enums\DosagePreset::forType($get('medicine_type')))
                                        ->searchable()
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            if ($state) {
                                                $options = \App\Enums\DosagePreset::forType($get('medicine_type'));
                                                if (!array_key_exists($state, $options)) {
                                                    $set('dosage', 'custom');
                                                    $set('dosage_custom', $state);
                                                }
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state === 'custom' ? $get('dosage_custom') : $state)
                                        ->required(),
                                   
                                ]),
                            Grid::make(5)
                                ->schema([
                                   Select::make('use_type')
                                        ->label('Use Type')
                                        ->options([
                                            'regular' => 'Regular (Scheduled Daily)',
                                            'sos' => 'SOS (Only when needed)',
                                            'custom' => 'Custom...',
                                        ])
                                        ->default('regular')
                                        ->required()
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            if ($state && !in_array($state, ['regular', 'sos'], true)) {
                                                $set('use_type', 'custom');
                                                $set('use_type_custom', $state);
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state === 'custom' ? $get('use_type_custom') : $state)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if ($state === 'sos') {
                                                $set('frequency', 'SOS');
                                                $set('frequency_times', null);
                                                $set('doses_per_day', 0);
                                                $set('dose_interval_hours', 0);
                                                $set('first_dose_time', null);
                                                static::generateSosInstruction($set, $get);
                                            } else {
                                                $set('frequency', 'OD');
                                                $set('doses_per_day', 1);
                                                $set('dose_interval_hours', 24);
                                                $set('first_dose_time', '08:00');
                                                $set('instructions', null);
                                            }
                                        }),
                                    Select::make('meal_timing')
                                        ->label('Meal Timing')
                                        ->options(static::mealOptions())
                                        ->searchable(),

                                    // Regular schedule fields
                                    TextInput::make('doses_per_day')
                                        ->label('Times per day')
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(6)
                                        ->default(1)
                                        ->required(fn ($get) => $get('use_type') !== 'sos')
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            $set('frequency', MedicineTemplateItem::frequencyFromDoses((int) $state));
                                            $set('dose_interval_hours', MedicineTemplateItem::defaultDoseInterval((int) $state));
                                        })
                                        ->visible(fn ($get) => $get('use_type') !== 'sos'),
                                    TimePicker::make('first_dose_time')
                                        ->label('First Dose')
                                        ->seconds(false)
                                        ->default('08:00')
                                        ->live()
                                        ->required(fn ($get) => $get('use_type') !== 'sos')
                                        ->visible(fn ($get) => $get('use_type') !== 'sos'),
                                    TextInput::make('dose_interval_hours')
                                        ->label('Gap Hours')
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(24)
                                        ->default(8)
                                        ->live()
                                        ->required(fn ($get) => $get('use_type') !== 'sos')
                                        ->visible(fn ($get) => $get('use_type') !== 'sos'),

                                    // SOS fields
                                    Select::make('take_when')
                                        ->label('Take When')
                                        ->placeholder('Select reason')
                                        ->options([
                                            'Fever' => 'Fever',
                                            'Pain' => 'Pain',
                                            'Headache' => 'Headache',
                                            'Cough' => 'Cough',
                                            'Acidity' => 'Acidity',
                                            'Vomiting' => 'Vomiting',
                                            'custom' => 'Custom Reason...',
                                        ])
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            $val = $get('take_when');
                                            if ($val && !in_array($val, ['Fever', 'Pain', 'Headache', 'Cough', 'Acidity', 'Vomiting'], true)) {
                                                $set('take_when', 'custom');
                                                $set('take_when_custom', $val);
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state === 'custom' ? $get('take_when_custom') : $state)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if ($state !== 'custom') {
                                                $set('take_when_custom', null);
                                            }
                                            static::generateSosInstruction($set, $get);
                                        })
                                        ->required(fn ($get) => $get('use_type') === 'sos')
                                        ->visible(fn ($get) => $get('use_type') === 'sos'),
                                    Select::make('min_gap')
                                        ->label('Minimum Gap')
                                        ->placeholder('Select gap')
                                        ->options([
                                            '4 hours' => '4 hours',
                                            '6 hours' => '6 hours',
                                            '8 hours' => '8 hours',
                                            '12 hours' => '12 hours',
                                            'custom' => 'Custom Gap...',
                                        ])
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            $val = $get('min_gap');
                                            if ($val && !in_array($val, ['4 hours', '6 hours', '8 hours', '12 hours'], true)) {
                                                $set('min_gap', 'custom');
                                                $set('min_gap_custom', $val);
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state === 'custom' ? $get('min_gap_custom') : $state)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if ($state !== 'custom') {
                                                $set('min_gap_custom', null);
                                            }
                                            static::generateSosInstruction($set, $get);
                                        })
                                        ->visible(fn ($get) => $get('use_type') === 'sos'),
                                    Select::make('max_doses_per_day')
                                        ->label('Max Per Day')
                                        ->placeholder('Select max doses')
                                        ->options([
                                            '1 dose' => '1 dose',
                                            '2 doses' => '2 doses',
                                            '3 doses' => '3 doses',
                                            '4 doses' => '4 doses',
                                            'custom' => 'Custom Max doses...',
                                        ])
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            $val = $get('max_doses_per_day');
                                            if ($val && !in_array($val, ['1 dose', '2 doses', '3 doses', '4 doses'], true)) {
                                                $set('max_doses_per_day', 'custom');
                                                $set('max_doses_custom', $val);
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state === 'custom' ? $get('max_doses_custom') : $state)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if ($state !== 'custom') {
                                                $set('max_doses_custom', null);
                                            }
                                            static::generateSosInstruction($set, $get);
                                        })
                                        ->visible(fn ($get) => $get('use_type') === 'sos'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('dosage_custom')
                                        ->label('Custom Dosage')
                                        ->placeholder('e.g. 10 mcg')
                                        ->visible(fn ($get) => $get('dosage') === 'custom')
                                        ->required(fn ($get) => $get('dosage') === 'custom')
                                        ->dehydrated(false)
                                        ->live(),
                                    TextInput::make('use_type_custom')
                                        ->label('Custom Use Type')
                                        ->placeholder('e.g. Empty stomach')
                                        ->visible(fn ($get) => $get('use_type') === 'custom')
                                        ->required(fn ($get) => $get('use_type') === 'custom')
                                        ->dehydrated(false)
                                        ->live(),
                                    TextInput::make('take_when_custom')
                                        ->label('Custom Reason')
                                        ->placeholder('e.g. Joint pain')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(fn ($state, callable $set, $get) => static::generateSosInstruction($set, $get))
                                        ->visible(fn ($get) => $get('use_type') === 'sos' && $get('take_when') === 'custom')
                                        ->required(fn ($get) => $get('use_type') === 'sos' && $get('take_when') === 'custom'),
                                    TextInput::make('min_gap_custom')
                                        ->label('Custom Gap')
                                        ->placeholder('e.g. 2 hours')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(fn ($state, callable $set, $get) => static::generateSosInstruction($set, $get))
                                        ->visible(fn ($get) => $get('use_type') === 'sos' && $get('min_gap') === 'custom')
                                        ->required(fn ($get) => $get('use_type') === 'sos' && $get('min_gap') === 'custom'),
                                    TextInput::make('max_doses_custom')
                                        ->label('Custom Max Doses')
                                        ->placeholder('e.g. 5 doses')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(fn ($state, callable $set, $get) => static::generateSosInstruction($set, $get))
                                        ->visible(fn ($get) => $get('use_type') === 'sos' && $get('max_doses_per_day') === 'custom')
                                        ->required(fn ($get) => $get('use_type') === 'sos' && $get('max_doses_per_day') === 'custom'),
                                ])
                                ->visible(fn ($get) => $get('dosage') === 'custom' || $get('use_type') === 'custom' || ($get('use_type') === 'sos' && ($get('take_when') === 'custom' || $get('min_gap') === 'custom' || $get('max_doses_per_day') === 'custom'))),
                            
                            Grid::make(3)
                                ->schema([
                                    Hidden::make('frequency')
                                        ->default('OD'),
                                    Hidden::make('frequency_times')
                                        ->dehydrated(false),
                                    Select::make('duration_type')
                                        ->label('Duration Type')
                                        ->options([
                                            'days' => 'Days',
                                            'weeks' => 'Weeks',
                                            'months' => 'Months',
                                        ])
                                        ->default('days')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('duration_preset', null)),
                                    Select::make('duration_preset')
                                        ->label('Duration')
                                        ->options(fn ($get) => match ($get('duration_type')) {
                                            'days' => [
                                                '1' => '1 Day',
                                                '2' => '2 Days',
                                                '3' => '3 Days',
                                                '5' => '5 Days',
                                                '7' => '7 Days',
                                                '10' => '10 Days',
                                                '14' => '14 Days',
                                                '30' => '30 Days',
                                                'custom' => 'Custom value...',
                                                'none' => 'No end date',
                                            ],
                                            'weeks' => [
                                                '1' => '1 Week',
                                                '2' => '2 Weeks',
                                                '3' => '3 Weeks',
                                                '4' => '4 Weeks',
                                                '6' => '6 Weeks',
                                                '8' => '8 Weeks',
                                                '12' => '12 Weeks',
                                                'custom' => 'Custom value...',
                                                'none' => 'No end date',
                                            ],
                                            'months' => [
                                                '1' => '1 Month',
                                                '2' => '2 Months',
                                                '3' => '3 Months',
                                                '6' => '6 Months',
                                                '12' => '12 Months',
                                                'custom' => 'Custom value...',
                                                'none' => 'No end date',
                                            ],
                                            default => [
                                                'custom' => 'Custom value...',
                                                'none' => 'No end date',
                                            ],
                                        })
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, $get) {
                                            $val = $get('duration_value');
                                            if ($val === null || $val === '') {
                                                $set('duration_preset', 'none');
                                                return;
                                            }

                                            $type = $get('duration_type') ?: 'days';
                                            $presets = match ($type) {
                                                'days' => ['1', '2', '3', '5', '7', '10', '14', '30'],
                                                'weeks' => ['1', '2', '3', '4', '6', '8', '12'],
                                                'months' => ['1', '2', '3', '6', '12'],
                                                default => [],
                                            };

                                            if (in_array((string)$val, $presets, true)) {
                                                $set('duration_preset', (string)$val);
                                            } else {
                                                $set('duration_preset', 'custom');
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state === 'none') {
                                                $set('duration_value', null);
                                            } elseif ($state !== 'custom' && $state !== null) {
                                                $set('duration_value', (int)$state);
                                            }
                                        }),
                                    TextInput::make('duration_value')
                                        ->label('Custom Duration Value')
                                        ->integer()
                                        ->minValue(1)
                                        ->visible(fn ($get) => $get('duration_preset') === 'custom')
                                        ->required(fn ($get) => $get('duration_preset') === 'custom')
                                        ->live(),
                                    Hidden::make('sort_order')->default(0),
                                ]),
                            Textarea::make('instructions')
                                ->label(fn ($get) => $get('use_type') === 'sos' ? 'Patient Instruction' : 'Instructions')
                                ->placeholder(fn ($get) => $get('use_type') === 'sos' ? 'Example: Take only if pain occurs. Do not take more than 3 doses in one day.' : 'Take after food')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Summary')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('name')->label('Template'),
                            TextEntry::make('scope_type')
                                ->label('Scope')
                                ->badge()
                                ->formatStateUsing(fn($state, MedicineTemplate $record): string => static::scopeLabel($record))
                                ->color(fn($state, MedicineTemplate $record): string => match ($record->scope_type ?? ($record->doctor_id ? MedicineTemplate::SCOPE_DOCTOR : MedicineTemplate::SCOPE_GLOBAL)) {
                                    MedicineTemplate::SCOPE_DOCTOR => 'info',
                                    MedicineTemplate::SCOPE_DEPARTMENT => 'warning',
                                    default => 'success',
                                }),
                            TextEntry::make('items_total')
                                ->label('Medicines')
                                ->state(fn(MedicineTemplate $record): int => $record->items()->count()),
                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime(),
                        ]),
                    TextEntry::make('description')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    ViewEntry::make('items')
                        ->view('filament.medicine-templates.template-items')
                        ->state(fn(MedicineTemplate $record) => $record->loadMissing(['items.medicine.type']))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('doctor_id')
                    ->label('Scope')
                    ->formatStateUsing(fn($state, MedicineTemplate $record): string => static::scopeLabel($record))
                    ->badge()
                    ->color(fn($state, MedicineTemplate $record): string => match ($record->scope_type ?? ($record->doctor_id ? MedicineTemplate::SCOPE_DOCTOR : MedicineTemplate::SCOPE_GLOBAL)) {
                        MedicineTemplate::SCOPE_DOCTOR => 'info',
                        MedicineTemplate::SCOPE_DEPARTMENT => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('items_count')->counts('items')->label('Medicines')->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('doctor_id')
                    ->label('Scope / Doctor')
                    ->options(fn() => ['global' => 'Global', 'department' => 'Department Specific'] + static::doctorOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'global' => $query->where('scope_type', MedicineTemplate::SCOPE_GLOBAL),
                            'department' => $query->where('scope_type', MedicineTemplate::SCOPE_DEPARTMENT),
                            null, '' => $query,
                            default => $query->where('doctor_id', $value),
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicineTemplates::route('/'),
            'create' => CreateMedicineTemplate::route('/create'),
            'view' => ViewMedicineTemplate::route('/{record}'),
            'edit' => EditMedicineTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['doctor.user', 'department'])
            ->withCount('items')
            ->withoutGlobalScopes();

        $user = Auth::user();

        if (! static::isPrivilegedUser() && $user?->doctor?->id) {
            return $query->where(fn(Builder $query) => $query
                ->where('doctor_id', $user->doctor->id)
                ->orWhere('scope_type', MedicineTemplate::SCOPE_GLOBAL)
                ->orWhereNull('scope_type')
                ->orWhereIn('department_id', $user->doctor->departments()->pluck('departments.id')));
        }

        return $query;
    }

    protected static function isOwnRecord($record): bool
    {
        return (bool) ($record?->doctor_id && Auth::user()?->doctor?->id === $record->doctor_id);
    }

    private static function hasMedicineTemplateRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'medicine_manager', 'doctor']);
    }

    private static function hasMedicineTemplateAdminRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'medicine_manager']);
    }

    private static function isPrivilegedUser(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'medicine_manager']);
    }

    private static function isDoctorOnlyUser(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasRole')
            && $user->hasRole('doctor')
            && ! static::isPrivilegedUser();
    }

    private static function defaultDoctorId(): ?string
    {
        return static::isDoctorOnlyUser() ? Auth::user()?->doctor?->id : null;
    }

    private static function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function doctorOptions(?string $departmentId = null): array
    {
        return Doctor::query()
            ->with('user')
            ->when($departmentId, fn(Builder $query) => $query->whereHas('departments', fn(Builder $query) => $query->where('departments.id', $departmentId)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn(Doctor $doctor): array => [$doctor->id => static::doctorDisplayName($doctor)])
            ->all();
    }

    private static function medicineOptions(): array
    {
        return Medicine::query()
            ->with('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn(Medicine $medicine): array => [
                $medicine->id => trim($medicine->name . ($medicine->type?->name ? ' (' . $medicine->type->name . ')' : '')),
            ])
            ->all();
    }

    private static function doctorDisplayName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '-';
        }

        return trim("{$doctor->first_name} {$doctor->last_name}")
            ?: (string) ($doctor->name ?: $doctor->user?->name ?: 'Doctor #' . $doctor->id);
    }

    private static function frequencyOptions(): array
    {
        return [
            'OD' => 'Once a day',
            'BD' => 'Twice a day',
            'TDS' => 'Three times a day',
            'SOS' => 'SOS / As needed',
        ];
    }

    private static function scopeOptions(): array
    {
        return [
            MedicineTemplate::SCOPE_GLOBAL => 'Global - All Doctors',
            MedicineTemplate::SCOPE_DOCTOR => 'Doctor Specific',
            MedicineTemplate::SCOPE_DEPARTMENT => 'Department Specific',
        ];
    }

    private static function scopeLabel(MedicineTemplate $record): string
    {
        $scope = $record->scope_type ?? ($record->doctor_id ? MedicineTemplate::SCOPE_DOCTOR : MedicineTemplate::SCOPE_GLOBAL);

        return match ($scope) {
            MedicineTemplate::SCOPE_DOCTOR => 'Doctor - ' . static::doctorDisplayName($record->doctor),
            MedicineTemplate::SCOPE_DEPARTMENT => 'Department - ' . ($record->department?->name ?? '-'),
            default => 'Global - All Doctors',
        };
    }

    private static function autoTimingPreview(int $dosesPerDay, string $firstDoseTime, int $intervalHours): HtmlString
    {
        $times = MedicineTemplateItem::autoTimings($dosesPerDay, $firstDoseTime, $intervalHours);
        $badges = collect($times)
            ->map(fn(string $time): string => '<span style="display:inline-flex;align-items:center;border-radius:999px;background:#eef2ff;color:#3730a3;padding:4px 10px;font-size:12px;font-weight:600;">' . e($time) . '</span>')
            ->implode(' ');

        return new HtmlString('
            <div style="border:1px solid #dbeafe;background:#eff6ff;border-radius:10px;padding:12px 14px;">
                <div style="font-size:12px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Auto Timing Preview</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">' . $badges . '</div>
                <div style="margin-top:8px;font-size:12px;color:#475569;">These times will be saved to the prescription when the template is assigned.</div>
            </div>
        ');
    }

    private static function timingOptions(): array
    {
        return [
            'Morning' => 'Morning',
            'Afternoon' => 'Afternoon',
            'Evening' => 'Evening',
            'Night' => 'Night',
        ];
    }

    private static function mealOptions(): array
    {
        return [
            'before_meal' => 'Before Meal',
            'after_meal' => 'After Meal',
            'with_meal' => 'With Meal',
        ];
    }

    protected static function generateSosInstruction(callable $set, callable $get): void
    {
        if ($get('use_type') !== 'sos') {
            return;
        }

        $reason = $get('take_when') === 'custom' ? $get('take_when_custom') : $get('take_when');
        $gap = $get('min_gap') === 'custom' ? $get('min_gap_custom') : $get('min_gap');
        $max = $get('max_doses_per_day') === 'custom' ? $get('max_doses_custom') : $get('max_doses_per_day');

        $parts = [];
        if ($reason) {
            $parts[] = "Take only if " . strtolower($reason) . " occurs.";
        }
        if ($gap) {
            $parts[] = "Minimum gap of " . strtolower($gap) . " between doses.";
        }
        if ($max) {
            $parts[] = "Do not take more than " . strtolower($max) . " in one day.";
        }

        $set('instructions', implode(' ', $parts));
    }

    private static function medicineItemLabel(array $state): ?string
    {
        $name = $state['medicine_name'] ?? null;
        $useType = $state['use_type'] ?? 'regular';
        $frequency = $state['frequency'] ?? null;
        $duration = $state['duration_value'] ?? null;
        $durationType = $state['duration_type'] ?? 'days';

        if (! $name) {
            return 'New medicine';
        }

        $parts = [$name];

        if ($useType === 'sos') {
            $takeWhen = $state['take_when'] ?? null;
            if ($takeWhen === 'custom') {
                $takeWhen = $state['take_when_custom'] ?? null;
            }
            if ($takeWhen) {
                $parts[] = "SOS (for {$takeWhen})";
            } else {
                $parts[] = "SOS";
            }
        } elseif ($frequency) {
            $parts[] = static::frequencyOptions()[$frequency] ?? $frequency;
        }

        if ($duration) {
            $parts[] = "{$duration} {$durationType}";
        }

        return implode(' - ', $parts);
    }
}
