<?php

namespace App\Filament\Pages;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController;
use Illuminate\Http\JsonResponse;
use App\Services\SettingService;

use App\Traits\HasCustomSidebar;

class TestRazorpayBooking extends Page implements HasForms
{
    use InteractsWithForms;
    use HasCustomSidebar;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?string $title = 'Appointment Booking';
    protected static ?string $navigationLabel = 'Appointment Booking';
    protected static ?string $slug = 'test-razorpay-booking';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.pages.test-razorpay-booking';

    public ?array $data = [];
    public ?array $result = null;
    public bool $showResult = false;
    public ?array $availabilityDetails = null;
    public array $appointmentDateOptions = [];
    public string $paymentMode = 'Live';
    public ?string $currentAppointmentId = null;
    // public static function canAccess(): bool
    // {
    //     return app()->isLocal();
    // }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Appointment Booking',
            'icon'  => 'heroicon-o-calendar-days',
            'sort'  => 1000,
            'group' => 'Appointment Management',
        ];
    }

    public function mount(): void
    {
        $this->paymentMode = SettingService::isAppointmentMockPaymentEnabled() ? 'Mock' : 'Live';
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Booking Details')
                    ->description('Create and manage appointment bookings from the admin panel')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(Patient::with('user')->get()->mapWithKeys(function ($patient) {
                                $name = $patient->first_name . ' ' . ($patient->last_name ?? '');
                                if ($patient->user) {
                                    $name .= ' (' . $patient->user->email . ')';
                                }
                                return [$patient->id => $name];
                            }))
                            ->searchable()
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Select::make('doctor_id')
                            ->label('Doctor')
                            ->options(Doctor::with('user')->get()->mapWithKeys(function ($doctor) {
                                $name = 'Dr. ' . $doctor->first_name . ' ' . ($doctor->last_name ?? '');
                                if ($doctor->user) {
                                    $name .= ' (' . $doctor->user->email . ')';
                                }
                                return [$doctor->id => $name];
                            }))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, callable $set) => $set('availability_id', null))
                            ->columnSpan(1),

                        Select::make('availability_id')
                            ->label('Availability Slot')
                            ->options(function (callable $get) {
                                $doctorId = $get('doctor_id');
                                if (!$doctorId) {
                                    return [];
                                }

                                return DoctorAvailability::where('doctor_id', $doctorId)
                                    ->where('is_available', true)
                                    ->get()
                                    ->mapWithKeys(function ($availability) {
                                        $date = $availability->date
                                            ? Carbon::parse($availability->date)->format('d M Y')
                                            : ($availability->day_of_week ?? 'Recurring');

                                        $startTime = $availability->start_time instanceof Carbon
                                            ? $availability->start_time->format('H:i')
                                            : ($availability->start_time ? Carbon::parse($availability->start_time)->format('H:i') : 'N/A');

                                        $endTime = $availability->end_time instanceof Carbon
                                            ? $availability->end_time->format('H:i')
                                            : ($availability->end_time ? Carbon::parse($availability->end_time)->format('H:i') : 'N/A');

                                        $fee = $availability->consultation_fee ? '₹' . number_format($availability->consultation_fee, 2) : 'Free';
                                        $type = $availability->consultation_type === 'video' ? 'Video' : 'In-Person';

                                        $label = "{$date} | {$startTime} - {$endTime} | {$type} | {$fee}";

                                        return [$availability->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $this->appointmentDateOptions = [];
                                if ($state) {
                                    $availability = DoctorAvailability::with('doctor')->find($state);
                                    if ($availability) {
                                        $dateOptions = $this->buildAppointmentDateOptions($availability);
                                        $this->appointmentDateOptions = $dateOptions;
                                        $set('appointment_date', array_key_first($dateOptions) ?: null);

                                        // Auto-populate time from availability (use start_time)
                                        if ($availability->start_time) {
                                            $time = $availability->start_time instanceof Carbon
                                                ? $availability->start_time->format('H:i')
                                                : Carbon::parse($availability->start_time)->format('H:i');
                                            $set('appointment_time', $time);
                                        }

                                        // Auto-populate consultation type
                                        if ($availability->consultation_type) {
                                            $set('consultation_type', $availability->consultation_type);
                                        }

                                        // Auto-populate OPD type for in-person consultations
                                        if ($availability->consultation_type === 'in-person' && $availability->opd_type) {
                                            $set('opd_type', $availability->opd_type);
                                        }

                                        // Store availability details for display
                                        $this->availabilityDetails = [
                                            'id' => $availability->id,
                                            'date' => $availability->date ? Carbon::parse($availability->date)->format('d M Y') : ($availability->day_of_week ?? 'Recurring'),
                                            'is_recurring' => (bool) $availability->is_recurring,
                                            'recurring_start_date' => $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->format('d M Y') : null,
                                            'recurring_end_date' => $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->format('d M Y') : null,
                                            'start_time' => $availability->start_time instanceof Carbon
                                                ? $availability->start_time->format('H:i')
                                                : ($availability->start_time ? Carbon::parse($availability->start_time)->format('H:i') : 'N/A'),
                                            'end_time' => $availability->end_time instanceof Carbon
                                                ? $availability->end_time->format('H:i')
                                                : ($availability->end_time ? Carbon::parse($availability->end_time)->format('H:i') : 'N/A'),
                                            'consultation_fee' => $availability->consultation_fee ?? 0,
                                            'consultation_type' => $availability->consultation_type,
                                            'opd_type' => $availability->opd_type ?? null,
                                            'capacity' => $availability->capacity ?? 1,
                                            'is_available' => $availability->is_available,
                                            'doctor_name' => $availability->doctor ? 'Dr. ' . $availability->doctor->first_name . ' ' . ($availability->doctor->last_name ?? '') : 'N/A',
                                        ];
                                    }
                                } else {
                                    $this->availabilityDetails = null;
                                    $set('appointment_date', null);
                                }
                            })
                            ->disabled(fn(callable $get) => !$get('doctor_id'))
                            ->columnSpan(1),

                        Select::make('appointment_date')
                            ->label('Appointment Date')
                            ->options(fn() => $this->appointmentDateOptions)
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Select availability slot to load valid dates')
                            ->helperText(function (callable $get) {
                                if (!$get('availability_id')) {
                                    return 'Select an availability slot first.';
                                }

                                if (empty($this->appointmentDateOptions)) {
                                    return 'No upcoming dates are available for this slot.';
                                }

                                return 'Dates are auto-generated from the selected slot.';
                            })
                            ->columnSpan(1),

                        TimePicker::make('appointment_time')
                            ->label('Appointment Time')
                            ->required()
                            ->seconds(false)
                            ->helperText('Auto-filled from selected availability')
                            ->columnSpan(1),

                        Select::make('consultation_type')
                            ->label('Consultation Type')
                            ->options([
                                'in-person' => 'In-Person',
                                'video' => 'Video',
                            ])
                            ->required()
                            ->default('in-person')
                            ->live()
                            ->helperText('Auto-filled from selected availability')
                            ->columnSpan(1),

                        Select::make('opd_type')
                            ->label('OPD Type')
                            ->options([
                                'general' => 'General',
                                'private' => 'Private',
                            ])
                            ->required(fn(callable $get) => $get('consultation_type') === 'in-person')
                            ->visible(fn(callable $get) => $get('consultation_type') === 'in-person')
                            ->helperText('Required for in-person consultations')
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $get('availability_id')) {
                                    $availability = DoctorAvailability::find($get('availability_id'));
                                    if ($availability && $availability->opd_type && $availability->opd_type !== $state) {
                                        // Show warning if OPD type doesn't match availability
                                        Notification::make()
                                            ->title('OPD Type Mismatch')
                                            ->body('Selected OPD type does not match the availability slot. Please verify.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Notes (Optional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }




    public function testBooking(): void
    {
        // Keep banner mode in sync with runtime settings.
        $this->paymentMode = SettingService::isAppointmentMockPaymentEnabled() ? 'Mock' : 'Live';
        $this->validate();

        // Because your form uses ->statePath('data')
        $payload = $this->data;

        try {
            // Create a fake API request
            $request = Request::create(
                '/api/v2/test-book-appointment',
                'POST',
                $payload
            );

            // Force JSON mode
            $request->headers->set('Accept', 'application/json');

            app()->instance('request', $request);

            // Call controller
            $controller = app(BookAppointmentController::class);
            $response = $controller->book($request);

            if (!$response instanceof JsonResponse) {
                throw new \Exception("Controller did not return JSON response");
            }

            $data = $response->getData(true);

            $this->result = [
                'status' => $response->isSuccessful() ? 'success' : 'error',
                'status_code' => $response->status(),
                'response' => $data,
                'raw_response' => json_encode($data),
            ];

            $this->showResult = true;
        } catch (\Exception $e) {
            $this->result = [
                'status' => 'error',
                'status_code' => 500,
                'response' => ['message' => $e->getMessage()],
                'raw_response' => $e->getMessage(),
            ];

            $this->showResult = true;
        }
    }



    public function clearResult(): void
    {
        $this->result = null;
        $this->showResult = false;
    }

    private function buildAppointmentDateOptions(DoctorAvailability $availability): array
    {
        if (!$availability->is_recurring && $availability->date) {
            $slotDate = Carbon::parse($availability->date)->startOfDay();

            if ($slotDate->lt(today()->startOfDay())) {
                return [];
            }

            return [
                $slotDate->format('Y-m-d') => $slotDate->format('D, d M Y'),
            ];
        }

        if (!$availability->is_recurring) {
            return [];
        }

        $endDate = $availability->recurring_end_date
            ? Carbon::parse($availability->recurring_end_date)->endOfDay()
            : now()->copy()->addMonths((int) ($availability->recurring_months ?: 3))->endOfDay();

        if ($endDate->isPast()) {
            return [];
        }

        $startDate = $availability->recurring_start_date
            ? Carbon::parse($availability->recurring_start_date)->startOfDay()
            : now()->startOfDay();

        $cursor = $startDate->greaterThan(today()->startOfDay())
            ? $startDate->copy()
            : today()->startOfDay();

        $targetDay = strtolower((string) $availability->day_of_week);
        if ($targetDay === '') {
            return [];
        }

        while (strtolower($cursor->format('l')) !== $targetDay) {
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
