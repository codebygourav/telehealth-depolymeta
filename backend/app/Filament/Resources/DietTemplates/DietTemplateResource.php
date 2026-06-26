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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            Select::make('duration_preset')
                                ->label('Duration Preset')
                                ->options(static::durationPresetOptions())
                                ->default('1_week')
                                ->live()
                                ->afterStateHydrated(function ($component, $state, $record): void {
                                    $component->state(static::durationPresetForDays((int) ($record?->duration_days ?? 7)));
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $days = static::durationDaysForPreset((string) $state);

                                    if ($days !== null) {
                                        $set('duration_days', $days);
                                    }
                                })
                                ->dehydrated(false),
                            TextInput::make('duration_days')
                                ->label('Duration Days')
                                ->integer()
                                ->minValue(1)
                                ->maxValue(180)
                                ->default(7)
                                ->helperText('Select a common preset above or enter a custom number of days.')
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

            Section::make('Meal Schedule Rule')
                ->description('Choose recurring or one-time mode. Weekly chart behavior updates automatically based on this rule.')
                ->schema([
                    Hidden::make('features.schedule.recurrence_mode')
                        ->default('recurring')
                        ->afterStateHydrated(function ($component, $state): void {
                            if (! filled($state)) {
                                $component->state('recurring');
                            }
                        })
                        ->live(),
                    Hidden::make('features.schedule.pattern_type')
                        ->default('weekly')
                        ->afterStateHydrated(function ($component, $state): void {
                            if (! filled($state)) {
                                $component->state('weekly');
                            }
                        })
                        ->live(),
                    Hidden::make('features.schedule.cycle_length_days')
                        ->default(7)
                        ->afterStateHydrated(function ($component, $state): void {
                            if (! filled($state)) {
                                $component->state(7);
                            }
                        }),
                    Hidden::make('features.schedule.follow_same_meal_all_days')
                        ->default(false)
                        ->live(),
                    View::make('filament.diet-templates.meal-schedule-rule-helper'),
                ])
                ->columnSpanFull(),

            Section::make('Weekly Meal Chart')
                ->description('Manage week tabs, day tabs, and meals in one editor.')
                ->collapsible()
                ->extraAttributes(['class' => 'diet-template-chart'])
                ->schema([
                    Hidden::make('diet_chart_payload')
                        ->default(fn(): string => json_encode(static::normalizeDietChartData([])))
                        ->dehydrated()
                        ->live(),
                    View::make('filament.diet-templates.weekly-meal-chart-context'),
                ])
                ->columnSpanFull(),

            Section::make('Sync Updates to Patients')
                ->description('This template is assigned to active patient diet plans. Choose whether the saved meals, dates, days, and plan details should also update those patients.')
                ->schema([
                    Radio::make('patient_sync_mode')
                        ->label('Patient update choice')
                        ->options([
                            'template_only' => 'Update template only. Do not change any assigned patient diet plans.',
                            'selected_patients' => 'Update selected patient diet plans with the same meals, dates, and days.',
                        ])
                        ->descriptions([
                            'template_only' => 'Patient and doctor frontend plans keep their current assigned data.',
                            'selected_patients' => 'Only checked patients below receive the updated plan details.',
                        ])
                        ->required()
                        ->live()
                        ->dehydrated(false),
                    CheckboxList::make('sync_patient_plans')
                        ->label('Assigned active patients')
                        ->options(fn ($record): array => static::assignedPatientPlanOptions($record))
                        ->descriptions(fn ($record): array => static::assignedPatientPlanDescriptions($record))
                        ->bulkToggleable()
                        ->columns(2)
                        ->required(fn (callable $get): bool => $get('patient_sync_mode') === 'selected_patients')
                        ->visible(fn (callable $get): bool => $get('patient_sync_mode') === 'selected_patients')
                        ->dehydrated(false),
                ])
                ->visible(function ($operation, $record, $livewire): bool {
                    return $operation === 'edit'
                        && (bool) ($livewire->showPatientSyncSection ?? false)
                        && static::assignedPatientPlansQuery($record)->exists();
                })
                ->extraAttributes(['id' => 'diet-template-patient-sync'])
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

    private static function durationPresetOptions(): array
    {
        return [
            '1_week' => '1 week (7 days)',
            '2_weeks' => '2 weeks (14 days)',
            '3_weeks' => '3 weeks (21 days)',
            '1_month' => '1 month (30 days)',
            '2_months' => '2 months (60 days)',
            'custom' => 'Custom duration',
        ];
    }

    private static function durationDaysForPreset(?string $preset): ?int
    {
        return match ($preset) {
            '1_week' => 7,
            '2_weeks' => 14,
            '3_weeks' => 21,
            '1_month' => 30,
            '2_months' => 60,
            default => null,
        };
    }

    private static function durationPresetForDays(int $days): string
    {
        return match ($days) {
            7 => '1_week',
            14 => '2_weeks',
            21 => '3_weeks',
            30 => '1_month',
            60 => '2_months',
            default => 'custom',
        };
    }

    private static function scheduleRecurrenceOptions(): array
    {
        return [
            'recurring' => 'Recurring template',
            'one_time' => 'One-time template (no repeat)',
        ];
    }

    private static function schedulePatternOptions(): array
    {
        return [
            'weekly' => 'Weekly repeat (Monday-Sunday)',
            'cycle' => 'Cycle repeat (7/14/21/28/30 days)',
        ];
    }

    private static function scheduleRepeatLogicOptions(): array
    {
        return [
            'repeat_cycle_until_duration' => 'Repeat cycle until duration ends',
        ];
    }

    private static function scheduleCycleLengthOptions(): array
    {
        return [
            7 => '1 week cycle (7 days)',
            14 => '2 weeks cycle (14 days)',
            21 => '3 weeks cycle (21 days)',
            28 => '4 weeks cycle (28 days)',
            30 => '1 month cycle (30 days)',
        ];
    }

    private static function scheduleValue(callable $get, string $field, mixed $default = null): mixed
    {
        return $get("features.schedule.{$field}")
            ?? $get("../features.schedule.{$field}")
            ?? $get("../../features.schedule.{$field}")
            ?? $default;
    }

    public static function dietChartDataForForm(DietTemplate $record): array
    {
        return $record->days()
            ->with('meals')
            ->orderBy('day_number')
            ->get()
            ->map(fn($day): array => [
                'day_number' => (int) $day->day_number,
                'week_day' => (string) $day->week_day,
                'meals' => $day->meals
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn($meal): array => [
                        'meal_type' => (string) $meal->meal_type,
                        'meal_name' => (string) $meal->meal_name,
                        'instructions' => (string) ($meal->instructions ?? ''),
                        'meal_image' => (string) ($meal->meal_image ?? ''),
                        'helpful_links' => collect($meal->helpful_links ?? [])->map(fn($link): array => [
                            'type' => (string) ($link['type'] ?? ''),
                            'title' => (string) ($link['title'] ?? ''),
                            'url' => (string) ($link['url'] ?? ''),
                        ])->all(),
                        'calories' => $meal->calories,
                        'protein_grams' => $meal->protein_grams,
                        'carbs_grams' => $meal->carbs_grams,
                        'fat_grams' => $meal->fat_grams,
                        'start_time' => $meal->start_time ? substr((string) $meal->start_time, 0, 5) : '',
                        'sort_order' => (int) $meal->sort_order,
                    ])
                    ->all(),
            ])
            ->all();
    }

    public static function normalizeDietChartData(?array $days): array
    {
        $weekDays = array_keys(static::weekDayOptions());

        $normalized = collect($days ?: [])
            ->values()
            ->map(function (array $day, int $index) use ($weekDays): array {
                $dayNumber = max(1, (int) ($day['day_number'] ?? ($index + 1)));
                $weekDay = (string) ($day['week_day'] ?? $weekDays[($dayNumber - 1) % count($weekDays)]);

                if (! array_key_exists($weekDay, static::weekDayOptions())) {
                    $weekDay = $weekDays[($dayNumber - 1) % count($weekDays)];
                }

                $meals = collect($day['meals'] ?? [])
                    ->values()
                    ->filter(fn($meal): bool => filled($meal['meal_name'] ?? null) || filled($meal['meal_type'] ?? null))
                    ->map(fn(array $meal, int $mealIndex): array => [
                        'meal_type' => (string) ($meal['meal_type'] ?? 'MORNING'),
                        'meal_name' => (string) ($meal['meal_name'] ?? 'New meal'),
                        'instructions' => filled($meal['instructions'] ?? null) ? (string) $meal['instructions'] : null,
                        'meal_image' => filled($meal['meal_image'] ?? null) ? (string) $meal['meal_image'] : null,
                        'helpful_links' => collect($meal['helpful_links'] ?? [])
                            ->filter(fn($link): bool => filled($link['url'] ?? null))
                            ->map(fn($link): array => [
                                'type' => filled($link['type'] ?? null) ? (string) $link['type'] : 'recipe',
                                'title' => filled($link['title'] ?? null) ? (string) $link['title'] : null,
                                'url' => (string) $link['url'],
                            ])
                            ->values()
                            ->all(),
                        'calories' => filled($meal['calories'] ?? null) ? (int) $meal['calories'] : null,
                        'protein_grams' => filled($meal['protein_grams'] ?? null) ? (int) $meal['protein_grams'] : null,
                        'carbs_grams' => filled($meal['carbs_grams'] ?? null) ? (int) $meal['carbs_grams'] : null,
                        'fat_grams' => filled($meal['fat_grams'] ?? null) ? (int) $meal['fat_grams'] : null,
                        'start_time' => filled($meal['start_time'] ?? null) ? (string) $meal['start_time'] : null,
                        'sort_order' => $mealIndex,
                    ])
                    ->all();

                return [
                    'day_number' => $dayNumber,
                    'week_day' => $weekDay,
                    'meals' => $meals,
                ];
            })
            ->all();

        return $normalized ?: [
            [
                'day_number' => 1,
                'week_day' => 'MONDAY',
                'meals' => [],
            ],
        ];
    }

    public static function decodeDietChartPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return static::normalizeDietChartData($payload);
        }

        if (! is_string($payload) || trim($payload) === '') {
            return static::normalizeDietChartData([]);
        }

        $decoded = json_decode($payload, true);

        return static::normalizeDietChartData(is_array($decoded) ? $decoded : []);
    }

    public static function syncDietChart(DietTemplate $record, ?array $days): void
    {
        $record->days()->delete();

        foreach (static::normalizeDietChartData($days) as $dayData) {
            $day = $record->days()->create([
                'day_number' => $dayData['day_number'],
                'week_day' => $dayData['week_day'],
            ]);

            foreach ($dayData['meals'] as $mealData) {
                $day->meals()->create($mealData);
            }
        }
    }

    public static function assignedPatientPlanOptions(?DietTemplate $record): array
    {
        return static::assignedPatientPlansQuery($record)
            ->get()
            ->mapWithKeys(function (\App\Models\PatientDietPlan $plan): array {
                return [$plan->id => static::patientPlanDisplayName($plan)];
            })
            ->all();
    }

    public static function assignedPatientPlanDescriptions(?DietTemplate $record): array
    {
        return static::assignedPatientPlansQuery($record)
            ->get()
            ->mapWithKeys(function (\App\Models\PatientDietPlan $plan): array {
                $startDate = $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('d M Y') : 'No start date';
                $contact = $plan->patient?->mobile_no ?: $plan->patient?->email;
                $parts = array_filter([
                    "Started {$startDate}",
                    $contact,
                    $plan->doctor ? 'Dr. ' . static::doctorDisplayName($plan->doctor) : null,
                ]);

                return [$plan->id => implode(' | ', $parts)];
            })
            ->all();
    }

    public static function assignedPatientPlansQuery(?DietTemplate $record): Builder
    {
        return \App\Models\PatientDietPlan::query()
            ->when($record?->id, fn (Builder $query, string $templateId): Builder => $query->where('diet_template_id', $templateId))
            ->when(! $record?->id, fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->where('status', 'active')
            ->with(['patient', 'doctor'])
            ->orderBy('start_date')
            ->orderBy('created_at');
    }

    private static function patientPlanDisplayName(\App\Models\PatientDietPlan $plan): string
    {
        $patient = $plan->patient;
        $name = trim(($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? ''));

        return $name ?: (string) ($patient?->email ?: $patient?->mobile_no ?: "Patient #{$plan->patient_id}");
    }

    private static function canAddTemplateDay(callable $get): bool
    {
        $days = $get('days') ?? [];
        $dayCount = is_countable($days) ? count($days) : 0;

        $recurrenceMode = (string) static::scheduleValue($get, 'recurrence_mode', 'recurring');
        $patternType = (string) static::scheduleValue($get, 'pattern_type', 'weekly');
        $cycleLength = max(1, (int) static::scheduleValue($get, 'cycle_length_days', 7));
        $durationDays = max(1, (int) ($get('duration_days') ?? 7));

        if ($recurrenceMode === 'one_time') {
            return $dayCount < $durationDays;
        }

        if ($patternType === 'cycle') {
            return $dayCount < $cycleLength;
        }

        return $dayCount < $durationDays;
    }

    private static function dayRepeaterHelperText(callable $get): string
    {
        $recurrenceMode = (string) static::scheduleValue($get, 'recurrence_mode', 'recurring');
        $patternType = (string) static::scheduleValue($get, 'pattern_type', 'weekly');
        $cycleLength = max(1, (int) static::scheduleValue($get, 'cycle_length_days', 7));

        if ($recurrenceMode === 'one_time') {
            return 'One-time mode: add exact days needed. Patient plan will not repeat beyond configured days.';
        }

        if ($patternType === 'weekly') {
            return 'Weekly mode: add day entries up to the selected duration. Use the week and day tabs above to edit one day at a time.';
        }

        return "Cycle mode: add up to {$cycleLength} days. After that, the cycle repeats automatically for patient duration.";
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
        foreach (
            [
                'meal_name',
                'instructions',
                'calories',
                'protein_grams',
                'carbs_grams',
                'fat_grams',
                'start_time',
            ] as $field
        ) {
            if (array_key_exists($field, $preset)) {
                $set($field, $preset[$field]);
            }
        }
    }

    public static function mealPresets(): array
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
