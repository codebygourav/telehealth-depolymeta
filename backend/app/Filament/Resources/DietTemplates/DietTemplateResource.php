<?php

namespace App\Filament\Resources\DietTemplates;

use App\Filament\Resources\DietTemplates\Pages\CreateDietTemplate;
use App\Filament\Resources\DietTemplates\Pages\EditDietTemplate;
use App\Filament\Resources\DietTemplates\Pages\ListDietTemplates;
use App\Filament\Resources\DietTemplates\Pages\ViewDietTemplate;
use App\Models\DietTemplate;
use App\Models\Doctor;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DietTemplateResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = DietTemplate::class;

    protected static ?string $navigationLabel = 'Diet Templates';

    protected static ?string $slug = 'diet-templates';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Diet Templates',
            'icon' => 'heroicon-o-clipboard-document-list',
            'sort' => 6,
            'group' => 'Diet',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['diet-templates.view_any', 'diet-templates.view', 'diet-templates.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function canViewAny(): bool
    {
        return check_permission(['diet-templates.view_any', 'diet-templates.view', 'diet-templates.manage_own'])
            || static::hasDietTemplateRole();
    }

    public static function canCreate(): bool
    {
        return check_permission(['diet-templates.create', 'diet-templates.manage_own']) || static::hasDietTemplateRole();
    }

    public static function canEdit($record): bool
    {
        return check_permission('diet-templates.update')
            || (check_permission('diet-templates.manage_own') && static::isOwnRecord($record))
            || static::hasDietTemplateRole();
    }

    public static function canDelete($record): bool
    {
        return check_permission('diet-templates.delete_any')
            || (check_permission(['diet-templates.delete', 'diet-templates.manage_own']) && static::isOwnRecord($record))
            || static::hasDietTemplateAdminRole();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')
                ->description('Create a clear diet plan template and assign it to a doctor.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->options(fn() => Doctor::query()
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->get()
                                    ->mapWithKeys(function (Doctor $doctor) {
                                        $name = trim($doctor->first_name . ' ' . $doctor->last_name);
                                        if (!$name || $name === '') {
                                            $name = $doctor->name ?? '';
                                        }
                                        if (!$name) {
                                            $name = 'Doctor #' . $doctor->id;
                                        }

                                        return [$doctor->id => $name];
                                    }))
                                ->searchable()
                                ->required()
                                ->default(fn() => Auth::user()?->doctor?->id)
                                ->disabled(function () {
                                    $role = Auth::user()?->role;
                                    $isDoctor = $role === 'doctor';
                                    $isPrivileged = in_array($role, ['super_admin', 'doctor_manager', 'receptionist'], true);

                                    return $isDoctor && ! $isPrivileged;
                                })
                                ->dehydrated(),
                            TextInput::make('name')
                                ->label('Template Name')
                                ->placeholder('Pregnancy Balanced Diet / Diabetes Diet Plan')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('duration_days')
                                ->label('Duration Days')
                                ->integer()
                                ->minValue(1)
                                ->maxValue(180)
                                ->default(7)
                                ->required(),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->onColor('success')
                                ->offColor('danger')
                                ->default(true),
                        ]),
                    Textarea::make('description')
                        ->label('Short Description')
                        ->rows(3)
                        ->helperText('Optional details to describe the diet plan.'),
                    Textarea::make('restrictions')
                        ->label('Diet Restrictions')
                        ->rows(3)
                        ->helperText('Optional restrictions or food avoidances for this plan.'),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->helperText('Optional doctor notes for the patient.'),
                ])
                ->columnSpanFull(),

            Section::make('Weekly Meal Chart')
                ->description('Add days and meals in a simple, full-width schedule.')
                ->extraAttributes(['class' => 'diet-template-chart'])
                ->schema([
                    View::make('filament.diet-templates.weekly-meal-chart-helper'),
                    Repeater::make('days')
                        ->label('Days')
                        ->relationship('days')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->collapsible()
                        ->collapsed()
                        ->cloneable()
                        ->addActionLabel('Add day')
                        ->extraAttributes(['class' => 'diet-day-repeater'])
                        ->itemLabel(fn(array $state): ?string => static::dietDayItemLabel($state))
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    TextInput::make('day_number')
                                        ->label('Day Number')
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(31)
                                        ->required(),
                                    Select::make('week_day')
                                        ->label('Week Day')
                                        ->options(self::weekDayOptions())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),
                                ]),
                            Repeater::make('meals')
                                ->label('Meals')
                                ->relationship('meals')
                                ->minItems(1)
                                ->defaultItems(1)
                                ->reorderable()
                                ->orderColumn('sort_order')
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->addActionLabel('Add meal to this day')
                                ->extraAttributes(['class' => 'diet-meal-repeater'])
                                ->itemLabel(fn(array $state): ?string => static::dietMealItemLabel($state))
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            Select::make('meal_type')
                                                ->label('Meal Type')
                                                ->options(self::mealTypeOptions())
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set): void {
                                                    $presetKey = static::defaultMealPresetKeyForType((string) $state);
                                                    $set('meal_preset', $presetKey);

                                                    $preset = static::mealPreset($presetKey);

                                                    if ($preset) {
                                                        static::applyMealPreset($set, $preset);
                                                    }
                                                })
                                                ->required(),
                                            Select::make('meal_preset')
                                                ->label('Meal Name / Food Items')
                                                ->options(fn(callable $get): array => static::mealPresetOptions((string) $get('meal_type')))
                                                ->placeholder('Select a suggested meal')
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->helperText('Selecting an option autofills details below. Admin can still edit everything.')
                                                ->afterStateUpdated(function ($state, callable $set): void {
                                                    $preset = static::mealPreset((string) $state);

                                                    if ($preset) {
                                                        static::applyMealPreset($set, $preset);
                                                    }
                                                })
                                                ->dehydrated(false),
                                            TextInput::make('meal_name')
                                                ->label('Editable Meal Name / Food Items')
                                                ->placeholder('Oats with fruit and milk')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                        ]),
                                    Textarea::make('instructions')
                                        ->label('Instructions')
                                        ->rows(2)
                                        ->helperText('Optional meal preparation or portion guidance.')
                                        ->columnSpanFull(),
                                    Grid::make(5)
                                        ->schema([
                                            TextInput::make('calories')
                                                ->label('Calories')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('protein_grams')
                                                ->label('Protein (g)')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('carbs_grams')
                                                ->label('Carbs (g)')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('fat_grams')
                                                ->label('Fat (g)')
                                                ->numeric()
                                                ->minValue(0),
                                            TimePicker::make('start_time')
                                                ->label('Start Time'),
                                        ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('template_details')
                ->view('filament.diet-templates.template-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('doctor_id')
                    ->label('Doctor')
                    ->formatStateUsing(fn($state, DietTemplate $record): string => static::doctorDisplayName($record->doctor))
                    ->placeholder('-'),
                TextColumn::make('duration_days')->label('Days')->sortable(),
                TextColumn::make('days_count')->counts('days')->label('Chart Days')->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
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
            'index' => ListDietTemplates::route('/'),
            'create' => CreateDietTemplate::route('/create'),
            'view' => ViewDietTemplate::route('/{record}'),
            'edit' => EditDietTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['doctor.user'])
            ->withoutGlobalScopes();

        $user = Auth::user();
        $isPrivileged = is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);

        if (! $isPrivileged && $user?->doctor?->id) {
            return $query->where('doctor_id', $user->doctor?->id);
        }

        return $query;
    }

    protected static function isOwnRecord($record): bool
    {
        return (bool) ($record?->doctor_id && Auth::user()?->doctor?->id === $record->doctor_id);
    }

    private static function hasDietTemplateRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    private static function hasDietTemplateAdminRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);
    }

    private static function doctorDisplayName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '-';
        }

        return trim("{$doctor->first_name} {$doctor->last_name}")
            ?: (string) ($doctor->name ?: $doctor->user?->name ?: '-');
    }

    private static function weekDayOptions(): array
    {
        return [
            'MONDAY' => 'Monday',
            'TUESDAY' => 'Tuesday',
            'WEDNESDAY' => 'Wednesday',
            'THURSDAY' => 'Thursday',
            'FRIDAY' => 'Friday',
            'SATURDAY' => 'Saturday',
            'SUNDAY' => 'Sunday',
        ];
    }

    private static function mealTypeOptions(): array
    {
        return [
            'MORNING' => 'Morning',
            'BREAKFAST' => 'Breakfast',
            'MID_MEAL' => 'Mid Meal',
            'LUNCH' => 'Lunch',
            'EVENING_SNACK' => 'Evening Snack',
            'DINNER' => 'Dinner',
            'NIGHT' => 'Night',
        ];
    }

    private static function dietDayItemLabel(array $state): ?string
    {
        $dayNumber = $state['day_number'] ?? null;
        $weekDay = $state['week_day'] ?? null;

        if (! $dayNumber && ! $weekDay) {
            return 'New day';
        }

        $label = $dayNumber ? "Day {$dayNumber}" : 'Day';

        if ($weekDay) {
            $label .= ' - ' . (static::weekDayOptions()[$weekDay] ?? ucfirst(strtolower((string) $weekDay)));
        }

        $mealCount = is_countable($state['meals'] ?? null) ? count($state['meals']) : 0;

        return $mealCount > 0 ? "{$label} ({$mealCount} meals)" : $label;
    }

    private static function dietMealItemLabel(array $state): ?string
    {
        $mealType = $state['meal_type'] ?? null;
        $mealName = $state['meal_name'] ?? null;

        if (! $mealType && ! $mealName) {
            return 'New meal';
        }

        $typeLabel = $mealType ? (static::mealTypeOptions()[$mealType] ?? ucfirst(strtolower((string) $mealType))) : 'Meal';

        return $mealName ? "{$typeLabel}: {$mealName}" : $typeLabel;
    }

    private static function mealPresetOptions(?string $mealType): array
    {
        if (! $mealType) {
            return [];
        }

        return collect(static::mealPresets()[$mealType] ?? [])
            ->mapWithKeys(fn(array $preset, string $key): array => [$key => $preset['meal_name']])
            ->all();
    }

    private static function defaultMealPresetKeyForType(?string $mealType): ?string
    {
        if (! $mealType) {
            return null;
        }

        $presets = static::mealPresets()[$mealType] ?? [];

        return array_key_first($presets);
    }

    private static function mealPreset(?string $presetKey): ?array
    {
        if (! $presetKey) {
            return null;
        }

        foreach (static::mealPresets() as $presets) {
            if (isset($presets[$presetKey])) {
                return $presets[$presetKey];
            }
        }

        return null;
    }

    private static function applyMealPreset(callable $set, array $preset): void
    {
        foreach ([
            'meal_name',
            'instructions',
            'calories',
            'protein_grams',
            'carbs_grams',
            'fat_grams',
            'start_time',
        ] as $field) {
            if (array_key_exists($field, $preset)) {
                $set($field, $preset[$field]);
            }
        }
    }

    private static function mealPresets(): array
    {
        return [
            'MORNING' => [
                'morning_warm_lemon_water' => [
                    'meal_name' => 'Warm lemon water with soaked almonds',
                    'instructions' => 'Serve warm water with lemon and 5 soaked almonds.',
                    'calories' => 110,
                    'protein_grams' => 4,
                    'carbs_grams' => 7,
                    'fat_grams' => 8,
                    'start_time' => '06:30',
                ],
                'morning_fruit_bowl' => [
                    'meal_name' => 'Seasonal fruit bowl',
                    'instructions' => 'Use one bowl of fresh seasonal fruit. Avoid added sugar.',
                    'calories' => 140,
                    'protein_grams' => 2,
                    'carbs_grams' => 32,
                    'fat_grams' => 1,
                    'start_time' => '07:00',
                ],
            ],
            'BREAKFAST' => [
                'breakfast_oats_fruit_milk' => [
                    'meal_name' => 'Oats with fruit and milk',
                    'instructions' => 'Cook oats in milk and top with banana or apple slices.',
                    'calories' => 320,
                    'protein_grams' => 13,
                    'carbs_grams' => 52,
                    'fat_grams' => 8,
                    'start_time' => '08:00',
                ],
                'breakfast_vegetable_upma' => [
                    'meal_name' => 'Vegetable upma with curd',
                    'instructions' => 'Prepare with mixed vegetables and serve with plain curd.',
                    'calories' => 360,
                    'protein_grams' => 11,
                    'carbs_grams' => 58,
                    'fat_grams' => 10,
                    'start_time' => '08:30',
                ],
                'breakfast_moong_chilla' => [
                    'meal_name' => 'Moong dal chilla with mint chutney',
                    'instructions' => 'Serve two medium chillas with fresh mint chutney.',
                    'calories' => 300,
                    'protein_grams' => 16,
                    'carbs_grams' => 42,
                    'fat_grams' => 8,
                    'start_time' => '08:30',
                ],
            ],
            'MID_MEAL' => [
                'midmeal_coconut_water' => [
                    'meal_name' => 'Coconut water with roasted chana',
                    'instructions' => 'Serve one glass coconut water with a small handful of roasted chana.',
                    'calories' => 170,
                    'protein_grams' => 7,
                    'carbs_grams' => 29,
                    'fat_grams' => 3,
                    'start_time' => '11:00',
                ],
                'midmeal_sprouts_salad' => [
                    'meal_name' => 'Sprouts salad',
                    'instructions' => 'Mix sprouts with cucumber, tomato, lemon and mild seasoning.',
                    'calories' => 180,
                    'protein_grams' => 10,
                    'carbs_grams' => 30,
                    'fat_grams' => 3,
                    'start_time' => '11:30',
                ],
            ],
            'LUNCH' => [
                'lunch_roti_dal_veg_curd' => [
                    'meal_name' => 'Roti, dal, vegetable sabzi and curd',
                    'instructions' => 'Serve two rotis with one bowl dal, one bowl sabzi and curd.',
                    'calories' => 560,
                    'protein_grams' => 22,
                    'carbs_grams' => 82,
                    'fat_grams' => 15,
                    'start_time' => '13:00',
                ],
                'lunch_rice_dal_salad' => [
                    'meal_name' => 'Rice, dal and salad',
                    'instructions' => 'Serve steamed rice with dal and a fresh vegetable salad.',
                    'calories' => 520,
                    'protein_grams' => 18,
                    'carbs_grams' => 88,
                    'fat_grams' => 11,
                    'start_time' => '13:30',
                ],
                'lunch_veg_khichdi' => [
                    'meal_name' => 'Vegetable khichdi with curd',
                    'instructions' => 'Use rice, moong dal and vegetables. Serve with plain curd.',
                    'calories' => 470,
                    'protein_grams' => 17,
                    'carbs_grams' => 76,
                    'fat_grams' => 11,
                    'start_time' => '13:00',
                ],
            ],
            'EVENING_SNACK' => [
                'snack_makhana_tea' => [
                    'meal_name' => 'Roasted makhana with tea',
                    'instructions' => 'Roast makhana lightly. Serve with unsweetened or low-sugar tea.',
                    'calories' => 190,
                    'protein_grams' => 6,
                    'carbs_grams' => 26,
                    'fat_grams' => 7,
                    'start_time' => '17:00',
                ],
                'snack_vegetable_sandwich' => [
                    'meal_name' => 'Vegetable sandwich',
                    'instructions' => 'Use whole wheat bread with cucumber, tomato and paneer or curd spread.',
                    'calories' => 280,
                    'protein_grams' => 11,
                    'carbs_grams' => 38,
                    'fat_grams' => 9,
                    'start_time' => '17:30',
                ],
            ],
            'DINNER' => [
                'dinner_roti_paneer_veg' => [
                    'meal_name' => 'Roti with paneer and vegetables',
                    'instructions' => 'Serve two rotis with paneer bhurji and cooked vegetables.',
                    'calories' => 520,
                    'protein_grams' => 25,
                    'carbs_grams' => 58,
                    'fat_grams' => 20,
                    'start_time' => '20:00',
                ],
                'dinner_dalia_soup' => [
                    'meal_name' => 'Vegetable dalia with soup',
                    'instructions' => 'Serve vegetable dalia with a clear vegetable soup.',
                    'calories' => 430,
                    'protein_grams' => 15,
                    'carbs_grams' => 70,
                    'fat_grams' => 10,
                    'start_time' => '20:00',
                ],
                'dinner_light_khichdi' => [
                    'meal_name' => 'Light moong dal khichdi',
                    'instructions' => 'Prepare soft khichdi with minimal oil and mild spices.',
                    'calories' => 390,
                    'protein_grams' => 15,
                    'carbs_grams' => 66,
                    'fat_grams' => 8,
                    'start_time' => '19:30',
                ],
            ],
            'NIGHT' => [
                'night_turmeric_milk' => [
                    'meal_name' => 'Warm turmeric milk',
                    'instructions' => 'Serve one cup warm milk with a pinch of turmeric.',
                    'calories' => 150,
                    'protein_grams' => 8,
                    'carbs_grams' => 12,
                    'fat_grams' => 8,
                    'start_time' => '22:00',
                ],
                'night_plain_milk' => [
                    'meal_name' => 'Plain warm milk',
                    'instructions' => 'Serve one cup warm milk. Keep sugar optional and minimal.',
                    'calories' => 130,
                    'protein_grams' => 8,
                    'carbs_grams' => 12,
                    'fat_grams' => 5,
                    'start_time' => '22:00',
                ],
            ],
        ];
    }
}
