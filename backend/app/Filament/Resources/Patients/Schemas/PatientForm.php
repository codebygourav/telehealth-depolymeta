<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\BloodGroupOption;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Registration;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PatientForm
{
    protected static function relationTypeOptions(?string $maritalStatus): array
    {
        $base = [
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'guardian_name' => 'Guardian Name',
            'other' => 'Other',
        ];

        if ($maritalStatus === 'married') {
            return [
                'husband_name' => 'Husband Name',
                'wife_name' => 'Wife Name',
                'spouse_name' => 'Spouse Name',
                ...$base,
            ];
        }

        if ($maritalStatus === 'divorced') {
            return [
                'ex_husband_name' => 'Ex-Husband Name',
                'ex_wife_name' => 'Ex-Wife Name',
                ...$base,
            ];
        }

        if ($maritalStatus === 'widowed') {
            return [
                'late_husband_name' => 'Late Husband Name',
                'late_wife_name' => 'Late Wife Name',
                ...$base,
            ];
        }

        return $base;
    }

    protected static function relationValueLabel(?string $type): string
    {
        return match ($type) {
            'husband_name' => 'Husband Name',
            'wife_name' => 'Wife Name',
            'spouse_name' => 'Spouse Name',
            'ex_husband_name' => 'Ex-Husband Name',
            'ex_wife_name' => 'Ex-Wife Name',
            'late_husband_name' => 'Late Husband Name',
            'late_wife_name' => 'Late Wife Name',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'guardian_name' => 'Guardian Name',
            default => 'Relation Name',
        };
    }

    protected static function maritalRelationLabel(?string $gender, ?string $maritalStatus): string
    {
        return match ($maritalStatus) {
            'married' => match ($gender) {
                'male' => 'Wife Name',
                'female' => 'Husband Name',
                default => 'Spouse Name',
            },
            'divorced' => match ($gender) {
                'male' => 'Ex-Wife Name',
                'female' => 'Ex-Husband Name',
                default => 'Ex-Partner Name',
            },
            'widowed' => match ($gender) {
                'male' => 'Late Wife Name',
                'female' => 'Late Husband Name',
                default => 'Late Partner Name',
            },
            default => 'Partner Name',
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('source')
                    ->default('app')
                    ->dehydrated(true),
                Hidden::make('create_user_account')
                    ->default(true)
                    ->dehydrated(true),
                Wizard::make([
                    Step::make('Verify Email')
                        ->description('Send OTP and verify patient email before profile creation.')
                        ->schema([
                            TextInput::make('registration_email')
                                ->label('Patient Email')
                                ->email()
                                ->required()
                                ->live(onBlur: true)
                                ->extraInputAttributes(fn ($get) => ($get('email_verified_flag') === true)
                                    ? []
                                    : ['class' => '!border-red-500 focus:!ring-red-500']
                                )
                                ->rule(function () {
                                    return function (string $attribute, $value, \Closure $fail): void {
                                        if (! is_string($value) || trim($value) === '') {
                                            return;
                                        }
                                        if (User::where('email', $value)->exists()) {
                                            $fail('This email is already registered as a user.');
                                        }
                                    };
                                })
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('email_verified_flag', false);
                                    $set('email_otp', null);
                                    $set('email', $state);
                                    $set('user_email', $state);
                                })
                                ->suffixAction(
                                    Action::make('send_registration_otp')
                                        ->label('Send OTP')
                                        ->icon('heroicon-o-paper-airplane')
                                        ->action(function ($get) {
                                            $email = trim((string) ($get('registration_email') ?? ''));
                                            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                Notification::make()
                                                    ->title('Enter a valid email first')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }
                                            if (User::where('email', $email)->exists()) {
                                                Notification::make()
                                                    ->title('Email already registered')
                                                    ->body('This email is already registered. Please login.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $registration = Registration::where('email', $email)->first();

                                            if ($registration && $registration->status === \App\Enums\AuthStatus::verified->value) {
                                                Notification::make()
                                                    ->title('Email already verified')
                                                    ->body('Email is already verified. Please complete your profile.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            if (! $registration) {
                                                Registration::create([
                                                    'email' => $email,
                                                    'status' => \App\Enums\AuthStatus::new_register->value,
                                                    'email_verified' => false,
                                                ]);
                                            } else {
                                                $registration->update([
                                                    'status' => \App\Enums\AuthStatus::new_register->value,
                                                    'email_verified' => false,
                                                ]);
                                            }

                                            app(OtpService::class)->sendOtp($email, 'registration');
                                            Notification::make()
                                                ->title('OTP sent')
                                                ->body('Verification code sent to patient email.')
                                                ->success()
                                                ->send();
                                        })
                                ),
                            Hidden::make('email_verified_flag')
                                ->default(false)
                                ->dehydrated(false),
                            TextInput::make('email_otp')
                                ->label('OTP')
                                ->maxLength(6)
                                ->dehydrated(true)
                                ->suffixAction(
                                    Action::make('verify_registration_otp')
                                        ->label('Verify')
                                        ->icon('heroicon-o-shield-check')
                                        ->action(function ($get, $set) {
                                            $email = trim((string) ($get('registration_email') ?? ''));
                                            $otp = trim((string) ($get('email_otp') ?? ''));
                                            if ($email === '' || $otp === '') {
                                                Notification::make()
                                                    ->title('Email and OTP are required')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }
                                            $verified = app(OtpService::class)->verifyOtp($email, $otp, 'registration');
                                            if (! $verified) {
                                                Notification::make()
                                                    ->title('Invalid or expired OTP')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            Registration::where('email', $email)->update([
                                                'email_verified' => true,
                                                'status' => \App\Enums\AuthStatus::verified->value,
                                            ]);
                                            app(OtpService::class)->deleteOtp($email, 'registration');
                                            $set('email_verified_flag', true);
                                            Notification::make()
                                                ->title('Email verified successfully')
                                                ->success()
                                                ->send();
                                        })
                                ),
                            TextInput::make('user_password')
                                ->label('Temporary Password')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->required()
                                ->helperText('This password will be shared in confirmation email.'),
                        ])
                        ->columns(3),

                    Step::make('Patient Profile')
                        ->description('Complete patient demographic and medical profile details.')
                        ->schema([
                            FileUpload::make('avatar')
                                ->label('Profile Photo')
                                ->disk('public')
                                ->directory('user_avatar')
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->imageEditorAspectRatios(['1:1'])
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                            TextInput::make('first_name')->required(),
                            TextInput::make('last_name')->required(),
                            Select::make('gender')
                                ->options(GenderOption::labels())
                                ->native(false)
                                ->searchable()
                                ->required(),
                            DatePicker::make('date_of_birth')
                                ->required()
                                ->native(false)
                                ->displayFormat('d-m-Y')
                                ->format('Y-m-d')
                                ->maxDate(now())
                                ->reactive()
                                ,
                            Select::make('blood_group')
                                ->options(BloodGroupOption::labels())
                                ->native(false)
                                ->searchable()
                                ->rule(Rule::in(array_keys(BloodGroupOption::labels()))),
                            Select::make('marital_status')
                                ->options(MaritalStatus::labels())
                                ->native(false)
                                ->searchable()
                                ->live()
                                ->rule(Rule::in(array_keys(MaritalStatus::labels()))),
                            Select::make('partner_relation_type')
                                ->label('Relation Type')
                                ->options(fn ($get) => self::relationTypeOptions($get('marital_status')))
                                ->native(false)
                                ->searchable()
                                ->live()
                                ->visible(fn ($get) => filled($get('marital_status')))
                                ->required(fn ($get) => filled($get('marital_status'))),
                            TextInput::make('spouse_name')
                                ->label(fn ($get) => self::relationValueLabel($get('partner_relation_type')))
                                ->visible(fn ($get) => filled($get('partner_relation_type')))
                                ->required(fn ($get) => filled($get('partner_relation_type'))),
                            TextInput::make('mobile_no')->required()->maxLength(20),
                            TextInput::make('alternate_no')->maxLength(20),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->readOnly(),
                            Textarea::make('address')->columnSpanFull(),
                            TextInput::make('emergency_contact_name'),
                            TextInput::make('emergency_contact_relationship'),
                            TextInput::make('emergency_contact_phone')->maxLength(32),
                            Textarea::make('allergies')->columnSpanFull(),
                            Textarea::make('existing_conditions')->columnSpanFull(),
                            Textarea::make('current_medications')->columnSpanFull(),
                            Textarea::make('past_medical_history')->columnSpanFull(),
                            Toggle::make('treatment_consent_accepted')
                                ->label('Treatment Consent Accepted')
                                ->offColor('gray')
                                ->onColor('primary')
                                ->default(true),
                        ])
                        ->columns(3),

                    Step::make('Appointment & Payment')
                        ->description('Book appointment in the same flow and save payment mode.')
                        ->schema([
                            Toggle::make('book_appointment')
                                ->label('Book Appointment Now')
                                ->offColor('gray')
                                ->onColor('primary')
                                ->default(true)
                                ->live(),
                            Select::make('department_id')
                                ->label('Department')
                                ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->live()
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->afterStateUpdated(fn ($set) => $set('doctor_id', null)),
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->options(function ($get) {
                                    $departmentId = $get('department_id');
                                    $query = Doctor::query()->orderBy('first_name');
                                    if ($departmentId) {
                                        $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
                                    }
                                    return $query->get()->mapWithKeys(function (Doctor $doctor) {
                                        $name = trim('Dr. ' . ($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
                                        return [$doctor->id => $name];
                                    })->toArray();
                                })
                                ->searchable()
                                ->live()
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->afterStateUpdated(fn ($set) => $set('availability_id', null)),
                            Select::make('availability_id')
                                ->label('Availability Slot')
                                ->options(function ($get) {
                                    $doctorId = $get('doctor_id');
                                    if (! $doctorId) {
                                        return [];
                                    }
                                    return DoctorAvailability::query()
                                        ->where('doctor_id', $doctorId)
                                        ->where('is_available', true)
                                        ->orderByDesc('date')
                                        ->get()
                                        ->mapWithKeys(function (DoctorAvailability $availability) {
                                            $dateLabel = $availability->date
                                                ? Carbon::parse($availability->date)->format('d M Y')
                                                : ($availability->day_of_week ?? 'Recurring');
                                            $start = $availability->start_time ? Carbon::parse($availability->start_time)->format('H:i') : '--';
                                            $end = $availability->end_time ? Carbon::parse($availability->end_time)->format('H:i') : '--';
                                            $type = $availability->consultation_type ?? 'n/a';
                                            $fee = $availability->consultation_fee !== null ? ' | Rs ' . $availability->consultation_fee : '';
                                            return [$availability->id => "{$dateLabel} | {$start}-{$end} | {$type}{$fee}"];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->live()
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->afterStateUpdated(function ($state, $set) {
                                    $slot = $state ? DoctorAvailability::find($state) : null;
                                    if (! $slot) {
                                        $set('appointment_date', null);
                                        return;
                                    }

                                    $dateOptions = self::buildAppointmentDateOptions($slot);
                                    $set('appointment_date', array_key_first($dateOptions) ?: null);
                                    $set('appointment_time', $slot->start_time ? Carbon::parse($slot->start_time)->format('H:i') : null);
                                    $set('consultation_type', $slot->consultation_type);
                                    $set('opd_type', $slot->opd_type);
                                }),
                            Select::make('appointment_date')
                                ->label('Appointment Date')
                                ->options(function ($get) {
                                    $availabilityId = $get('availability_id');
                                    $slot = $availabilityId ? DoctorAvailability::find($availabilityId) : null;

                                    return $slot ? self::buildAppointmentDateOptions($slot) : [];
                                })
                                ->searchable()
                                ->native(false)
                                ->placeholder('Select availability slot to load valid dates')
                                ->helperText(function ($get) {
                                    $availabilityId = $get('availability_id');
                                    if (! $availabilityId) {
                                        return 'Select an availability slot first.';
                                    }

                                    $slot = DoctorAvailability::find($availabilityId);
                                    if (! $slot) {
                                        return 'Selected slot could not be loaded.';
                                    }

                                    $options = self::buildAppointmentDateOptions($slot);

                                    if (empty($options)) {
                                        return 'No valid dates are available within the recurring start and end dates.';
                                    }

                                    return 'Only valid dates for the selected slot are shown.';
                                })
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->required(fn ($get) => (bool) $get('book_appointment')),
                            TextInput::make('appointment_time')
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->required(fn ($get) => (bool) $get('book_appointment')),
                            Select::make('consultation_type')
                                ->options([
                                    'in-person' => 'In-person',
                                    'video' => 'Video',
                                ])
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->required(fn ($get) => (bool) $get('book_appointment'))
                                ->live(),
                            Select::make('opd_type')
                                ->options([
                                    'general' => 'General',
                                    'private' => 'Private',
                                ])
                                ->visible(fn ($get) => (bool) $get('book_appointment') && $get('consultation_type') === 'in-person')
                                ->required(fn ($get) => (bool) $get('book_appointment') && $get('consultation_type') === 'in-person'),
                            Textarea::make('visit_reason')
                                ->visible(fn ($get) => (bool) $get('book_appointment'))
                                ->columnSpanFull(),
                            Select::make('payment_mode')
                                ->label('Payment Mode')
                                ->options([
                                    'cash' => 'Cash (confirm now)',
                                    'online' => 'Online (payment link/order)',
                                ])
                                ->default('cash')
                                ->required()
                                ->live()
                                ->suffixAction(
                                    Action::make('pay_now_create')
                                        ->label('Pay Now & Create')
                                        ->icon('heroicon-o-credit-card')
                                        ->color('success')
                                        ->visible(fn ($get) => (bool) $get('book_appointment') && $get('payment_mode') === 'online')
                                        ->action(function (Select $component) {
                                            $livewire = $component->getLivewire();
                                            if (method_exists($livewire, 'create')) {
                                                $livewire->create(false);
                                            }
                                        })
                                )
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state !== 'cash') {
                                        $set('cash_transaction_id', null);
                                        return;
                                    }
                                    if (! filled($get('cash_transaction_id'))) {
                                        $set('cash_transaction_id', 'cash_txn_' . strtoupper(Str::random(10)));
                                    }
                                })
                                ->visible(fn ($get) => (bool) $get('book_appointment')),
                            TextInput::make('cash_transaction_id')
                                ->label('Cash Transaction ID')
                                ->helperText('Auto-generated for cash. Admin can edit before save.')
                                ->visible(fn ($get) => (bool) $get('book_appointment') && $get('payment_mode') === 'cash')
                                ->required(fn ($get) => (bool) $get('book_appointment') && $get('payment_mode') === 'cash')
                                ->afterStateHydrated(function ($component, $state) {
                                    if (! filled($state)) {
                                        $component->state('cash_txn_' . strtoupper(Str::random(10)));
                                    }
                                }),
                        ])
                        ->columns(3),
                ])
                    ->visibleOn('create')
                    ->columnSpanFull(),
                Section::make('Additional Information')
                    ->description('Update complete patient profile details.')
                    ->visibleOn('edit')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Profile Photo')
                            ->disk('public')
                            ->directory('user_avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                        TextInput::make('first_name')->required(),
                        TextInput::make('last_name')->required(),
                        Select::make('gender')
                            ->options(GenderOption::labels())
                            ->native(false)
                            ->searchable()
                            ->live(),
                        DatePicker::make('date_of_birth')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->format('Y-m-d')
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $set('age', now()->diffInYears($state));
                                }
                            }),
                        TextInput::make('age')->numeric()->readOnly(),
                        Select::make('blood_group')
                            ->options(BloodGroupOption::labels())
                            ->native(false)
                            ->searchable(),
                        Select::make('marital_status')
                            ->options(MaritalStatus::labels())
                            ->native(false)
                            ->searchable()
                            ->live(),
                        Select::make('partner_relation_type')
                            ->label('Relation Type')
                            ->options(fn ($get) => self::relationTypeOptions($get('marital_status')))
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->visible(fn ($get) => filled($get('marital_status'))),
                        TextInput::make('spouse_name')
                            ->label(fn ($get) => self::relationValueLabel($get('partner_relation_type')))
                            ->visible(fn ($get) => filled($get('partner_relation_type'))),
                        TextInput::make('mobile_no')->required()->maxLength(20),
                        TextInput::make('alternate_no')->maxLength(20),
                        TextInput::make('email')->email(),
                        TextInput::make('father_name'),
                        TextInput::make('mother_name'),
                        TextInput::make('landmark'),
                        TextInput::make('pincode')->maxLength(10),
                        TextInput::make('area'),
                        TextInput::make('city'),
                        TextInput::make('state'),
                        TextInput::make('nationality'),
                        Textarea::make('address')->columnSpanFull(),
                        Textarea::make('allergies')->columnSpanFull(),
                        Textarea::make('existing_conditions')->columnSpanFull(),
                        Textarea::make('current_medications')->columnSpanFull(),
                        Textarea::make('past_medical_history')->columnSpanFull(),
                        TextInput::make('emergency_contact_name'),
                        TextInput::make('emergency_contact_relationship'),
                        TextInput::make('emergency_contact_phone')->maxLength(32),
                        TextInput::make('insurance_provider'),
                        TextInput::make('insurance_policy_number'),
                        DatePicker::make('insurance_policy_expiry')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->format('Y-m-d'),
                        TextInput::make('insurance_tpa_details'),
                        Toggle::make('is_existing_patient')
                            ->label('Is Existing Patient')
                            ->offColor('gray')
                            ->onColor('primary')
                            ->reactive()
                            ->afterStateUpdated(fn ($set, $state) => ! $state ? $set('existing_patient_id', null) : null),
                        TextInput::make('existing_patient_id')
                            ->visible(fn ($get) => (bool) $get('is_existing_patient')),
                        Toggle::make('treatment_consent_accepted')
                            ->offColor('gray')
                            ->onColor('primary')
                            ->label('Treatment Consent Accepted'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    protected static function buildAppointmentDateOptions(DoctorAvailability $availability): array
    {
        if (! $availability->is_recurring && $availability->date) {
            $slotDate = Carbon::parse($availability->date)->startOfDay();

            if ($slotDate->lt(today()->startOfDay())) {
                return [];
            }

            return [
                $slotDate->format('Y-m-d') => $slotDate->format('D, d M Y'),
            ];
        }

        if (! $availability->is_recurring) {
            return [];
        }

        $startDate = $availability->recurring_start_date
            ? Carbon::parse($availability->recurring_start_date)->startOfDay()
            : today()->startOfDay();

        $endDate = $availability->recurring_end_date
            ? Carbon::parse($availability->recurring_end_date)->endOfDay()
            : $startDate->copy()->addMonths((int) ($availability->recurring_months ?: 3))->endOfDay();

        if ($endDate->lt(today()->startOfDay())) {
            return [];
        }

        $cursor = $startDate->greaterThan(today()->startOfDay())
            ? $startDate->copy()
            : today()->startOfDay();

        $targetDay = strtolower((string) $availability->day_of_week);
        if ($targetDay === '') {
            $targetDay = strtolower($startDate->format('l'));
        }

        while (strtolower($cursor->format('l')) !== $targetDay && $cursor->lte($endDate)) {
            $cursor->addDay();
        }

        $options = [];

        while ($cursor->lte($endDate)) {
            $options[$cursor->format('Y-m-d')] = $cursor->format('D, d M Y');
            $cursor->addWeek();
        }

        return $options;
    }
}
