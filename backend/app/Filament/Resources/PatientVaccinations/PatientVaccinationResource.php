<?php

namespace App\Filament\Resources\PatientVaccinations;

use App\Enums\VaccinationStatus;
use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\PatientVaccinations\Pages\ListPatientVaccinations;
use App\Models\PatientVaccination;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PatientVaccinationResource extends Resource
{
    use ConfiguresSlideOverSections;
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
            'group' => 'Clinical',
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
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('Dose Information', [
                Placeholder::make('patient_name')
                    ->label('Patient Account')
                    ->content(fn (?PatientVaccination $record): string => trim(($record?->patient?->first_name ?? '').' '.($record?->patient?->last_name ?? '')) ?: '-'),
                Placeholder::make('patient_profile_name')
                    ->label('Vaccination Profile')
                    ->content(function (?PatientVaccination $record): string {
                        $name = $record?->patientProfile?->name ?? '-';
                        $profileType = $record?->patientProfile?->profile_type ?? null;

                        if ($profileType instanceof \App\Enums\PatientProfileType) {
                            $typeLabel = $profileType->label();
                        } else {
                            $typeLabel = $profileType ? (is_object($profileType) && method_exists($profileType, 'label') ? $profileType->label() : (string) $profileType) : null;
                        }

                        return (string) $name . ($typeLabel ? ' (' . $typeLabel . ')' : '');
                    }),

                Placeholder::make('doctor_name')
                    ->label('Doctor')
                    ->content(fn (?PatientVaccination $record): string => trim(($record?->doctor?->first_name ?? '').' '.($record?->doctor?->last_name ?? '')) ?: '-'),
                Placeholder::make('vaccination_name')
                    ->label('Vaccine')
                    ->content(fn (?PatientVaccination $record): string => (string) ($record?->vaccination?->name ?? '-')),
                Placeholder::make('set_name_label')
                    ->label('Schedule Set')
                    ->content(fn (?PatientVaccination $record): string => (string) ($record?->set_name ?: '-')),
                Select::make('status')
                    ->helperText('Current status of this dose.')
                    ->options(VaccinationStatus::options())
                    ->required(),
                TextInput::make('dose_no')
                    ->label('Dose Number')
                    ->helperText('Which dose this is in the schedule.')
                    ->numeric()
                    ->minValue(1),
                DatePicker::make('first_dose_date')
                    ->helperText('Original start date used for this schedule.'),
                DatePicker::make('scheduled_date')
                    ->helperText('Due date for this dose.'),
                DatePicker::make('completed_date')
                    ->helperText('Date when the dose was given.'),
                TextInput::make('due_after_months')
                    ->helperText('Months after start date saved from template.')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('due_after_days')
                    ->helperText('Days after start date saved from template.')
                    ->numeric()
                    ->minValue(0),
            ], 'Review the generated patient dose and update dates or status.'),
            static::slideOverSection('Administration Details', [
                TextInput::make('batch_number')
                    ->helperText('Vaccine batch or lot number.')
                    ->maxLength(255),
                TextInput::make('manufacturer')
                    ->helperText('Manufacturer used for this patient dose.')
                    ->maxLength(255),
                TextInput::make('route')
                    ->helperText('How it was given, for example oral or injection.')
                    ->maxLength(255),
                TextInput::make('site')
                    ->helperText('Body site, for example left thigh or upper arm.')
                    ->maxLength(255),
                TextInput::make('dose_amount')
                    ->helperText('Dose amount, for example 0.5 ml.')
                    ->maxLength(255),
                TextInput::make('given_at')
                    ->helperText('Clinic, hospital, or location where it was given.')
                    ->maxLength(255),
                TextInput::make('given_by')
                    ->helperText('Name of person who administered it.')
                    ->maxLength(255),
            ], 'Fill these when the vaccine dose is given.'),
            static::slideOverSection('Notes And Reaction', [
                Textarea::make('doctor_notes')
                    ->helperText('Doctor notes for this dose.')
                    ->rows(3),
                Textarea::make('side_effect_observed')
                    ->helperText('Any side effects observed after dose.')
                    ->rows(3),
                Textarea::make('patient_reaction')
                    ->helperText('Patient or parent reported reaction.')
                    ->rows(3),
            ], 'Optional clinical notes for follow-up.', icon: 'heroicon-o-chat-bubble-left-right'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state, PatientVaccination $record): string => trim(($record->patient?->first_name ?? '').' '.($record->patient?->last_name ?? '')))
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
                    ->formatStateUsing(fn ($state, PatientVaccination $record): string => trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? '')))
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
                    ->toggleable(),
                TextColumn::make('recommended_age_label')
                    ->label('Recommended Age')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('dose_no')
                    ->label('Dose')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof VaccinationStatus ? $state->value : (string) $state) {
                        VaccinationStatus::COMPLETED->value => 'success',
                        VaccinationStatus::PENDING->value => 'warning',
                        VaccinationStatus::SCHEDULED->value => 'info',
                        VaccinationStatus::MISSED->value, VaccinationStatus::CANCELLED->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => $state instanceof VaccinationStatus ? $state->label() : ucfirst((string) $state))
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
                    EditAction::make()->slideOver(),
                ]),
            ])
            ->defaultSort('set_sort_order')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientVaccinations::route('/'),
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
