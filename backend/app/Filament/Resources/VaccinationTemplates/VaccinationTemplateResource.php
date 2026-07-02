<?php

namespace App\Filament\Resources\VaccinationTemplates;

use App\Enums\VaccinationProgramTargetType;
use App\Filament\Resources\VaccinationTemplates\Pages;
use App\Models\Doctor;
use App\Models\Vaccination;
use App\Models\VaccinationProgram;
use App\Models\VaccinationTemplate;
use App\Models\VaccinationTemplateItem;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VaccinationTemplateResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = VaccinationTemplate::class;

    protected static ?string $navigationLabel = 'Vaccination Templates';

    protected static ?string $slug = 'vaccination-templates';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Schedule Templates',
            'icon' => 'heroicon-o-clipboard-document-list',
            'sort' => 5,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['vaccination-templates.view_any', 'vaccination-templates.view', 'vaccination-templates.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function form(Schema $schema): Schema
    {
        $getTargetType = function ($get) {
            $programId = $get('../../vaccination_program_id');
            if (! $programId) {
                return 'baby';
            }
            return \App\Models\VaccinationProgram::where('id', $programId)->value('target_type') ?: 'baby';
        };

        return $schema->components([
            Section::make('Template Information')
                ->description('Choose the category/target for this schedule. Doctors will assign this template to a registered patient or family profile.')
                ->schema([
                    Grid::make(12)
                        ->schema([
                            Select::make('vaccination_program_id')
                                ->label('Target Category')
                                ->options(fn() => VaccinationProgram::query()
                                    ->where('is_active', true)
                                    ->orderBy('target_type')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function (VaccinationProgram $category) {
                                        $target = $category->target_type instanceof VaccinationProgramTargetType
                                            ? $category->target_type->label()
                                            : str((string) $category->target_type)->replace('_', ' ')->title()->toString();

                                        return [$category->id => "{$target} - {$category->name}"];
                                    }))
                                ->searchable()
                                ->required()
                                ->live()
                                ->helperText('Choose one default target category. Admins do not create categories from this form.')
                                ->columnSpan(6),
                            TextInput::make('name')
                                ->label('Template Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(6),
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->options(fn() => Doctor::query()
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim("{$doctor->first_name} {$doctor->last_name}") ?: $doctor->name ?: $doctor->id]))
                                ->searchable()
                                ->required()
                                ->default(fn() => Auth::user()?->doctor?->id)
                                ->disabled(function () {
                                    $role = Auth::user()?->role;
                                    $isDoctor = $role === 'doctor';
                                    $isPrivileged = in_array($role, ['super_admin', 'doctor_manager', 'receptionist'], true);

                                    return $isDoctor && ! $isPrivileged;
                                })
                                ->dehydrated()
                                ->columnSpan(6),
                            Toggle::make('is_active')
                                ->label('Template Active')
                                ->onColor('success')
                                ->offColor('danger')
                                ->default(true)
                                ->columnSpan(6),
                            Textarea::make('description')
                                ->label('Template Description')
                                ->rows(3)
                                ->placeholder('Enter a clear description of who this template is for...')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Notification Rules (NotificationService)')
                ->description('Configure dynamic reminder rules in days relative to the calculated vaccine dose due dates.')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextInput::make('reminder_1_days_before')
                                ->label('Reminder 1')
                                ->numeric()
                                ->minValue(0)
                                ->default(7)
                                ->suffix('days before')
                                ->required(),
                            TextInput::make('reminder_2_days_before')
                                ->label('Reminder 2')
                                ->numeric()
                                ->minValue(0)
                                ->default(3)
                                ->suffix('days before')
                                ->required(),
                            TextInput::make('reminder_3_days_before')
                                ->label('Reminder 3')
                                ->numeric()
                                ->minValue(0)
                                ->default(1)
                                ->suffix('days before')
                                ->required(),
                            TextInput::make('overdue_alert_days_after')
                                ->label('Overdue Alert')
                                ->numeric()
                                ->minValue(0)
                                ->default(1)
                                ->suffix('days after due')
                                ->required(),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Vaccination Schedule')
                ->description('Group doses into schedule sets. Each set can contain multiple doses and dependency rules.')
                ->extraAttributes(['class' => 'vaccination-template-builder'])
                ->schema([
                    Repeater::make('sets')
                        ->label('Vaccination Sets')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->addActionLabel('Add vaccination set')
                        ->extraAttributes(['class' => 'vaccination-set-repeater'])
                        ->itemLabel(fn(array $state): ?string => $state['set_name'] ?? 'New Set')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('set_name')
                                        ->label('Set Name')
                                        ->placeholder('Pregnancy Month 1, Baby Month 2, Adult Booster')
                                        ->required(),
                                    Textarea::make('set_description')
                                        ->label('Set Description')
                                        ->rows(1)
                                        ->autosize()
                                        ->helperText('Optional note for this set.'),
                                ]),
                            Repeater::make('doses')
                                ->label('Doses')
                                ->minItems(1)
                                ->defaultItems(1)
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->addActionLabel('Add dose to this set')
                                ->extraAttributes(['class' => 'vaccination-dose-repeater'])
                                ->itemLabel(fn(array $state): ?string => 'Dose ' . ($state['dose_no'] ?? 1))
                                ->schema([
                                    Grid::make(12)
                                        ->schema([
                                            Select::make('vaccination_id')
                                                ->label('Vaccine Name')
                                                ->placeholder('Choose a vaccine...')
                                                ->options(fn() => Vaccination::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                                ->searchable()
                                                ->preload()
                                                ->optionsLimit(1000)
                                                ->required()
                                                ->live()
                                                ->columnSpan(6),
                                            TextInput::make('dose_no')
                                                ->label('Dose Number')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1)
                                                ->placeholder('e.g. 1')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->columnSpan(2),
                                            TextInput::make('recommended_age_label')
                                                ->label(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return match ($targetType) {
                                                        'pregnancy' => 'Recommended Gestational Stage',
                                                        'baby', 'child' => 'Recommended Age Label',
                                                        default => 'Recommended Schedule Phase',
                                                    };
                                                })
                                                ->placeholder(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return match ($targetType) {
                                                        'pregnancy' => 'e.g. 28 Weeks, 3rd Trimester',
                                                        'baby', 'child' => 'e.g. Birth, 6 Weeks, 9 Months',
                                                        default => 'e.g. Phase 1, Booster 1',
                                                    };
                                                })
                                                ->helperText('Visual reference label displayed to patients and doctors.')
                                                ->live(onBlur: true)
                                                ->columnSpan(4),
                                            Select::make('timing_type')
                                                ->label('Timing Type')
                                                ->options(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    $baseLabel = match ($targetType) {
                                                        'pregnancy' => 'Pregnancy Based (LMP / Pregnancy Start Date)',
                                                        'baby', 'child' => 'DOB Based (Patient Account Date of Birth)',
                                                        default => 'Assignment Date Based (Category Start Date)',
                                                    };
                                                    return [
                                                        'base_date' => $baseLabel,
                                                        'previous_dose' => 'Previous Dose Based (Actual completed date + gap)',
                                                        'doctor_manual_date' => 'Doctor Manual Date (doctor decides after review)',
                                                    ];
                                                })
                                                ->selectablePlaceholder(false)
                                                ->default('base_date')
                                                ->live()
                                                ->columnSpan(6),
                                            TextInput::make('offset_value')
                                                ->label(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return match ($targetType) {
                                                        'pregnancy' => 'Offset Value (e.g. 28)',
                                                        'baby', 'child' => 'Age Offset Value',
                                                        default => 'Category Offset Value',
                                                    };
                                                })
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->placeholder(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return match ($targetType) {
                                                        'pregnancy' => 'e.g. 28',
                                                        'baby', 'child' => 'e.g. 6',
                                                        default => 'e.g. 0',
                                                    };
                                                })
                                                ->helperText(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return match ($targetType) {
                                                        'pregnancy' => 'Use with unit weeks. Example: 28 weeks after LMP.',
                                                        'baby', 'child' => 'Use with days/weeks/months/years from DOB.',
                                                        default => 'Use with days/weeks/months/years from assignment date.',
                                                    };
                                                })
                                                ->hidden(fn($get) => $get('timing_type') !== 'base_date')
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                            Select::make('offset_unit')
                                                ->label('Offset Unit')
                                                ->options([
                                                    'days' => 'Days',
                                                    'weeks' => 'Weeks',
                                                    'months' => 'Months',
                                                    'years' => 'Years',
                                                ])
                                                ->default(fn($get) => $getTargetType($get) === 'pregnancy' ? 'weeks' : 'days')
                                                ->selectablePlaceholder(false)
                                                ->helperText('Clear unit for all categories. Pregnancy uses weeks instead of months-as-weeks.')
                                                ->hidden(fn($get) => $get('timing_type') !== 'base_date')
                                                ->live()
                                                ->columnSpan(3),
                                            TextInput::make('interval_value')
                                                ->label('Gap Value')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->placeholder(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return $targetType === 'pregnancy' ? 'e.g. 4' : 'e.g. 1';
                                                })
                                                ->helperText(function ($get) use ($getTargetType) {
                                                    $targetType = $getTargetType($get);
                                                    return $targetType === 'pregnancy' ? 'Gap after previous completed dose.' : 'Gap after previous actual completed date.';
                                                })
                                                ->visible(fn($get) => $get('timing_type') === 'previous_dose')
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                            Select::make('interval_unit')
                                                ->label('Gap Unit')
                                                ->options([
                                                    'days' => 'Days',
                                                    'weeks' => 'Weeks',
                                                    'months' => 'Months',
                                                    'years' => 'Years',
                                                ])
                                                ->default('days')
                                                ->selectablePlaceholder(false)
                                                ->helperText('Example: Dose 2 = 30 days after Dose 1 actual completed date.')
                                                ->visible(fn($get) => $get('timing_type') === 'previous_dose')
                                                ->live()
                                                ->columnSpan(3),
                                            Placeholder::make('timing_explanation')
                                                ->label('')
                                                ->content(function ($get) use ($getTargetType) {
                                                    $timingType = $get('timing_type') ?: 'base_date';
                                                    $targetType = $getTargetType($get);
                                                    if ($timingType === 'previous_dose') {
                                                        return new \Illuminate\Support\HtmlString('
                                                            <div class="dark:bg-amber-955/20 dark:border-amber-900/50 dark:text-amber-300" style="padding: 10px 14px; border-radius: 8px; font-size: 13px; line-height: 1.4; border: 1px solid #fde68a; background-color: #fef3c7; color: #78350f;">
                                                                <strong>Previous Dose Calculation:</strong> This dose is due after the <strong>previous dose actual completed date</strong>. If Dose 1 is completed late, Dose 2 shifts automatically.
                                                            </div>
                                                        ');
                                                    }
                                                    if ($timingType === 'doctor_manual_date') {
                                                        return new \Illuminate\Support\HtmlString('
                                                            <div class="dark:bg-purple-955/20 dark:border-purple-900/50 dark:text-purple-300" style="padding: 10px 14px; border-radius: 8px; font-size: 13px; line-height: 1.4; border: 1px solid #ddd6fe; background-color: #f5f3ff; color: #4c1d95;">
                                                                <strong>Doctor Manual Date:</strong> The system will not auto-calculate this dose date. The doctor sets it after checking the patient condition.
                                                            </div>
                                                        ');
                                                    }
                                                    return match ($targetType) {
                                                        'pregnancy' => new \Illuminate\Support\HtmlString('
                                                            <div class="dark:bg-blue-955/20 dark:border-blue-900/50 dark:text-blue-300" style="padding: 10px 14px; border-radius: 8px; font-size: 13px; line-height: 1.4; border: 1px solid #bfdbfe; background-color: #eff6ff; color: #1e3a8a;">
                                                                <strong>Pregnancy-Based Calculation:</strong> Scheduled relative to <strong>Pregnancy Start / Last Menstrual Period (LMP)</strong>. Use offset_value 28 and offset_unit weeks for 28 weeks.
                                                            </div>
                                                        '),
                                                        'baby', 'child' => new \Illuminate\Support\HtmlString('
                                                            <div class="dark:bg-blue-955/20 dark:border-blue-900/50 dark:text-blue-300" style="padding: 10px 14px; border-radius: 8px; font-size: 13px; line-height: 1.4; border: 1px solid #bfdbfe; background-color: #eff6ff; color: #1e3a8a;">
                                                                <strong>Baby Age-Based Calculation:</strong> Scheduled relative to the baby\'s <strong>Birth Date (DOB)</strong> (e.g. HepB is due at birth, Pentavalent at 6 Weeks).
                                                            </div>
                                                        '),
                                                        default => new \Illuminate\Support\HtmlString('
                                                            <div class="dark:bg-blue-955/20 dark:border-blue-900/50 dark:text-blue-300" style="padding: 10px 14px; border-radius: 8px; font-size: 13px; line-height: 1.4; border: 1px solid #bfdbfe; background-color: #eff6ff; color: #1e3a8a;">
                                                                <strong>Assignment-Date Calculation:</strong> Adult, travel, elderly, and hospital staff schedules start from <strong>template assignment / category start date</strong>, not patient DOB.
                                                            </div>
                                                        '),
                                                    };
                                                })
                                                ->columnSpan(12),
                                            Toggle::make('show_advanced')
                                                ->label('Show Advanced Configurations (Grace periods & age constraints)')
                                                ->default(false)
                                                ->onColor('success')
                                                ->offColor('danger')
                                                ->live()
                                                ->columnSpanFull(),
                                            TextInput::make('minimum_age_days')
                                                ->label('Minimum Age (Days)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->placeholder('e.g. 42')
                                                ->helperText('Earliest age patient can take this dose.')
                                                ->visible(fn($get) => (bool) $get('show_advanced'))
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                            TextInput::make('maximum_age_days')
                                                ->label('Maximum Age (Days)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->placeholder('e.g. 120')
                                                ->helperText('Latest age patient can take this dose.')
                                                ->visible(fn($get) => (bool) $get('show_advanced'))
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                            TextInput::make('grace_period_before_days')
                                                ->label('Grace Period Before (Days)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->placeholder('e.g. 4')
                                                ->helperText('Allowed days to receive dose early.')
                                                ->visible(fn($get) => (bool) $get('show_advanced'))
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                            TextInput::make('grace_period_after_days')
                                                ->label('Grace Period After (Days)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->placeholder('e.g. 7')
                                                ->helperText('Allowed days late before marked overdue.')
                                                ->visible(fn($get) => (bool) $get('show_advanced'))
                                                ->live(onBlur: true)
                                                ->columnSpan(3),
                                        ]),
                                ]),
                        ])
                        ->afterStateHydrated(function ($component, $record) {
                            if (! $record) {
                                $component->state([]);
                                return;
                            }

                            $items = $record->items()
                                ->orderBy('set_sort_order')
                                ->orderBy('sort_order')
                                ->get();

                            $grouped = [];
                            foreach ($items as $item) {
                                $setKey = $item->set_name ?? 'General';
                                if (! isset($grouped[$setKey])) {
                                    $grouped[$setKey] = [
                                        'set_name' => $item->set_name,
                                        'set_description' => $item->set_description,
                                        'set_sort_order' => $item->set_sort_order ?? 0,
                                        'doses' => [],
                                    ];
                                }

                                $hasAdvanced = ($item->minimum_age_days !== null && $item->minimum_age_days > 0)
                                    || ($item->maximum_age_days !== null && $item->maximum_age_days > 0)
                                    || ($item->grace_period_before_days > 0)
                                    || ($item->grace_period_after_days > 0);

                                $grouped[$setKey]['doses'][] = [
                                    'vaccination_id' => $item->vaccination_id,
                                    'dose_no' => $item->dose_no,
                                    'timing_type' => $item->effectiveTimingType(),
                                    'depends_on_previous_dose' => (bool) $item->depends_on_previous_dose,
                                    'interval_days' => $item->interval_days ?? 0,
                                    'interval_months' => $item->interval_months ?? 0,
                                    'interval_value' => $item->effectiveIntervalValue(),
                                    'interval_unit' => $item->effectiveIntervalUnit(),
                                    'due_after_months' => $item->due_after_months ?? 0,
                                    'due_after_days' => $item->due_after_days ?? 0,
                                    'offset_value' => $item->effectiveOffsetValue(),
                                    'offset_unit' => $item->effectiveOffsetUnit(),
                                    'recommended_age_label' => $item->recommended_age_label,
                                    'minimum_age_days' => $item->minimum_age_days,
                                    'maximum_age_days' => $item->maximum_age_days,
                                    'grace_period_before_days' => $item->grace_period_before_days ?? 0,
                                    'grace_period_after_days' => $item->grace_period_after_days ?? 0,
                                    'show_advanced' => $hasAdvanced,
                                ];
                            }

                            $component->state(array_values($grouped));
                        })
                        ->saveRelationshipsUsing(function ($record, $state) {
                            $record->items()->delete();

                            if (! is_array($state)) {
                                return;
                            }

                            foreach ($state as $setIndex => $setData) {
                                $setName = $setData['set_name'] ?? 'General';
                                $setDescription = $setData['set_description'] ?? null;
                                $setSortOrder = (int) ($setData['set_sort_order'] ?? $setIndex);

                                foreach ($setData['doses'] ?? [] as $doseData) {
                                    if (empty($doseData['vaccination_id'])) {
                                        continue;
                                    }

                                    $timingType = $doseData['timing_type'] ?? ((bool) ($doseData['depends_on_previous_dose'] ?? false) ? 'previous_dose' : 'base_date');
                                    $offsetValue = (int) ($doseData['offset_value'] ?? 0);
                                    $offsetUnit = $doseData['offset_unit'] ?? 'days';
                                    $intervalValue = (int) ($doseData['interval_value'] ?? 0);
                                    $intervalUnit = $doseData['interval_unit'] ?? 'days';

                                    [$dueAfterDays, $dueAfterMonths] = static::legacyValueColumns($offsetValue, $offsetUnit);
                                    [$intervalDays, $intervalMonths] = static::legacyValueColumns($intervalValue, $intervalUnit);

                                    $record->items()->create([
                                        'vaccination_id' => $doseData['vaccination_id'],
                                        'set_name' => $setName,
                                        'set_description' => $setDescription,
                                        'set_sort_order' => $setSortOrder,
                                        'dose_no' => (int) ($doseData['dose_no'] ?? 1),
                                        'depends_on_previous_dose' => $timingType === 'previous_dose',
                                        'timing_type' => $timingType,
                                        'interval_days' => $intervalDays,
                                        'interval_months' => $intervalMonths,
                                        'interval_value' => $intervalValue,
                                        'interval_unit' => $intervalUnit,
                                        'doctor_manual_date' => $timingType === 'doctor_manual_date',
                                        'due_after_months' => $dueAfterMonths,
                                        'due_after_days' => $dueAfterDays,
                                        'offset_value' => $offsetValue,
                                        'offset_unit' => $offsetUnit,
                                        'recommended_age_label' => $doseData['recommended_age_label'] ?? null,
                                        'minimum_age_days' => $doseData['minimum_age_days'] ?? null,
                                        'maximum_age_days' => $doseData['maximum_age_days'] ?? null,
                                        'grace_period_before_days' => (int) ($doseData['grace_period_before_days'] ?? 0),
                                        'grace_period_after_days' => (int) ($doseData['grace_period_after_days'] ?? 0),
                                    ]);
                                }
                            }
                        }),
                ])
                ->columnSpanFull(),

            Section::make('Vaccination Schedule Preview')
                ->description('Calculated preview using sample DOB/LMP/assignment date of Jan 01, 2026. Manual-date doses remain unset for doctor review.')
                ->collapsible()
                ->schema([
                    Placeholder::make('schedule_preview')
                        ->label('')
                        ->content(function ($get) {
                            $sets = $get('sets') ?: [];
                            if (empty($sets)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Add sets and doses to preview the calculated timeline.</p>');
                            }

                            $programId = $get('vaccination_program_id');
                            $targetType = 'baby';
                            if ($programId) {
                                $targetType = \App\Models\VaccinationProgram::where('id', $programId)->value('target_type') ?: 'baby';
                            }

                            // Simulated start date
                            $startDate = \Carbon\Carbon::parse('2026-01-01');
                            $ageBaseDate = $startDate->copy();
                            $previousDate = $startDate->copy();

                            $html = '<div class="space-y-6">';

                            foreach ($sets as $setIndex => $setData) {
                                $setName = $setData['set_name'] ?? 'New Set';
                                $setDescription = $setData['set_description'] ?? null;

                                $html .= '<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 p-4">';
                                $html .= '<div class="mb-3">';
                                $html .= '<h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">' . htmlspecialchars($setName) . '</h4>';
                                if ($setDescription) {
                                    $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . htmlspecialchars($setDescription) . '</p>';
                                }
                                $html .= '</div>';
                                $html .= '<div class="space-y-3">';

                                $doses = $setData['doses'] ?? [];
                                if (empty($doses)) {
                                    $html .= '<p class="text-xs text-gray-400">No doses in this set yet.</p>';
                                } else {
                                    foreach ($doses as $doseData) {
                                        $vaccinationId = $doseData['vaccination_id'] ?? null;
                                        $vaccinationName = 'Select a Vaccine...';
                                        if ($vaccinationId) {
                                            $vaccinationName = \App\Models\Vaccination::find($vaccinationId)?->name ?: 'Vaccine ID: ' . $vaccinationId;
                                        }

                                        $timingType = $doseData['timing_type'] ?? ((bool) ($doseData['depends_on_previous_dose'] ?? false) ? 'previous_dose' : 'base_date');

                                        if ($timingType === 'doctor_manual_date') {
                                            $scheduledDate = null;
                                        } else {
                                            $baseDate = $timingType === 'previous_dose' ? $previousDate : $ageBaseDate;
                                            $value = (int) ($timingType === 'previous_dose' ? ($doseData['interval_value'] ?? 0) : ($doseData['offset_value'] ?? 0));
                                            $unit = (string) ($timingType === 'previous_dose' ? ($doseData['interval_unit'] ?? 'days') : ($doseData['offset_unit'] ?? 'days'));
                                            $scheduledDate = static::addValueUnitToDate($baseDate, $value, $unit);
                                            $previousDate = $scheduledDate->copy()->startOfDay();
                                        }

                                        $recAge = $doseData['recommended_age_label'] ?? null;
                                        $graceBefore = (int) ($doseData['grace_period_before_days'] ?? 0);
                                        $graceAfter = (int) ($doseData['grace_period_after_days'] ?? 0);

                                        $html .= '<div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-900 rounded-lg shadow-sm gap-2">';
                                        $html .= '<div>';
                                        $html .= '<p class="text-xs font-bold text-primary-600 dark:text-primary-400">Dose ' . ($doseData['dose_no'] ?? 1) . ' — ' . htmlspecialchars($vaccinationName) . '</p>';
                                        if ($recAge) {
                                            $html .= '<p class="text-[10px] text-gray-500 mt-0.5">Recommended: ' . htmlspecialchars($recAge) . '</p>';
                                        }
                                        $html .= '</div>';
                                        $html .= '<div class="text-right shrink-0">';
                                        $html .= '<p class="text-xs font-bold text-gray-700 dark:text-gray-300">Expected: ' . ($scheduledDate ? $scheduledDate->format('d M Y') : 'Doctor will set date') . '</p>';
                                        $html .= '<p class="text-[10px] text-amber-600 dark:text-amber-500 mt-0.5">';

                                        $originLabel = $targetType === 'pregnancy' ? 'LMP' : (in_array($targetType, ['baby', 'child'], true) ? 'DOB' : 'start');

                                        if ($timingType === 'doctor_manual_date') {
                                            $html .= 'Manual date: doctor decides after patient review';
                                        } elseif ($timingType === 'previous_dose') {
                                            $html .= 'After ' . (int) ($doseData['interval_value'] ?? 0) . ' ' . htmlspecialchars((string) ($doseData['interval_unit'] ?? 'days')) . ' from previous completed dose';
                                        } else {
                                            $html .= 'After ' . (int) ($doseData['offset_value'] ?? 0) . ' ' . htmlspecialchars((string) ($doseData['offset_unit'] ?? 'days')) . ' from ' . $originLabel;
                                        }
                                        if ($graceBefore || $graceAfter) {
                                            $html .= ' | Grace: -' . $graceBefore . 'd/+' . $graceAfter . 'd';
                                        }
                                        $html .= '</p>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }
                                }

                                $html .= '</div>';
                                $html .= '</div>';
                            }

                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        })
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('template_details')
                ->view('filament.vaccination-templates.template-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('program.name')
                    ->label('Category')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('doctor_id')
                    ->label('Doctor')
                    ->formatStateUsing(fn($state, VaccinationTemplate $record): string => static::doctorDisplayName($record->doctor))
                    ->placeholder('-'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Doses')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state): string => ((bool) $state) ? 'Active' : 'Inactive')
                    ->color(fn($state): string => ((bool) $state) ? 'success' : 'gray'),
                TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('vaccination_program_id')
                    ->label('Category')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
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
            'index' => Pages\ListVaccinationTemplates::route('/'),
            'create' => Pages\CreateVaccinationTemplate::route('/create'),
            'view' => Pages\ViewVaccinationTemplate::route('/{record}'),
            'edit' => Pages\EditVaccinationTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['doctor.user', 'program'])
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

    private static function doctorDisplayName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '-';
        }

        return trim("{$doctor->first_name} {$doctor->last_name}")
            ?: (string) ($doctor->name ?: $doctor->user?->name ?: '-');
    }

    private static function addValueUnitToDate(\Carbon\Carbon $date, int $value, string $unit): \Carbon\Carbon
    {
        return match ($unit) {
            'weeks' => $date->copy()->addWeeks($value),
            'months' => $date->copy()->addMonths($value),
            'years' => $date->copy()->addYears($value),
            default => $date->copy()->addDays($value),
        };
    }

    private static function legacyValueColumns(int $value, string $unit): array
    {
        return match ($unit) {
            'weeks' => [$value * 7, 0],
            'months' => [0, $value],
            'years' => [0, $value * 12],
            default => [$value, 0],
        };
    }
}
