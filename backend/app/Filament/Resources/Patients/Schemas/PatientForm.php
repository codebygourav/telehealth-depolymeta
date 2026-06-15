<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\BloodGroupOption;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
// Removed Group
use Filament\Forms\Components\Textarea;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registration & Login')
                    ->description('Choose whether this patient should also get a mobile app login account.')
                    ->schema([
                        Select::make('source')
                            ->label('Source')
                            ->options([
                                'app' => 'Mobile App',
                                'website' => 'Website',
                                'internal' => 'Internal',
                            ])
                            ->default('website')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $get, $state) {
                                if ($state === 'app') {
                                    $set('create_user_account', true);

                                    if (blank($get('user_email')) && filled($get('email'))) {
                                        $set('user_email', $get('email'));
                                    }

                                    if (blank($get('user_phone')) && filled($get('mobile_no'))) {
                                        $set('user_phone', $get('mobile_no'));
                                    }
                                } else {
                                    $set('create_user_account', false);
                                }
                            }),
                        Toggle::make('create_user_account')
                            ->label('Create User Account')
                            ->onIcon('heroicon-o-check')
                            ->offIcon('heroicon-o-x-mark')
                            ->offColor('gray')
                            ->onColor('success')
                            ->default(false)
                            ->visible(fn($get) => in_array($get('source'), ['website', 'internal'], true))
                            ->live()
                            ->afterStateUpdated(function ($set, $get, $state) {
                                if (! $state) {
                                    return;
                                }

                                if (blank($get('user_email')) && filled($get('email'))) {
                                    $set('user_email', $get('email'));
                                }

                                if (blank($get('user_phone')) && filled($get('mobile_no'))) {
                                    $set('user_phone', $get('mobile_no'));
                                }
                            })
                            ->dehydrated(true), // Always include in form data
                        TextInput::make('user_email')
                            ->label('Login Email')
                            ->email()
                            ->disabled(fn($record) => filled($record?->user_id))
                            ->helperText(fn($record) => filled($record?->user_id) ? 'Login email cannot be changed here. Update other patient details as needed.' : null)
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->user_id && $record->user) {
                                    $component->state($record->user->email ?? $state);
                                }
                            }),
                        TextInput::make('user_phone')
                            ->label('Login Phone')
                            ->tel()
                            ->maxLength(20)
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->user_id && $record->user) {
                                    $component->state($record->user->phone ?? $state);
                                }
                            }),
                        TextInput::make('user_password')
                            ->label('Login Password')
                            ->password()
                            ->revealable()
                            ->required(function ($get, $livewire) {
                                $record = $livewire->record ?? null;
                                if ($record === null) {
                                    $source = $get('source');
                                    $createUserAccount = $get('create_user_account');

                                    return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                                }

                                return false;
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'app' || (in_array($source, ['website', 'internal'], true) && $createUserAccount === true);
                            })
                            ->helperText(function ($livewire) {
                                $record = $livewire->record ?? null;

                                return $record ? 'Leave blank to keep current password' : 'Enter password for mobile app login';
                            })
                            ->dehydrated(fn($state) => filled($state)),
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
                            ->afterStateUpdated(function ($set, $state) {
                                if (! $state) {
                                    $set('existing_patient_id', null);
                                }
                            }),
                        TextInput::make('existing_patient_id')
                            ->label('Existing Patient ID')
                            ->visible(fn($get) => $get('is_existing_patient') === true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Last Name'),

                        Select::make('gender')
                            ->label('Gender')
                            ->options(GenderOption::labels())
                            ->native(false)
                            ->searchable()
                            ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                            ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $age = now()->diffInYears($state);
                                    $set('age', $age);
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
                            ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                            ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                        Select::make('blood_group')
                            ->label('Blood Group')
                            ->options(BloodGroupOption::labels())
                            ->native(false)
                            ->searchable()
                            ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                            ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),
                        Textarea::make('bio')
                        ->label('Short Bio')
                        ->placeholder('Brief professional introduction')
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
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Contact Details')
                    ->schema([
                        TextInput::make('mobile_no')
                            ->label('Mobile Number')
                            ->maxLength(20)
                            ->live()
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return in_array($source, ['website', 'internal'], true) && $createUserAccount === false;
                            })
                            ->afterStateUpdated(function ($set, $get, $state) {
                                if (($get('source') === 'app' || $get('create_user_account')) && blank($get('user_phone'))) {
                                    $set('user_phone', $state);
                                }
                            }),
                        TextInput::make('alternate_no')
                            ->label('Alternate Number')
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->live()
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return in_array($source, ['website', 'internal'], true) && $createUserAccount === false;
                            })
                            ->afterStateUpdated(function ($set, $get, $state) {
                                if (($get('source') === 'app' || $get('create_user_account')) && blank($get('user_email'))) {
                                    $set('user_email', $state);
                                }
                            }),
                    ])
                    ->columns(3)
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
                            ->label('Address'),


                    ])
                    ->columns(3)
                    ->columnSpanFull(),

            ]);
    }
}
