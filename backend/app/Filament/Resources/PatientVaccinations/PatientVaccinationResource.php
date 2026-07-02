<?php

namespace App\Filament\Resources\PatientVaccinations;

use App\Enums\VaccinationStatus;
use App\Filament\Resources\PatientVaccinations\Pages;

use App\Models\Doctor;
use App\Models\Patient;

use App\Models\PatientVaccination;
use App\Models\Vaccination;
use App\Models\VaccinationTemplate;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PatientVaccinationResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = PatientVaccination::class;

    protected static ?string $navigationLabel = 'Patient Vaccinations';

    protected static ?string $slug = 'patient-vaccinations';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Patient Vaccine Doses',
            'icon' => 'heroicon-o-clipboard-document-check',
            'sort' => 6,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['patient-vaccinations.view_any', 'patient-vaccinations.view', 'patient-vaccinations.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Patient and Vaccine')
                ->description('Choose the patient account and the vaccine dose being scheduled.')
                ->extraAttributes(['class' => 'patient-vaccination-form patient-vaccination-primary'])
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('patient_id')
                                ->label('Patient Account')
                                ->options(fn() => Patient::query()
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn(Patient $patient) => [
                                        $patient->id => trim("{$patient->first_name} {$patient->last_name}") ?: ($patient->email ?: $patient->id),
                                    ]))
                                ->searchable()
                                ->required()
                                ->live(),
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->options(fn() => Doctor::query()
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn(Doctor $doctor) => [
                                        $doctor->id => trim("{$doctor->first_name} {$doctor->last_name}") ?: ($doctor->name ?: $doctor->id),
                                    ]))
                                ->searchable()
                                ->required()
                                ->default(fn() => Auth::user()?->doctor?->id),
                            Select::make('vaccination_id')
                                ->label('Vaccine')
                                ->options(fn() => Vaccination::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->optionsLimit(1000)
                                ->required(),
                            Select::make('vaccination_template_id')
                                ->label('Source Template')
                                ->options(fn() => VaccinationTemplate::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->helperText('Optional. Use this when this dose came from a schedule template.'),
                            TextInput::make('dose_no')
                                ->label('Dose No.')
                                ->numeric()
                                ->minValue(1),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Schedule')
                ->description('Set where this dose appears in the schedule and when it is due.')
                ->extraAttributes(['class' => 'patient-vaccination-form patient-vaccination-schedule'])
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(VaccinationStatus::options())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->default(VaccinationStatus::UPCOMING->value),
                            DatePicker::make('expected_date')
                                ->label('Expected Date (System Calculated)')
                                ->disabled()
                                ->dehydrated(),
                            DatePicker::make('assigned_date')
                                ->label('Assigned / Category Start Date')
                                ->helperText('Adult, travel, elderly, and hospital staff schedules use this as base date.'),
                            DatePicker::make('due_date')
                                ->label('Current Due Date'),
                            DatePicker::make('changed_date')
                                ->label('Changed Date (Doctor Override)')
                                ->helperText('Use when doctor reschedules after reviewing the patient.'),
                            DatePicker::make('completed_date')
                                ->label('Completed Date')
                                ->visible(fn($get) => $get('status') === VaccinationStatus::COMPLETED->value),
                            DatePicker::make('overdue_date')
                                ->label('Overdue Date')
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn($get) => in_array((string) $get('status'), [VaccinationStatus::OVERDUE->value, VaccinationStatus::MISSED->value], true)),
                            DatePicker::make('missed_date')
                                ->label('Missed Date')
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn($get) => $get('status') === VaccinationStatus::MISSED->value),
                            TextInput::make('grace_period_before_days')
                                ->label('Grace Period Before (Days)')
                                ->numeric()
                                ->default(0),
                            TextInput::make('grace_period_after_days')
                                ->label('Grace Period After (Days)')
                                ->numeric()
                                ->default(0),
                            TextInput::make('skipped_reason')
                                ->label('Skipped Reason')
                                ->placeholder('Specify medical reason if skipped')
                                ->visible(fn($get) => $get('status') === VaccinationStatus::SKIPPED_BY_DOCTOR->value)
                                ->required(fn($get) => $get('status') === VaccinationStatus::SKIPPED_BY_DOCTOR->value),
                            TextInput::make('on_hold_reason')
                                ->label('On Hold Reason')
                                ->placeholder('Specify medical reason if paused')
                                ->visible(fn($get) => $get('status') === VaccinationStatus::ON_HOLD->value)
                                ->required(fn($get) => $get('status') === VaccinationStatus::ON_HOLD->value),
                        ]),
                    Grid::make(4)
                        ->schema([
                            TextInput::make('set_name')
                                ->label('Schedule Set')
                                ->placeholder('Set 1 (Birth)'),
                            TextInput::make('recommended_age_label')
                                ->label('Recommended Age')
                                ->placeholder('Birth, 6 weeks, 9 months'),
                            TextInput::make('due_after_months')
                                ->label('Legacy Offset Months')
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                            TextInput::make('due_after_days')
                                ->label('Legacy Offset Days')
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                            Textarea::make('set_description')
                                ->label('Set Note')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Administration Notes')
                ->description('Optional details captured when a dose is given.')
                ->extraAttributes(['class' => 'patient-vaccination-form patient-vaccination-admin'])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('batch_number')
                                ->label('Batch Number'),
                            TextInput::make('manufacturer')
                                ->label('Manufacturer'),
                            TextInput::make('dose_amount')
                                ->label('Dose Amount'),
                            TextInput::make('route')
                                ->label('Route'),
                            TextInput::make('site')
                                ->label('Site'),
                            TextInput::make('given_by')
                                ->label('Given By'),
                            TextInput::make('given_at')
                                ->label('Given At')
                                ->columnSpanFull(),
                            Textarea::make('doctor_notes')
                                ->label('Doctor Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                            Textarea::make('side_effect_observed')
                                ->label('Side Effects Observed')
                                ->rows(2),
                            Textarea::make('patient_reaction')
                                ->label('Patient Reaction')
                                ->rows(2),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('vaccination_details')
                ->view('filament.patient-vaccinations.vaccination-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn($state, PatientVaccination $record): string => trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('patient', function (Builder $patientQuery) use ($search) {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile_no', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('patient.mobile_no')
                    ->label('Patient Phone')
                    ->toggleable(),
                TextColumn::make('doctor.first_name')
                    ->label('Doctor')
                    ->formatStateUsing(fn($state, PatientVaccination $record): string => trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? '')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('doctor', function (Builder $doctorQuery) use ($search) {
                            $doctorQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('vaccination.name')
                    ->label('Vaccination')
                    ->searchable(),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('template.program.name')
                    ->label('Category')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('set_name')
                    ->label('Set')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recommended_age_label')
                    ->label('Recommended Age')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('dose_no')
                    ->label('Dose')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state, PatientVaccination $record) => match (static::effectiveStatus($record)) {
                        VaccinationStatus::COMPLETED->value => 'success',
                        VaccinationStatus::DUE_TODAY->value, 'due' => 'warning',
                        VaccinationStatus::DUE_SOON->value => 'info',
                        VaccinationStatus::PENDING->value => 'warning',
                        VaccinationStatus::SCHEDULED->value => 'info',
                        VaccinationStatus::OVERDUE->value, 'overdue' => 'danger',
                        VaccinationStatus::MISSED->value, VaccinationStatus::CANCELLED->value => 'danger',
                        VaccinationStatus::ON_HOLD->value, VaccinationStatus::RESCHEDULED->value => 'gray',
                        VaccinationStatus::SKIPPED_BY_DOCTOR->value => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state, PatientVaccination $record): string => static::statusLabel(static::effectiveStatus($record)))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status_timeline')
                    ->label('Status Timeline')
                    ->badge()
                    ->formatStateUsing(fn($state, PatientVaccination $record): string => static::timelineLabel($record))
                    ->color(fn($state, PatientVaccination $record): string => static::timelineColor($record))
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('reminder_sent')
                    ->boolean()
                    ->label('Reminder Sent'),
                TextColumn::make('next_reminder_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->options(fn() => Patient::query()
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn(Patient $patient) => [
                            $patient->id => trim("{$patient->first_name} {$patient->last_name}") ?: ($patient->email ?: $patient->id),
                        ]))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('vaccination_template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('program')
                    ->label('Category')
                    ->options(fn() => \App\Models\VaccinationProgram::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn(Builder $query, $programId) => $query->whereHas('template', fn(Builder $templateQuery) => $templateQuery->where('vaccination_program_id', $programId))
                    ))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(VaccinationStatus::options()),
                Filter::make('current')
                    ->label('Current / Due Now')
                    ->query(fn(Builder $query): Builder => $query->whereIn('status', [
                        VaccinationStatus::DUE_SOON->value,
                        VaccinationStatus::DUE_TODAY->value,
                        VaccinationStatus::OVERDUE->value,
                    ])),
                Filter::make('upcoming')
                    ->label('Upcoming')
                    ->query(fn(Builder $query): Builder => $query->where('status', VaccinationStatus::UPCOMING->value)),
                Filter::make('due_date')
                    ->form([
                        DatePicker::make('from')->label('Due From'),
                        DatePicker::make('until')->label('Due Until'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn(Builder $query, $date) => $query->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn(Builder $query, $date) => $query->whereDate('due_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_date')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatientVaccinations::route('/'),
            'create' => Pages\CreatePatientVaccination::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient.user', 'doctor.user', 'vaccination', 'template.program'])
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->withoutGlobalScopes();
    }

    protected static function effectiveStatus(PatientVaccination $record): string
    {
        $status = $record->status instanceof VaccinationStatus ? $record->status->value : (string) $record->status;

        if ($status === VaccinationStatus::COMPLETED->value) {
            return $status;
        }

        if (
            in_array($status, [VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value], true)
            && $record->scheduled_date
        ) {
            if ($record->scheduled_date->isToday()) {
                return 'due';
            }

            if ($record->scheduled_date->isPast()) {
                return 'overdue';
            }
        }

        return $status ?: VaccinationStatus::PENDING->value;
    }

    protected static function statusLabel(string $status): string
    {
        if ($status === 'due') {
            return 'Due Today';
        }

        return VaccinationStatus::tryFrom($status)?->label() ?? str($status)->replace('_', ' ')->title()->toString();
    }

    protected static function timelineLabel(PatientVaccination $record): string
    {
        $effectiveStatus = static::effectiveStatus($record);

        $date = match ($effectiveStatus) {
            VaccinationStatus::COMPLETED->value => $record->completed_date,
            VaccinationStatus::MISSED->value => $record->missed_date,
            VaccinationStatus::OVERDUE->value, 'overdue' => $record->overdue_date,
            VaccinationStatus::RESCHEDULED->value => $record->changed_date,
            default => $record->due_date,
        };

        $label = match ($effectiveStatus) {
            VaccinationStatus::COMPLETED->value => 'Completed',
            VaccinationStatus::MISSED->value => 'Missed',
            VaccinationStatus::OVERDUE->value, 'overdue' => 'Overdue',
            VaccinationStatus::RESCHEDULED->value => 'Rescheduled',
            default => 'Due',
        };

        return $date ? "{$label}: {$date->format('d M Y')}" : $label;
    }

    protected static function timelineColor(PatientVaccination $record): string
    {
        $effectiveStatus = static::effectiveStatus($record);

        return match ($effectiveStatus) {
            VaccinationStatus::COMPLETED->value => 'success',
            VaccinationStatus::MISSED->value, VaccinationStatus::OVERDUE->value, 'overdue' => 'danger',
            VaccinationStatus::RESCHEDULED->value => 'gray',
            VaccinationStatus::DUE_SOON->value, VaccinationStatus::DUE_TODAY->value, 'due' => 'warning',
            default => 'info',
        };
    }
}