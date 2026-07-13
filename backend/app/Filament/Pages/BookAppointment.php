<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Enums\DoctorStatus;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Services\DoctorAvailabilityService;
use App\Services\PatientAuthAccountService;
use App\Services\SettingService;
use App\Services\SlotCapacityService;
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookAppointment extends Page implements HasForms
{
    use InteractsWithForms;
    use HasCustomSidebar;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?string $title = 'Book Appointment';
    protected static ?string $navigationLabel = 'Book Appointment';
    protected static ?string $slug = 'book-appointment';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.pages.book-appointment';

    public ?array $data = [];
    public ?array $result = null;
    public bool $showResult = false;
    public bool $showResultModal = false;
    public ?array $availabilityDetails = null;
    public array $appointmentDateOptions = [];
    public string $paymentMode = 'Live';
    public ?string $currentAppointmentId = null;
    public ?array $autoCheckout = null;
    public bool $showBookingConfirmation = false;

    public static function canAccess(): bool
    {
        $module = static::$slug ?? strtolower(class_basename(static::class));
        return check_permission(["{$module}.view", "{$module}.view_any", "{$module}.manage_own"]);
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Book Appointment',
            'icon'  => 'heroicon-o-calendar-days',
            'sort'  => 41,
            'group' => 'Appointments & Finance',
        ];
    }

    public function mount(): void
    {
        $this->paymentMode = SettingService::isAppointmentMockPaymentEnabled() ? 'Mock' : 'Live';
        $patientId = request()->query('patient_id');
        $payload = [
            'patient_mode' => 'existing',
            'booking_mode' => 'new',
            'consultation_type' => 'in-person',
            'collect_payment' => true,
        ];

        if (is_string($patientId) && $patientId !== '' && Patient::query()->whereKey($patientId)->exists()) {
            $payload['patient_id'] = $patientId;
        }

        $this->form->fill($payload);

        $openCheckout = (string) request()->query('open_checkout', '0') === '1';
        $orderId = request()->query('order_id');
        $appointmentId = request()->query('appointment_id');
        $amountPaise = (int) request()->query('amount_paise', 0);
        $keyId = request()->query('key_id');

        if ($openCheckout && is_string($orderId) && is_string($appointmentId) && $amountPaise > 0 && is_string($keyId)) {
            $this->autoCheckout = [
                'appointment_id' => $appointmentId,
                'payment' => [
                    'order_id' => $orderId,
                    'amount_paise' => $amountPaise,
                    'razorpay_key_id' => $keyId,
                ],
            ];
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Admin Payment')
                ->description('Choose whether this admin-created appointment should collect payment.')
                ->schema([
                    Toggle::make('collect_payment')
                        ->label('Collect payment for this booking')
                        ->helperText('Turn this off to confirm the appointment without creating a payment order.')
                        ->default(true)
                        ->inline(false)
                        ->onColor('success')
                        ->offColor('gray'),
                ])
                ->columns(1),
                Section::make('Patient Details')
                    ->description('Select an existing patient or enter the walk-in patient details.')
                    ->schema([
                        Radio::make('patient_mode')
                            ->label('Patient Type')
                            ->options([
                                'existing' => 'Existing Patient',
                                'new' => 'New Patient',
                            ])
                            ->default('existing')
                            ->inline()
                            ->inlineLabel(false)
                            ->columns(2)
                            ->extraAttributes(['class' => 'patient-type-radio'])
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('patient_id', null);
                                $set('booking_mode', 'new');
                                $set('existing_appointment_id', null);
                            })
                            ->columnSpanFull(),

                        Select::make('patient_id')
                            ->label('Find Patient')
                            ->options(fn() => $this->patientOptions())
                            ->searchable()
                            ->required(fn(callable $get) => $get('patient_mode') === 'existing')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'existing')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $patient = Patient::query()->find($state);
                                $set('existing_patient_id', $patient?->existing_patient_id);
                                $set('booking_mode', 'new');
                                $set('existing_appointment_id', null);
                                $set('doctor_id', null);
                                $set('appointment_date_choice', null);
                                $set('time_slot_key', null);
                                $set('availability_id', null);
                                $set('appointment_date', null);
                                $set('appointment_time', null);
                                $set('consultation_type', null);
                                $set('opd_type', null);
                                $this->availabilityDetails = null;
                            })
                            ->columnSpan(2),

                        Radio::make('booking_mode')
                            ->label('Booking Type')
                            ->options([
                                'new' => 'Book new appointment',
                                'reschedule' => 'Reschedule existing appointment',
                            ])
                            ->default('new')
                            ->inline()
                            ->inlineLabel(false)
                            ->columns(2)
                            ->live()
                            ->visible(fn(callable $get): bool => $get('patient_mode') === 'existing' && $this->hasReschedulableAppointments($get('patient_id')))
                            ->helperText('This patient already has booked appointments. Choose whether to create a new appointment or reschedule an existing one.')
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $set('existing_appointment_id', null);
                                $set('doctor_id', null);
                                $set('appointment_date_choice', null);
                                $set('time_slot_key', null);
                                $set('availability_id', null);
                                $set('appointment_date', null);
                                $set('appointment_time', null);
                                $set('consultation_type', null);
                                $set('opd_type', null);
                                $this->availabilityDetails = null;
                            })
                            ->columnSpanFull(),

                        Select::make('existing_appointment_id')
                            ->label('Appointment to Reschedule')
                            ->options(fn(callable $get): array => $this->reschedulableAppointmentOptions($get('patient_id')))
                            ->searchable()
                            ->required(fn(callable $get): bool => $get('patient_mode') === 'existing' && $get('booking_mode') === 'reschedule')
                            ->visible(fn(callable $get): bool => $get('patient_mode') === 'existing' && $get('booking_mode') === 'reschedule')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $appointment = Appointment::query()->find($state);
                                $set('doctor_id', $appointment?->doctor_id);
                                $set('appointment_date_choice', null);
                                $set('time_slot_key', null);
                                $set('availability_id', null);
                                $set('appointment_date', null);
                                $set('appointment_time', null);
                                $set('consultation_type', null);
                                $set('opd_type', null);
                                $this->availabilityDetails = null;
                            })
                            ->helperText('The selected appointment will be moved to the new slot. The doctor stays the same.')
                            ->columnSpan(2),

                        TextInput::make('new_patient_first_name')
                            ->label('First Name')
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('new_patient_last_name')
                            ->label('Last Name')
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('existing_patient_id')
                            ->label('Patient Unit ID')
                            ->placeholder('Optional hospital/unit ID')
                            ->maxLength(255)
                            ->helperText(fn(callable $get): string => $get('patient_mode') === 'existing'
                                ? 'Saved on the selected patient record if entered.'
                                : 'Saved on the new patient record if entered.')
                            ->columnSpan(fn(callable $get): int => $get('patient_mode') === 'existing' ? 2 : 1),

                        TextInput::make('new_patient_age')
                            ->label('Age')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->placeholder('e.g. 35')
                            ->helperText('Age 1-120')
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->columnSpan(1),

                        Select::make('new_patient_gender')
                            ->label('Gender')
                            ->options(GenderOption::labels())
                            ->default(GenderOption::MALE->value)
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->native(false)
                            ->live()
                            ->columnSpan(1),

                        Select::make('new_patient_marital_status')
                            ->label('Marital Status')
                            ->options($this->maritalStatusOptions())
                            ->default(MaritalStatus::SINGLE->value)
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->native(false)
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('new_patient_father_name')
                            ->label("Father's Name")
                            ->required(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'father_name')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'father_name')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('new_patient_wife_name')
                            ->label("Wife Name")
                            ->required(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'wife_name')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'wife_name')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('new_patient_husband_name')
                            ->label("Husband Name")
                            ->required(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'husband_name')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new' && $this->relationshipField($get) === 'husband_name')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('new_patient_mobile_no')
                            ->label('Mobile Number')
                            ->tel()
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->maxLength(20)
                            ->columnSpan(1),

                        TextInput::make('new_patient_email')
                            ->label('Email')
                            ->email()
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('new_patient_password')
                            ->label('Login Password')
                            ->password()
                            ->revealable()
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->helperText('Required for mobile app login. Leave blank to use the default password `Patient@123`.')
                            ->columnSpan(1),

                        Textarea::make('new_patient_address')
                            ->label('Address')
                            ->rows(4)
                            ->required(fn(callable $get) => $get('patient_mode') === 'new')
                            ->visible(fn(callable $get) => $get('patient_mode') === 'new')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Appointment Details')
                    ->description('Select the doctor, then choose an available date and time.')
                    ->schema([
                        Select::make('doctor_id')
                            ->label('Doctor')
                            ->options(fn(callable $get): array => $this->doctorOptions($get('doctor_id')))
                            ->searchable()
                            ->required()
                            ->live()
                            ->disabled(fn(callable $get): bool => $get('booking_mode') === 'reschedule' && filled($get('existing_appointment_id')))
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('appointment_date_choice', null);
                                $set('time_slot_key', null);
                                $set('availability_id', null);
                                $set('appointment_date', null);
                                $set('appointment_time', null);
                                $set('consultation_type', null);
                                $set('opd_type', null);
                                $this->availabilityDetails = null;
                            })
                            ->columnSpan(1),

                        Select::make('appointment_date_choice')
                            ->label('Appointment Date')
                            ->options(fn(callable $get) => $this->dateOptionsForDoctor($get('doctor_id')))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('time_slot_key', null);
                                $set('availability_id', null);
                                $set('appointment_date', null);
                                $set('appointment_time', null);
                                $set('consultation_type', null);
                                $set('opd_type', null);
                                $this->availabilityDetails = null;
                            })
                            ->disabled(fn(callable $get) => !$get('doctor_id'))
                            ->placeholder('Select doctor first')
                            ->columnSpan(1),

                        Select::make('time_slot_key')
                            ->label('Time Slot')
                            ->options(fn(callable $get) => $this->timeOptionsForDate($get('doctor_id'), $get('appointment_date_choice')))
                            ->disableOptionWhen(function ($value) {
                                [$availabilityId, $date] = $this->parseSlotKey($value);
                                if (! $availabilityId || ! $date) {
                                    return false;
                                }
                                $availability = DoctorAvailability::find($availabilityId);
                                if (! $availability) {
                                    return false;
                                }
                                $effectiveSlot = $this->effectiveSlotForDate($availability, $date);
                                if (! $effectiveSlot) {
                                    return false;
                                }
                                $bookedCount = app(SlotCapacityService::class)->bookedCount(
                                    doctorId: $availability->doctor_id,
                                    date: $date,
                                    startTime: $effectiveSlot->start_time,
                                    availabilityId: $availability->id,
                                    consultationType: $effectiveSlot->consultation_type,
                                );
                                return $bookedCount >= (int) ($effectiveSlot->capacity ?? 1);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, callable $set) => $this->syncSelectedTimeSlot($state, $set))
                            ->disabled(fn(callable $get) => ! $get('doctor_id') || ! $get('appointment_date_choice'))
                            ->placeholder('Select date first')
                            ->columnSpan(1),

                        Hidden::make('availability_id'),

                        Hidden::make('appointment_date'),

                        Hidden::make('appointment_time'),

                        Select::make('consultation_type')
                            ->label('Consultation Type')
                            ->options([
                                'in-person' => 'In-Person',
                                'video' => 'Video',
                            ])
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Locked from selected time slot')
                            ->columnSpan(1),

                        Select::make('opd_type')
                            ->label('OPD Type')
                            ->options([
                                'general' => 'General',
                                'private' => 'Private',
                            ])
                            ->required(fn(callable $get) => $get('consultation_type') === 'in-person')
                            ->visible(fn(callable $get) => $get('consultation_type') === 'in-person')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Locked from selected time slot')
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Notes (Optional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

               
            ])
            ->statePath('data');
    }



    public function confirmBooking(): void
    {
        $this->showBookingConfirmation = true;
    }

    public function cancelBookingConfirmation(): void
    {
        $this->showBookingConfirmation = false;
    }

    public function submitConfirmedBooking(): void
    {
        $this->showBookingConfirmation = false;
        $this->bookAppointment();
    }

    public function bookAppointment(): void
    {
        $this->paymentMode = SettingService::isAppointmentMockPaymentEnabled() ? 'Mock' : 'Live';
        $state = $this->form->getState();
        $transactionStarted = false;

        if (($state['booking_mode'] ?? 'new') === 'reschedule') {
            try {
                $this->rescheduleAppointment($state);
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->result = [
                    'status' => 'error',
                    'status_code' => 500,
                    'response' => [
                        'message' => $this->canShowDebugResponse()
                            ? $e->getMessage()
                            : 'Unable to reschedule this appointment. Please check the selected slot and try again.',
                    ],
                    'message' => $this->canShowDebugResponse()
                        ? $e->getMessage()
                        : 'Unable to reschedule this appointment. Please check the selected slot and try again.',
                    'raw_response' => $e->getMessage(),
                ];

                $this->showResult = true;
                $this->showResultModal = false;

                Notification::make()
                    ->title('Appointment could not be rescheduled')
                    ->body($this->result['message'])
                    ->danger()
                    ->send();
            }

            return;
        }

        try {
            DB::beginTransaction();
            $transactionStarted = true;

            $patient = $this->resolvePatientForBooking($state);

            $payload = Arr::only($state, [
                'doctor_id',
                'availability_id',
                'appointment_date',
                'appointment_time',
                'consultation_type',
                'opd_type',
                'notes',
            ]);
            $payload['patient_id'] = $patient->id;
            $payload['admin_skip_payment'] = ! (bool) ($state['collect_payment'] ?? true);

            $request = Request::create(
                '/api/v2/test-book-appointment',
                'POST',
                $payload
            );

            $request->headers->set('Accept', 'application/json');
            $request->setUserResolver(fn() => auth()->user());

            app()->instance('request', $request);

            $controller = app(BookAppointmentController::class);
            $response = $controller->book($request);

            if (!$response instanceof JsonResponse) {
                throw new \RuntimeException('Controller did not return JSON response');
            }

            $data = $response->getData(true);
            $isSuccess = $response->isSuccessful();

            if ($isSuccess) {
                DB::commit();
                $transactionStarted = false;
            } else {
                DB::rollBack();
                $transactionStarted = false;
            }

            $this->result = [
                'status' => $isSuccess ? 'success' : 'error',
                'status_code' => $response->status(),
                'response' => $data,
                'message' => $this->responseMessage($data, $isSuccess),
                'raw_response' => json_encode($data),
            ];

            $this->showResult = true;
            $this->showResultModal = false;

            Notification::make()
                ->title($isSuccess ? 'Appointment created' : 'Appointment could not be booked')
                ->body($this->result['message'])
                ->{$isSuccess ? 'success' : 'danger'}()
                ->send();
        } catch (ValidationException $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }

            throw $e;
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }

            $this->result = [
                'status' => 'error',
                'status_code' => 500,
                'response' => [
                    'message' => $this->canShowDebugResponse()
                        ? $e->getMessage()
                        : 'Unable to complete this booking. Please check the selected slot and try again.',
                ],
                'message' => $this->canShowDebugResponse()
                    ? $e->getMessage()
                    : 'Unable to complete this booking. Please check the selected slot and try again.',
                'raw_response' => $e->getMessage(),
            ];

            $this->showResult = true;
            $this->showResultModal = false;

            Notification::make()
                ->title('Appointment could not be booked')
                ->body($this->result['message'])
                ->danger()
                ->send();
        }
    }

    private function rescheduleAppointment(array $state): void
    {
        $appointment = Appointment::query()
            ->where('patient_id', $state['patient_id'] ?? null)
            ->whereKey($state['existing_appointment_id'] ?? null)
            ->first();

        if (! $appointment) {
            throw ValidationException::withMessages([
                'data.existing_appointment_id' => 'Please select a valid appointment to reschedule.',
            ]);
        }

        if ($appointment->doctor_id !== ($state['doctor_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.doctor_id' => 'The selected doctor must match the appointment being rescheduled.',
            ]);
        }

        $payload = Arr::only($state, [
            'availability_id',
            'appointment_date',
            'appointment_time',
        ]);
        $payload['appointment_id'] = $appointment->id;

        $request = Request::create(
            '/api/v2/reschedule',
            'POST',
            $payload
        );

        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn() => auth()->user());

        app()->instance('request', $request);

        $controller = app(BookAppointmentController::class);
        $response = $controller->reschedule($request, app(\App\Services\WherebyService::class));

        if (!$response instanceof JsonResponse) {
            throw new \RuntimeException('Controller did not return JSON response');
        }

        $data = $response->getData(true);
        $isSuccess = $response->isSuccessful();

        $this->result = [
            'status' => $isSuccess ? 'success' : 'error',
            'status_code' => $response->status(),
            'response' => $data,
            'message' => $this->responseMessage($data, $isSuccess),
            'raw_response' => json_encode($data),
        ];

        $this->showResult = true;
        $this->showResultModal = false;

        Notification::make()
            ->title($isSuccess ? 'Appointment rescheduled' : 'Appointment could not be rescheduled')
            ->body($this->result['message'])
            ->{$isSuccess ? 'success' : 'danger'}()
            ->send();
    }

    public function clearResult(): void
    {
        $this->result = null;
        $this->showResult = false;
        $this->showResultModal = false;
    }

    public function handlePaymentSuccess(array $json): void
    {
        $this->result = [
            'status' => 'success',
            'status_code' => 200,
            'response' => $json,
            'message' => 'Payment completed and verified successfully.',
            'raw_response' => json_encode($json),
        ];
        $this->showResult = true;
        $this->showResultModal = false;
    }

    public function handlePaymentFailure(array $json): void
    {
        $this->result = [
            'status' => 'error',
            'status_code' => 400,
            'response' => $json,
            'message' => 'Payment verification failed. Please check the payment status.',
            'raw_response' => json_encode($json),
        ];
        $this->showResult = true;
        $this->showResultModal = false;
    }

    public function closeResultModal(): void
    {
        $this->showResultModal = false;
        if ($this->result && $this->result['status'] === 'success') {
            $this->redirect(static::getUrl());
        }
    }

    public function canShowDebugResponse(): bool
    {
        return app()->isLocal() || (bool) config('app.debug');
    }

    public function isRescheduleMode(): bool
    {
        return ($this->data['booking_mode'] ?? 'new') === 'reschedule';
    }

    private function patientOptions(): array
    {
        return Patient::with('user')
            ->latest()
            ->limit(300)
            ->get()
            ->mapWithKeys(function (Patient $patient) {
                $name = trim($patient->first_name . ' ' . ($patient->last_name ?? ''));
                $meta = array_filter([
                    $patient->mobile_no,
                    $patient->email,
                    $patient->existing_patient_id ? 'Unit ID: ' . $patient->existing_patient_id : null,
                ]);

                return [$patient->id => $name . (empty($meta) ? '' : ' (' . implode(' | ', $meta) . ')')];
            })
            ->all();
    }

    private function doctorOptions(?string $selectedDoctorId = null): array
    {
        return Doctor::with('user')
            ->where(function ($query) use ($selectedDoctorId): void {
                $query->where(function ($query): void {
                    $query->where('status', DoctorStatus::ACTIVE)
                        ->whereHas('availabilities', function ($query): void {
                            $query->where('is_available', true);
                        });
                });

                if ($selectedDoctorId) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $selectedDoctorId);
                }
            })
            ->get()
            ->mapWithKeys(function (Doctor $doctor) {
                $name = $doctor->first_name . ' ' . ($doctor->last_name ?? '');
                if ($doctor->user) {
                    $name .= ' (' . $doctor->user->email . ')';
                }
                return [$doctor->id => $name];
            })
            ->all();
    }

    private function hasReschedulableAppointments(?string $patientId): bool
    {
        if (! $patientId) {
            return false;
        }

        return $this->reschedulableAppointmentsQuery($patientId)->exists();
    }

    private function reschedulableAppointmentOptions(?string $patientId): array
    {
        if (! $patientId) {
            return [];
        }

        return $this->reschedulableAppointmentsQuery($patientId)
            ->with(['doctor.user'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->mapWithKeys(function (Appointment $appointment): array {
                $doctorName = $appointment->doctor?->user?->name
                    ?: trim(($appointment->doctor?->first_name ?? '') . ' ' . ($appointment->doctor?->last_name ?? ''))
                    ?: 'Doctor';
                $date = $appointment->appointment_date ? Carbon::parse($appointment->appointment_date)->format('d M Y') : 'No date';
                $time = $appointment->appointment_time ? Carbon::parse($appointment->appointment_time)->format('h:i A') : 'No time';
                $status = $appointment->status instanceof AppointmentStatus ? $appointment->status->label() : ucfirst((string) $appointment->status);

                return [
                    $appointment->id => "{$date} {$time} | {$doctorName} | {$status}",
                ];
            })
            ->all();
    }

    private function reschedulableAppointmentsQuery(string $patientId)
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::RESCHEDULED->value,
            ])
            ->where(function ($query): void {
                $query->whereHas('payment', fn($paymentQuery) => $paymentQuery->where('status', PaymentStatus::PAID->value))
                    ->orWhere(function ($adminQuery): void {
                        $adminQuery
                            ->where('booking_source', 'admin')
                            ->where('admin_payment_type', 'without_payment');
                    });
            })
            ->where(function ($query): void {
                $query->whereDate('appointment_date', '>', now()->toDateString())
                    ->orWhere(function ($todayQuery): void {
                        $todayQuery
                            ->whereDate('appointment_date', now()->toDateString())
                            ->whereTime('appointment_time', '>', now()->copy()->addHour()->format('H:i:s'));
                    });
            });
    }

    private function maritalStatusOptions(): array
    {
        return [
            MaritalStatus::SINGLE->value => 'Single',
            MaritalStatus::MARRIED->value => 'Married',
        ];
    }

    private function dateOptionsForDoctor(?string $doctorId): array
    {
        if (! $doctorId) {
            return [];
        }

        return $this->expandedSlotsForDoctor($doctorId)
            ->groupBy(fn(DoctorAvailability $slot) => Carbon::parse($slot->date)->toDateString())
            ->mapWithKeys(function ($slots, string $date) {
                return [
                    $date => Carbon::parse($date)->format('D, d M Y'),
                ];
            })
            ->all();
    }

    private function timeOptionsForDate(?string $doctorId, ?string $date): array
    {
        if (! $doctorId || ! $date) {
            return [];
        }

        $date = Carbon::parse($date)->toDateString();

        return $this->expandedSlotsForDoctor($doctorId)
            ->filter(fn(DoctorAvailability $slot) => Carbon::parse($slot->date)->toDateString() === $date)
            ->filter(fn(DoctorAvailability $slot) => $this->slotEndsAfterNow($slot))
            ->mapWithKeys(function (DoctorAvailability $slot) use ($date) {
                $key = $slot->id . '|' . $date;
                $fee = (float) ($slot->consultation_fee ?? 0);
                $type = $slot->consultation_type === 'video' ? 'Video' : 'In-Person';
                $source = ($slot->source ?? null) === 'override' ? ' | Updated slot' : '';

                $bookedCount = app(SlotCapacityService::class)->bookedCount(
                    doctorId: $slot->doctor_id,
                    date: $date,
                    startTime: $slot->start_time,
                    availabilityId: $slot->id,
                    consultationType: $slot->consultation_type,
                );
                $capacity = (int) ($slot->capacity ?? 1);
                $isFull = $bookedCount >= $capacity;
                $fullBadge = $isFull ? ' | (Fully Booked)' : '';

                return [
                    $key => Carbon::parse($slot->start_time)->format('g:i A')
                        . ' - '
                        . Carbon::parse($slot->end_time)->format('g:i A')
                        . ' | '
                        . $type
                        . ' | '
                        . ($fee > 0 ? '₹' . number_format($fee, 2) : 'Free')
                        . $source
                        . $fullBadge,
                ];
            })
            ->all();
    }

    private function expandedSlotsForDoctor(string $doctorId): \Illuminate\Support\Collection
    {
        return DoctorAvailability::query()
            ->where('doctor_id', $doctorId)
            ->where('is_available', true)
            ->with(['doctor', 'overrides'])
            ->get()
            ->flatMap(fn(DoctorAvailability $availability) => $this->expandedSlotsForAvailability($availability))
            ->filter(fn(DoctorAvailability $slot) => (bool) $slot->is_available)
            ->filter(fn(DoctorAvailability $slot) => $this->slotEndsAfterNow($slot))
            ->sortBy([
                ['date', 'asc'],
                ['start_time', 'asc'],
            ])
            ->values();
    }

    private function syncSelectedTimeSlot(?string $slotKey, callable $set): void
    {
        $set('availability_id', null);
        $set('appointment_date', null);
        $set('appointment_time', null);
        $set('consultation_type', null);
        $set('opd_type', null);
        $this->availabilityDetails = null;

        [$availabilityId, $date] = $this->parseSlotKey($slotKey);
        if (! $availabilityId || ! $date) {
            return;
        }

        $availability = DoctorAvailability::with(['doctor', 'overrides'])->find($availabilityId);
        if (! $availability) {
            return;
        }

        $effectiveSlot = $this->effectiveSlotForDate($availability, $date);
        if (! $effectiveSlot) {
            return;
        }

        $set('availability_id', $availability->id);
        $set('appointment_date', Carbon::parse($effectiveSlot->date)->toDateString());
        $set('appointment_time', Carbon::parse($effectiveSlot->start_time)->format('H:i'));
        $set('consultation_type', $effectiveSlot->consultation_type);
        $set('opd_type', $effectiveSlot->opd_type);

        $this->setAvailabilityDetails($availability, $effectiveSlot);
    }

    private function parseSlotKey(?string $slotKey): array
    {
        if (! $slotKey || ! str_contains($slotKey, '|')) {
            return [null, null];
        }

        [$availabilityId, $date] = explode('|', $slotKey, 2);

        return [$availabilityId ?: null, $date ?: null];
    }

    private function resolvePatientForBooking(array $state): Patient
    {
        if (($state['patient_mode'] ?? 'existing') === 'existing') {
            $patient = Patient::query()->find($state['patient_id'] ?? null);

            if (! $patient) {
                throw ValidationException::withMessages([
                    'data.patient_id' => 'Please select an existing patient.',
                ]);
            }

            $unitId = trim((string) ($state['existing_patient_id'] ?? ''));
            if ($unitId !== '' && $unitId !== (string) $patient->existing_patient_id) {
                $patient->update([
                    'existing_patient_id' => $unitId,
                    'is_existing_patient' => true,
                ]);
            }

            return $patient;
        }

        $existingPatient = $this->existingPatientForNewPatientEmail($state);
        if ($existingPatient) {
            $this->form->fill(array_merge($state, [
                'patient_mode' => 'existing',
                'patient_id' => $existingPatient->id,
                'existing_patient_id' => $existingPatient->existing_patient_id,
            ]));

            $existingPatientLabel = trim($existingPatient->first_name . ' ' . ($existingPatient->last_name ?? ''));
            $existingPatientMeta = array_filter([
                $existingPatient->mobile_no,
                $existingPatient->existing_patient_id ? 'Unit ID: ' . $existingPatient->existing_patient_id : null,
            ]);

            Notification::make()
                ->title('Existing patient found')
                ->body(trim($existingPatientLabel . (empty($existingPatientMeta) ? '' : ' (' . implode(' | ', $existingPatientMeta) . ')')))
                ->warning()
                ->send();

            throw ValidationException::withMessages([
                'data.new_patient_email' => 'This email already belongs to an existing patient. The existing patient record has been selected.',
            ]);
        }

        $relationshipField = $this->relationshipFieldFromValues(
            $state['new_patient_gender'] ?? null,
            $state['new_patient_marital_status'] ?? null,
        );

        $patientData = [
            'first_name' => $state['new_patient_first_name'] ?? null,
            'last_name' => $state['new_patient_last_name'] ?? null,
            'gender' => $state['new_patient_gender'] ?? null,
            'age' => $state['new_patient_age'] ?? null,
            'marital_status' => $state['new_patient_marital_status'] ?? null,
            'mobile_no' => $state['new_patient_mobile_no'] ?? null,
            'email' => $state['new_patient_email'] ?? null,
            'address' => $state['new_patient_address'] ?? null,
            'existing_patient_id' => filled($state['existing_patient_id'] ?? null)
                ? trim((string) $state['existing_patient_id'])
                : null,
            'source' => 'internal',
            'is_existing_patient' => filled($state['existing_patient_id'] ?? null),
            'create_user_account' => true,
            'user_email' => $state['new_patient_email'] ?? null,
            'user_phone' => $state['new_patient_mobile_no'] ?? null,
        ];

        if ($relationshipField) {
            $patientData[$relationshipField] = $state['new_patient_' . $relationshipField] ?? null;
        }

        return app(PatientAuthAccountService::class)->provision(
            patientData: $patientData,
            plainPassword: $state['new_patient_password'] ?? null,
        )['patient'];
    }

    private function existingPatientForNewPatientEmail(array $state): ?Patient
    {
        $email = trim((string) ($state['new_patient_email'] ?? ''));
        if ($email === '') {
            return null;
        }

        return Patient::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->latest()
            ->first();
    }

    private function relationshipField(callable $get): string
    {
        return $this->relationshipFieldFromValues(
            $get('new_patient_gender'),
            $get('new_patient_marital_status'),
        );
    }

    private function relationshipFieldFromValues(?string $gender, ?string $maritalStatus): string
    {
        if ($maritalStatus === MaritalStatus::MARRIED->value) {
            return $gender === GenderOption::FEMALE->value ? 'husband_name' : 'wife_name';
        }

        return 'father_name';
    }

    private function responseMessage(array $data, bool $isSuccess): string
    {
        if ($isSuccess) {
            return (string) ($data['message'] ?? 'Appointment created successfully.');
        }

        $message = $data['message'] ?? Arr::get($data, 'errors.message.0') ?? Arr::get($data, 'data.message');

        if (is_array($message)) {
            $message = reset($message);
        }

        return (string) ($message ?: 'Unable to book this appointment. Please select another slot and try again.');
    }

    private function buildAppointmentDateOptions(DoctorAvailability $availability): array
    {
        return $this->expandedSlotsForAvailability($availability)
            ->mapWithKeys(fn(DoctorAvailability $slot) => [
                Carbon::parse($slot->date)->format('Y-m-d') => Carbon::parse($slot->date)->format('D, d M Y')
                    . ' | '
                    . Carbon::parse($slot->start_time)->format('g:i A')
                    . ' - '
                    . Carbon::parse($slot->end_time)->format('g:i A')
                    . (($slot->source ?? null) === 'override' ? ' | Override' : ''),
            ])
            ->all();
    }

    private function expandedSlotsForAvailability(DoctorAvailability $availability): \Illuminate\Support\Collection
    {
        $service = app(DoctorAvailabilityService::class);
        $start = Carbon::today();
        $end = $availability->recurring_end_date
            ? Carbon::parse($availability->recurring_end_date)->endOfDay()
            : $start->copy()->addMonths((int) ($availability->recurring_months ?: 3))->endOfDay();

        if (! $service->isRecurringTemplate($availability) && $availability->date) {
            $end = Carbon::parse($availability->date)->endOfDay();
        }

        return $service->expandSlots([$availability], $start, $end, includePast: true);
    }

    private function slotEndsAfterNow(DoctorAvailability $slot): bool
    {
        if (! $slot->date || ! $slot->end_time) {
            return false;
        }

        return Carbon::parse(
            Carbon::parse($slot->date)->toDateString() . ' ' . Carbon::parse($slot->end_time)->format('H:i:s')
        )->greaterThan(now());
    }

    private function effectiveSlotForDate(DoctorAvailability $availability, string $date): ?DoctorAvailability
    {
        return $this->expandedSlotsForAvailability($availability)
            ->first(fn(DoctorAvailability $slot) => Carbon::parse($slot->date)->toDateString() === Carbon::parse($date)->toDateString());
    }

    private function syncEffectiveAvailabilityFields(callable $get, callable $set): void
    {
        $availabilityId = $get('availability_id');
        $date = $get('appointment_date');

        if (! $availabilityId || ! $date) {
            return;
        }

        $availability = DoctorAvailability::with(['doctor', 'overrides'])->find($availabilityId);
        if (! $availability) {
            return;
        }

        $effectiveSlot = $this->effectiveSlotForDate($availability, $date);
        if (! $effectiveSlot) {
            return;
        }

        $set('appointment_time', Carbon::parse($effectiveSlot->start_time)->format('H:i'));
        $set('consultation_type', $effectiveSlot->consultation_type);

        if ($effectiveSlot->consultation_type === 'in-person' && $effectiveSlot->opd_type) {
            $set('opd_type', $effectiveSlot->opd_type);
        }

        $this->setAvailabilityDetails($availability, $effectiveSlot);
    }

    private function setAvailabilityDetails(DoctorAvailability $availability, DoctorAvailability $effectiveSlot): void
    {
        $this->availabilityDetails = [
            'id' => $availability->id,
            'date' => Carbon::parse($effectiveSlot->date)->format('d M Y'),
            'is_recurring' => app(DoctorAvailabilityService::class)->isRecurringTemplate($availability),
            'recurring_start_date' => $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->format('d M Y') : null,
            'recurring_end_date' => $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->format('d M Y') : null,
            'start_time' => Carbon::parse($effectiveSlot->start_time)->format('H:i'),
            'end_time' => Carbon::parse($effectiveSlot->end_time)->format('H:i'),
            'consultation_fee' => $effectiveSlot->consultation_fee ?? 0,
            'consultation_type' => $effectiveSlot->consultation_type,
            'opd_type' => $effectiveSlot->opd_type ?? null,
            'capacity' => $effectiveSlot->capacity ?? 1,
            'is_available' => $effectiveSlot->is_available,
            'source' => $effectiveSlot->source ?? 'availability',
            'availability_override_id' => $effectiveSlot->override_id ?? null,
            'doctor_name' => $availability->doctor ? $availability->doctor->first_name . ' ' . ($availability->doctor->last_name ?? '') : 'N/A',
        ];
    }
}
