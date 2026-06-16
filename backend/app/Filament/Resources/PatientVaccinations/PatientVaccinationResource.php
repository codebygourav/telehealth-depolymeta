<?php

namespace App\Filament\Resources\PatientVaccinations;

use App\Enums\VaccinationStatus;
use App\Filament\Resources\PatientVaccinations\Pages;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientProfile;
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
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
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
                ->description('Choose the patient profile and the vaccine dose being scheduled.')
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
                                ->live()
                                ->afterStateUpdated(fn(callable $set) => $set('patient_profile_id', null)),
                            Select::make('patient_profile_id')
                                ->label('Vaccination Profile')
                                ->options(function (callable $get): array {
                                    if (! $get('patient_id')) {
                                        return [];
                                    }

                                    return PatientProfile::query()
                                        ->where('patient_id', $get('patient_id'))
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn(PatientProfile $profile) => [
                                            $profile->id => $profile->name ?: $profile->id,
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Select the child or family member profile when applicable.'),
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
                    Grid::make(4)
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(VaccinationStatus::options())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(VaccinationStatus::PENDING->value),
                            DatePicker::make('scheduled_date')
                                ->label('Scheduled Date'),
                            DatePicker::make('first_dose_date')
                                ->label('First Dose Date'),
                            DatePicker::make('completed_date')
                                ->label('Completed Date'),
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
                                ->label('Months From Start')
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                            TextInput::make('due_after_days')
                                ->label('Days From Start')
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
                TextColumn::make('patientProfile.name')
                    ->label('Profile')
                    ->placeholder('-')
                    ->searchable()
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
                    ->label('Program')
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
                    ->badge()
                    ->color(fn($state) => match ($state instanceof VaccinationStatus ? $state->value : (string) $state) {
                        VaccinationStatus::COMPLETED->value => 'success',
                        VaccinationStatus::PENDING->value => 'warning',
                        VaccinationStatus::SCHEDULED->value => 'info',
                        VaccinationStatus::MISSED->value, VaccinationStatus::CANCELLED->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => $state instanceof VaccinationStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('first_dose_date')
                    ->date()
                    ->label('First Dose Date')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
                IconColumn::make('reminder_sent')
                    ->boolean()
                    ->label('Reminder Sent'),
                TextColumn::make('next_reminder_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VaccinationStatus::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->groups([
                Group::make('set_name')
                    ->label('Schedule Set')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(PatientVaccination $record): string => $record->set_name ?: 'No schedule set')
                    ->collapsible(),
            ])
            ->defaultGroup('set_name')
            ->defaultSort('set_sort_order')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatientVaccinations::route('/'),
            'create' => Pages\CreatePatientVaccination::route('/create'),
            'view' => Pages\ViewPatientVaccination::route('/{record}'),
            'edit' => Pages\EditPatientVaccination::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient.user', 'doctor.user', 'vaccination', 'template.program', 'patientProfile'])
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->withoutGlobalScopes();
    }
}
