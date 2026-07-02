<?php

namespace App\Filament\Resources\PatientVaccinationPrograms;

use App\Enums\PatientProfileType;
use App\Enums\PatientVaccinationProgramStatus;
use App\Filament\Resources\PatientVaccinationPrograms\Pages;
use App\Models\Doctor;
use App\Models\PatientProfile;
use App\Models\PatientVaccinationProgram;
use App\Models\VaccinationTemplate;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;

class PatientVaccinationProgramResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = PatientVaccinationProgram::class;

    protected static ?string $navigationLabel = 'Assigned Vaccine Programs';

    protected static ?string $slug = 'patient-vaccination-programs';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Assigned Vaccine Programs',
            'icon' => 'heroicon-o-clipboard-document-check',
            'sort' => 8,
            'group' => 'Clinical',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return check_permission(['patient-vaccination-programs.view_any', 'patient-vaccination-programs.view', 'patient-vaccination-programs.manage_own'])
            || $user?->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assign Schedule To Patient Profile')
                ->description('Choose the profile and schedule template. Saving creates all patient vaccine doses.')
                ->schema([
                    Select::make('patient_profile_id')
                        ->label('Patient Profile')
                        ->helperText('The person who will receive this schedule (self, baby, pregnancy, or family member).')
                        ->options(fn () => PatientProfile::query()
                            ->with('patient')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function (PatientProfile $profile) {
                                $account = trim(($profile->patient?->first_name ?? '').' '.($profile->patient?->last_name ?? ''));
                                $type = $profile->profile_type instanceof PatientProfileType
                                    ? $profile->profile_type->label()
                                    : (PatientProfileType::tryFrom((string) $profile->profile_type)?->label() ?? ucfirst((string) $profile->profile_type));

                                return [
                                    $profile->id => $profile->name.' ('.$type.') — Account: '.($account ?: '—'),
                                ];
                            }))
                        ->searchable()
                        ->required(),
                    Select::make('vaccination_template_id')
                        ->label('Vaccination Schedule Template')
                        ->helperText('Template to assign. The program linked to this template is saved automatically.')
                        ->options(fn () => VaccinationTemplate::query()
                            ->with('program')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (VaccinationTemplate $template) => [$template->id => $template->name.' - '.($template->program?->name ?? 'No program')]))
                        ->searchable()
                        ->required(),
                    Select::make('doctor_id')
                        ->label('Doctor')
                        ->helperText('Doctor responsible for this vaccination plan.')
                        ->options(fn () => Doctor::query()
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Doctor $doctor) => [$doctor->id => trim("{$doctor->first_name} {$doctor->last_name}") ?: $doctor->name ?: $doctor->id]))
                        ->searchable()
                        ->required(),
                    DatePicker::make('start_date')
                        ->helperText('Base date used to calculate due dates.')
                        ->required(),
                    Select::make('status')
                        ->helperText('Active means the schedule is currently running.')
                        ->options(PatientVaccinationProgramStatus::options())
                        ->default(PatientVaccinationProgramStatus::ACTIVE->value)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('program_details')
                ->view('filament.patient-vaccination-programs.program-view')
                ->state(fn ($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patientProfile.name')->label('Profile')->searchable(),
                TextColumn::make('patientProfile.profile_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PatientProfileType ? $state->label() : (PatientProfileType::tryFrom((string) $state)?->label() ?? ucfirst((string) $state))),
                TextColumn::make('patientProfile.patient.first_name')
                    ->label('Patient Account')
                    ->formatStateUsing(fn ($state, PatientVaccinationProgram $record): string => trim(($record->patientProfile?->patient?->first_name ?? '').' '.($record->patientProfile?->patient?->last_name ?? '')) ?: '-'),
                TextColumn::make('vaccinationProgram.name')->label('Program')->searchable(),
                TextColumn::make('vaccinationTemplate.name')->label('Template')->searchable(),
                TextColumn::make('doctor.first_name')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($state, PatientVaccinationProgram $record): string => trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? '')) ?: '-'),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PatientVaccinationProgramStatus ? $state->label() : (PatientVaccinationProgramStatus::tryFrom((string) $state)?->label() ?? ucfirst((string) $state)))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PatientVaccinationProgramStatus::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatientVaccinationPrograms::route('/'),
            'create' => Pages\CreatePatientVaccinationProgram::route('/create'),
            'view' => Pages\ViewPatientVaccinationProgram::route('/{record}'),
            'edit' => Pages\EditPatientVaccinationProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patientProfile.patient', 'vaccinationProgram', 'vaccinationTemplate', 'doctor'])
            ->withoutGlobalScopes();
    }
}
