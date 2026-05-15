<?php

namespace App\Filament\Resources\PatientProfiles;

use App\Enums\BloodGroupOption;
use App\Enums\PatientProfileType;
use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\PatientProfiles\Pages\ListPatientProfiles;
use App\Models\Patient;
use App\Models\PatientProfile;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientProfileResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = PatientProfile::class;

    protected static ?string $navigationLabel = 'Patient Profiles';

    protected static ?string $slug = 'patient-profiles';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Patient Family Profiles',
            'icon' => 'heroicon-o-user-group',
            'sort' => 7,
            'group' => 'Clinical',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return check_permission(['patient-profiles.view_any', 'patient-profiles.view', 'patient-profiles.manage_own'])
            || $user?->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    public static function isSelfType(mixed $profileType): bool
    {
        if ($profileType instanceof PatientProfileType) {
            return $profileType === PatientProfileType::SELF;
        }

        return (string) $profileType === PatientProfileType::SELF->value;
    }

    public static function isPregnancyType(mixed $profileType): bool
    {
        if ($profileType instanceof PatientProfileType) {
            return $profileType === PatientProfileType::PREGNANCY;
        }

        return (string) $profileType === PatientProfileType::PREGNANCY->value;
    }

    public static function patientAccountName(?string $patientId): string
    {
        if (! $patientId) {
            return '—';
        }

        $patient = Patient::query()->find($patientId);

        return trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: ($patient?->email ?? '—');
    }

    public static function profileTypeLabel(mixed $profileType): string
    {
        if ($profileType instanceof PatientProfileType) {
            return $profileType->label();
        }

        return PatientProfileType::tryFrom((string) $profileType)?->label() ?? ucfirst((string) $profileType);
    }

    public static function hydrateFormDataForRecord(PatientProfile $record, array $data): array
    {
        if (! static::isSelfType($record->profile_type)) {
            return $data;
        }

        $record->loadMissing('patient');
        $patient = $record->patient;
        if (! $patient) {
            return $data;
        }

        return static::mergePatientHealthIntoFormData($data, $patient);
    }

    public static function applyPatientHealthToSelfProfile(PatientProfile $profile): void
    {
        if (! static::isSelfType($profile->profile_type)) {
            return;
        }

        $profile->loadMissing('patient');
        $patient = $profile->patient;
        if (! $patient) {
            return;
        }

        $patient->update([
            'gender' => $profile->gender,
            'date_of_birth' => $profile->date_of_birth,
            'blood_group' => $profile->blood_group,
            'weight' => $profile->weight,
            'height' => $profile->height,
        ]);
    }

    public static function syncSelfProfileNameFromPatient(PatientProfile $profile): void
    {
        if (! static::isSelfType($profile->profile_type)) {
            return;
        }

        $profile->loadMissing('patient');
        $patient = $profile->patient;
        if (! $patient) {
            return;
        }

        $name = trim("{$patient->first_name} {$patient->last_name}") ?: 'Self';
        if ($profile->name !== $name) {
            $profile->forceFill(['name' => $name])->saveQuietly();
        }
    }

    public static function fillSelfHealthFromPatient(?string $patientId, callable $set): void
    {
        if (! $patientId) {
            return;
        }

        $patient = Patient::query()->find($patientId);
        if (! $patient) {
            return;
        }

        $set('name', trim("{$patient->first_name} {$patient->last_name}") ?: 'Self');
        $set('gender', $patient->gender);
        $set('date_of_birth', $patient->date_of_birth);
        $set('blood_group', $patient->blood_group);
        $set('weight', $patient->weight);
        $set('height', $patient->height);
        $set('pregnancy_due_date', null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mergePatientHealthIntoFormData(array $data, Patient $patient): array
    {
        return array_merge($data, [
            'name' => trim("{$patient->first_name} {$patient->last_name}") ?: ($data['name'] ?? 'Self'),
            'gender' => $patient->gender,
            'date_of_birth' => $patient->date_of_birth,
            'blood_group' => $patient->blood_group,
            'weight' => $patient->weight,
            'height' => $patient->height,
            'pregnancy_due_date' => null,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        $syncSelfFromPatient = function ($state, callable $set, callable $get): void {
            if (! static::isSelfType($get('profile_type'))) {
                return;
            }

            static::fillSelfHealthFromPatient($get('patient_id'), $set);
        };

        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection(
                'Account & Profile Type',
                [
                    Select::make('patient_id')
                        ->label('Patient Account')
                        ->helperText('The login account that owns this profile.')
                        ->options(fn () => Patient::query()
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Patient $patient) => [
                                $patient->id => trim("{$patient->first_name} {$patient->last_name}") ?: $patient->email ?: $patient->id,
                            ]))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated($syncSelfFromPatient),
                    Select::make('profile_type')
                        ->label('Profile Type')
                        ->helperText('Self uses the patient account health record. Baby, pregnancy, and family use this profile only.')
                        ->options(PatientProfileType::options())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($syncSelfFromPatient): void {
                            if (static::isSelfType($state)) {
                                $syncSelfFromPatient(null, $set, $get);

                                return;
                            }

                            if (! $get('name')) {
                                $set('name', static::profileTypeLabel($state));
                            }
                        }),
                    Toggle::make('is_primary')
                        ->label('Primary Profile')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Used by default when no profile is selected in the app.')
                        ->default(false),
                ],
                'Link this schedule profile to a patient account and choose who it represents.',
                collapsedByDefault: false,
            ),
            static::slideOverSection(
                'Profile Identity',
                [
                    Placeholder::make('self_profile_notice')
                        ->label('Self Profile')
                        ->content(fn (callable $get): string => 'Health details for '
                            .static::patientAccountName($get('patient_id'))
                            .' are managed in the Health Details section below and saved to the patient account.')
                        ->visible(fn (callable $get): bool => static::isSelfType($get('profile_type'))),
                    TextInput::make('name')
                        ->label(fn (callable $get): string => static::isSelfType($get('profile_type'))
                            ? 'Display Name (Account Owner)'
                            : 'Profile Name')
                        ->helperText(fn (callable $get): string => static::isSelfType($get('profile_type'))
                            ? 'Filled automatically from the patient account name.'
                            : 'Example: Baby Aryan, Mom pregnancy profile, etc.')
                        ->required(fn (callable $get): bool => ! static::isSelfType($get('profile_type')))
                        ->disabled(fn (callable $get): bool => static::isSelfType($get('profile_type')))
                        ->dehydrated()
                        ->maxLength(255)
                        ->visible(fn (callable $get): bool => filled($get('profile_type'))),
                    Placeholder::make('family_profile_context')
                        ->label('Profile For')
                        ->content(fn (callable $get): string => (string) $get('name').' ('.static::profileTypeLabel($get('profile_type')).') — Account: '.static::patientAccountName($get('patient_id')))
                        ->visible(fn (callable $get): bool => filled($get('profile_type'))
                            && ! static::isSelfType($get('profile_type'))
                            && filled($get('name'))),
                ],
                'Name shown on the vaccination schedule for this family member or pregnancy.',
            ),
            static::slideOverSection(
                'Health Details',
                [
                    Placeholder::make('health_section_heading')
                        ->hiddenLabel()
                        ->content(function (callable $get): string {
                            if (static::isSelfType($get('profile_type'))) {
                                return 'Health details for '.static::patientAccountName($get('patient_id')).' (account owner). Changes are saved to the patient account.';
                            }

                            $name = (string) ($get('name') ?: 'this profile');

                            return 'Health details for '.$name.' ('.static::profileTypeLabel($get('profile_type')).'). Stored on this profile only.';
                        }),
                    Select::make('gender')
                        ->label('Gender')
                        ->helperText('Used for vaccines with gender-specific rules.')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                        ]),
                    DatePicker::make('date_of_birth')
                        ->label('Date of Birth')
                        ->helperText('Used to calculate age-based schedule rules.'),
                    DatePicker::make('pregnancy_due_date')
                        ->label('Pregnancy Due Date')
                        ->helperText('Only for pregnancy profiles.')
                        ->visible(fn (callable $get): bool => static::isPregnancyType($get('profile_type'))),
                    Select::make('blood_group')
                        ->label('Blood Group')
                        ->options(BloodGroupOption::labels())
                        ->searchable(),
                    TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01),
                    TextInput::make('height')
                        ->label('Height (cm)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01),
                ],
                'Vitals and demographics used for schedule rules and the patient app.',
                icon: 'heroicon-o-heart',
            ),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('patient.first_name')
                    ->label('Patient Account')
                    ->formatStateUsing(fn ($state, PatientProfile $record): string => trim(($record->patient?->first_name ?? '').' '.($record->patient?->last_name ?? '')) ?: '-')
                    ->searchable(),
                TextColumn::make('profile_type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PatientProfileType ? $state->label() : (PatientProfileType::tryFrom((string) $state)?->label() ?? ucfirst((string) $state)))
                    ->sortable(),
                TextColumn::make('gender')->placeholder('-'),
                TextColumn::make('date_of_birth')->date()->placeholder('-'),
                IconColumn::make('is_primary')->boolean(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->mutateRecordDataUsing(fn (array $data, PatientProfile $record): array => static::hydrateFormDataForRecord($record, $data)),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientProfiles::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('patient')
            ->withoutGlobalScopes();
    }
}
