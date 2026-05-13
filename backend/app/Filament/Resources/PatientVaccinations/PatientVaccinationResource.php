<?php

namespace App\Filament\Resources\PatientVaccinations;

use App\Enums\VaccinationStatus;
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
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = PatientVaccination::class;

    protected static ?string $navigationLabel = 'Patient Vaccinations';

    protected static ?string $slug = 'patient-vaccinations';

    public static function getSidebarOptions(): array
    {
        return [
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
        return $schema->components([
            Placeholder::make('patient_name')
                ->label('Patient')
                ->content(fn (?PatientVaccination $record): string => trim(($record?->patient?->first_name ?? '').' '.($record?->patient?->last_name ?? '')) ?: '-'),
            Placeholder::make('doctor_name')
                ->label('Doctor')
                ->content(fn (?PatientVaccination $record): string => trim(($record?->doctor?->first_name ?? '').' '.($record?->doctor?->last_name ?? '')) ?: '-'),
            Placeholder::make('vaccination_name')
                ->label('Vaccination')
                ->content(fn (?PatientVaccination $record): string => (string) ($record?->vaccination?->name ?? '-')),
            Placeholder::make('set_name_label')
                ->label('Set name')
                ->content(fn (?PatientVaccination $record): string => (string) ($record?->set_name ?: '-')),
            Select::make('status')
                ->options([
                    VaccinationStatus::PENDING->value => VaccinationStatus::PENDING->label(),
                    VaccinationStatus::SCHEDULED->value => VaccinationStatus::SCHEDULED->label(),
                    VaccinationStatus::COMPLETED->value => VaccinationStatus::COMPLETED->label(),
                    VaccinationStatus::MISSED->value => VaccinationStatus::MISSED->label(),
                    VaccinationStatus::CANCELLED->value => VaccinationStatus::CANCELLED->label(),
                ])
                ->required(),
            TextInput::make('dose_no')
                ->numeric()
                ->minValue(1),
            DatePicker::make('first_dose_date'),
            DatePicker::make('scheduled_date'),
            DatePicker::make('completed_date'),
            TextInput::make('due_after_months')
                ->numeric()
                ->minValue(0),
            TextInput::make('due_after_days')
                ->numeric()
                ->minValue(0),
            TextInput::make('batch_number')
                ->maxLength(255),
            TextInput::make('manufacturer')
                ->maxLength(255),
            TextInput::make('given_at')
                ->maxLength(255),
            TextInput::make('given_by')
                ->maxLength(255),
            Textarea::make('doctor_notes')
                ->columnSpanFull(),
            Textarea::make('side_effect_observed')
                ->columnSpanFull(),
            Textarea::make('patient_reaction')
                ->columnSpanFull(),
        ])->columns(2);
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
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        VaccinationStatus::PENDING->value => VaccinationStatus::PENDING->label(),
                        VaccinationStatus::SCHEDULED->value => VaccinationStatus::SCHEDULED->label(),
                        VaccinationStatus::COMPLETED->value => VaccinationStatus::COMPLETED->label(),
                        VaccinationStatus::MISSED->value => VaccinationStatus::MISSED->label(),
                        VaccinationStatus::CANCELLED->value => VaccinationStatus::CANCELLED->label(),
                    ]),
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
            ->with(['patient.user', 'doctor.user', 'vaccination', 'template'])
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->withoutGlobalScopes();
    }
}
