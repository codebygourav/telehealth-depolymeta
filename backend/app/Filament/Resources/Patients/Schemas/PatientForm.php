<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\BloodGroupOption;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Login Details')
                        ->description('Create and activate the patient login first. The same email and mobile number will be saved to both the user and patient records.')
                        ->schema([
                            Hidden::make('create_user_account')
                                ->default(true)
                                ->dehydrated(true),
                            Hidden::make('draft_patient_id')
                                ->default(fn ($record) => $record?->getKey())
                                ->dehydrated(true),
                            Grid::make(3)
                                ->schema([
                                    Select::make('source')
                                        ->label('Source')
                                        ->options([
                                            'app' => 'Mobile App',
                                            'website' => 'Website',
                                            'internal' => 'Internal',
                                        ])
                                        ->default('internal')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('create_user_account', true)),
                                    TextInput::make('first_name')
                                        ->label('First Name')
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->label('Last Name'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required()
                                        ->helperText('Used for admin panel login, mobile app login, and password reset.')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record && $record->user_id && $record->user) {
                                                $component->state($record->user->email ?? $state);
                                            }
                                        }),
                                    TextInput::make('user_password')
                                        ->label('Login Password')
                                        ->password()
                                        ->revealable()
                                        ->helperText(function (string $operation) {
                                            return $operation === 'edit'
                                                ? 'Leave blank to keep the current password.'
                                                : 'Optional. If left blank, the default password `Patient@123` will be used.';
                                        })
                                        ->dehydrated(fn ($state) => filled($state)),
                                    TextInput::make('mobile_no')
                                        ->label('Mobile Number')
                                        ->tel()
                                        ->maxLength(20)
                                        ->required()
                                        ->helperText('Saved as both the patient mobile number and the linked user phone number.')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record && $record->user_id && $record->user) {
                                                $component->state($record->user->phone ?? $state);
                                            }
                                        }),
                                    
                                ]),
                            
                        ])
                        ->afterValidation(function (Get $get, Set $set, $livewire) {
                            if (method_exists($livewire, 'persistAccountStep')) {
                                $livewire->persistAccountStep($get, $set);
                            }
                        }),

                    Step::make('Patient Profile')
                        ->description('Add the remaining patient details. The login created in step 1 stays linked to this patient profile.')
                        ->schema([
                            Section::make('Login Summary')
                                ->description('Review the active login details before saving the rest of the patient profile.')
                                ->schema([
                                    Placeholder::make('login_email_summary')
                                        ->label('Login Email')
                                        ->content(fn (Get $get) => $get('email') ?: '-'),
                                    Placeholder::make('login_phone_summary')
                                        ->label('Login Phone')
                                        ->content(fn (Get $get) => $get('mobile_no') ?: '-'),
                                    Placeholder::make('login_password_summary')
                                        ->label('Password')
                                        ->content(function (Get $get, string $operation): string {
                                            if ($operation === 'edit') {
                                                return filled($get('user_password'))
                                                    ? 'Will be updated on save'
                                                    : 'Current password unchanged';
                                            }

                                            return filled($get('user_password'))
                                                ? 'Custom password set'
                                                : 'Default password: Patient@123';
                                        }),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),

                            Section::make('Patient Status')
                                ->schema([
                                    Toggle::make('is_existing_patient')
                                        ->label('Is Existing Patient')
                                        ->onIcon('heroicon-o-check')
                                        ->offIcon('heroicon-o-x-mark')
                                        ->offColor('gray')
                                        ->onColor('success')
                                        ->default(false)
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if (! $state) {
                                                $set('existing_patient_id', null);
                                            }
                                        }),
                                    TextInput::make('existing_patient_id')
                                        ->label('Existing Patient ID')
                                        ->visible(fn (Get $get) => $get('is_existing_patient') === true),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Personal Information')
                                ->schema([
                                    Select::make('gender')
                                        ->label('Gender')
                                        ->options(GenderOption::labels())
                                        ->native(false)
                                        ->searchable()
                                        ->dehydrateStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : $state)
                                        ->afterStateHydrated(fn ($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                                    DatePicker::make('date_of_birth')
                                        ->label('Date of Birth')
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if ($state) {
                                                $set('age', now()->diffInYears($state));
                                            }
                                        }),
                                    TextInput::make('age')
                                        ->label('Age')
                                        ->numeric()
                                        ->readOnly(),
                                    Select::make('marital_status')
                                        ->label('Marital Status')
                                        ->options(MaritalStatus::labels())
                                        ->native(false)
                                        ->searchable()
                                        ->dehydrateStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : $state)
                                        ->afterStateHydrated(fn ($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                                    Select::make('blood_group')
                                        ->label('Blood Group')
                                        ->options(BloodGroupOption::labels())
                                        ->native(false)
                                        ->searchable()
                                        ->dehydrateStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : $state)
                                        ->afterStateHydrated(fn ($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                                    Textarea::make('bio')
                                        ->label('Short Bio')
                                        ->placeholder('Brief patient note or short bio')
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),

                            Section::make('Profile Photo')
                                ->schema([
                                    FileUpload::make('avatar')
                                        ->label('Profile Photo')
                                        ->disk('public')
                                        ->directory('user_avatar')
                                        ->image()
                                        ->avatar()
                                        ->imageEditor()
                                        ->imageEditorAspectRatios([
                                            '1:1',
                                        ])
                                        ->maxSize(2048)
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),

                            Section::make('Additional Contact')
                                ->schema([
                                    TextInput::make('alternate_no')
                                        ->label('Alternate Number')
                                        ->maxLength(20),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),

                            Section::make('Family Information')
                                ->schema([
                                    TextInput::make('father_name')
                                        ->label("Father's Name"),
                                    TextInput::make('wife_name')
                                        ->label("Wife's Name"),
                                    TextInput::make('husband_name')
                                        ->label("Husband's Name"),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),

                            Section::make('Address')
                                ->schema([
                                    TextInput::make('landmark')
                                        ->label('Landmark'),
                                    TextInput::make('pincode')
                                        ->label('Pincode')
                                        ->maxLength(10),
                                    TextInput::make('area')
                                        ->label('Area'),
                                    TextInput::make('city')
                                        ->label('City'),
                                    TextInput::make('state')
                                        ->label('State'),
                                    TextInput::make('nationality')
                                        ->label('Nationality'),
                                    Textarea::make('address')
                                        ->label('Address')
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}