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
                Section::make('Professional Information')
                    ->description('Professional designation and core credentials.')
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
                            ->maxSize(2048) // 2MB max file size
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            // Note: optimize() and imageResize methods don't exist in Filament v4
                            // Image editing is handled via imageEditor() above
                            ->columnSpanFull(),

                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Registration Settings')
                    ->description('Configure patient source and registration options.')
                    ->schema([
                        Select::make('source')
                            ->label('Source')
                            ->options([
                                'app' => 'Mobile App',
                                'website' => 'Website',
                            ])
                            ->default('website')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                // For app source, automatically enable user account creation
                                if ($state === 'app') {
                                    $set('create_user_account', true);
                                } else {
                                    // For website, reset to false
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
                            ->visible(fn($get) => $get('source') === 'website')
                            ->live()
                            ->dehydrated(true), // Always include in form data
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
                        Textarea::make('bio')
                        ->label('Short Bio')
                        ->placeholder('Brief professional introduction')
                        ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Contact Details')
                    ->schema([
                        // Patient fields - shown when create_user_account is false
                        TextInput::make('mobile_no')
                            ->label('Mobile Number')
                            ->maxLength(20)
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'website' && $createUserAccount === false;
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                // Show only when source is website and create_user_account is false
                                return $source === 'website' && $createUserAccount === false;
                            }),
                        TextInput::make('alternate_no')
                            ->label('Alternate Number')
                            ->maxLength(20)
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                // Show only when source is website and create_user_account is false
                                return $source === 'website' && $createUserAccount === false;
                            }),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                return $source === 'website' && $createUserAccount === false;
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                // Show only when source is website and create_user_account is false
                                return $source === 'website' && $createUserAccount === false;
                            }),
                        // User registration fields - shown when source is app OR (website and create_user_account is true)
                        TextInput::make('user_email')
                            ->label('Email (for Registration)')
                            ->email()
                            ->unique('users', 'email', ignorable: fn($record) => $record?->user)
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                // Required for app source always, or website when create_user_account is true
                                return $source === 'app' || ($source === 'website' && $createUserAccount === true);
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');
                                // Show for app source always
                                if ($source === 'app') {
                                    return true;
                                }

                                // Show for website when create_user_account is true
                                return $source === 'website' && $createUserAccount === true;
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // If editing and user exists, populate from user table
                                if ($record && $record->user_id && $record->user) {
                                    $component->state($record->user->email ?? $state);
                                }
                            }),
                        TextInput::make('user_phone')
                            ->label('Phone (for Registration)')
                            ->tel()
                            ->maxLength(20)
                            ->unique('users', 'phone', ignorable: fn($record) => $record?->user)
                            ->required(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');

                                // Required for app source always, or website when create_user_account is true
                                return $source === 'app' || ($source === 'website' && $createUserAccount === true);
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');
                                // Show for app source always
                                if ($source === 'app') {
                                    return true;
                                }

                                // Show for website when create_user_account is true
                                return $source === 'website' && $createUserAccount === true;
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // If editing and user exists, populate from user table
                                if ($record && $record->user_id && $record->user) {
                                    $component->state($record->user->phone ?? $state);
                                }
                            }),
                        TextInput::make('user_password')
                            ->label('Password (for Registration)')
                            ->password()
                            ->revealable()
                            ->required(function ($get, $livewire) {
                                // Required when creating new record for app source or website with create_user_account
                                $record = $livewire->record ?? null;
                                if ($record === null) {
                                    $source = $get('source');
                                    $createUserAccount = $get('create_user_account');

                                    return $source === 'app' || ($source === 'website' && $createUserAccount === true);
                                }

                                return false; // Not required when editing
                            })
                            ->visible(function ($get) {
                                $source = $get('source');
                                $createUserAccount = $get('create_user_account');
                                // Show for app source always
                                if ($source === 'app') {
                                    return true;
                                }

                                // Show for website when create_user_account is true
                                return $source === 'website' && $createUserAccount === true;
                            })
                            ->helperText(function ($livewire) {
                                $record = $livewire->record ?? null;

                                return $record ? 'Leave blank to keep current password' : 'Enter password for user registration';
                            })
                            ->dehydrated(fn($state) => filled($state)), // Only save if password is provided
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Family Information')
                    ->schema([
                        TextInput::make('father_name')
                            ->label("Father's Name"),
                        TextInput::make('mother_name')
                            ->label("Mother's Name"),
                    ])
                    ->columns(2)
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
                                // If is_existing_patient is true, clear existing_patient_id if needed
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
            ]);
    }
}
