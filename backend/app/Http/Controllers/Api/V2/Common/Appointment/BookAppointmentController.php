<?php

namespace App\Http\Controllers\Api\V2\Common\Appointment;

use App\Http\Controllers\Controller;
use App\Enums\{AppointmentStatus, PaymentStatus};
use App\Jobs\{CreateVideoRoomJob, GenerateReceiptJob, ProcessRazorpayWebhook, SendBookingEmailJob};
use App\Models\{Appointment, Doctor, DoctorAvailability, Patient, Payment};
use App\Notifications\MobileNotification;
use App\Services\{ApiResponseService, DoctorAvailabilityService, PaymentService, SettingService, SlotCapacityService, WherebyService};
use App\Http\Resources\Common\{AppointmentDetailResource, AppointmentResource};
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Illuminate\Support\Str;
use App\Notifications\SystemNotification;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Validator;



class BookAppointmentController extends Controller
{
    protected PaymentService $paymentService;

    protected $paymentOrderResult = null;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Book appointment with payment processing
     * Works for both app and WordPress
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function book(Request $request)
    {
        $user = $request->user();
        try {
            // Validate request data
            $validationRules = [
                'doctor_id' => ['required', 'string'],
                'availability_id' => ['required', 'string'],
                'appointment_date' => ['required', 'date'],
                'appointment_time' => ['required', 'string'],
                'consultation_type' => ['required', 'string', 'in:in-person,video'],
                'admin_skip_payment' => ['nullable', 'boolean'],
                // Only validate opd_type if consultation_type is in-person
            ];

            // Add opd_type validation only for in-person consultation
            if ($request->input('consultation_type') === 'in-person') {
                $validationRules['opd_type'] = ['required', 'string', 'in:general,private'];
            } else {
                $validationRules['opd_type'] = ['nullable'];
            }
            $data = $request->validate($validationRules);
            $user = $request->user();
            $useMockPayment = SettingService::isAppointmentMockPaymentEnabled();
            $isAdminBooking = $request->has('admin_skip_payment') && $this->canAdminBookWithoutPayment($user);
            $adminSkipPayment = $request->boolean('admin_skip_payment') && $isAdminBooking;


            /*
            |--------------------------------------------------------------------------
            | WEBSITE FLOW
            |--------------------------------------------------------------------------
            */

            if ($request->filled('patient_id')) {

                $patient = Patient::find($request->patient_id);

                if (!$patient) {
                    return ApiResponseService::validationError([
                        'patient' => ['Patient not found.']
                    ]);
                }

                /*
            |--------------------------------------------------------------------------
            | MOBILE APP FLOW
            |--------------------------------------------------------------------------
            */
            } else {

                if (!$user) {
                    return ApiResponseService::validationError([
                        'user' => ['Unauthenticated.']
                    ]);
                }

                if ($user->hasRole('super_admin')) {

                    $patient = $request->patient_id
                        ? Patient::find($request->patient_id)
                        : null;
                } else {

                    $patient = Patient::where('user_id', $user->id)->first();

                    if (!$patient) {
                        return ApiResponseService::validationError([
                            'patient' => ['Patient record not found.']
                        ]);
                    }
                }
            }



            $doctor = Doctor::query()
                ->active()
                ->find($data['doctor_id']);

            if (! $doctor) {
                return ApiResponseService::validationError('This doctor is not available for booking.');
            }

            // Validate availability
            $availability = DoctorAvailability::findOrFail($data['availability_id']);

            // Check if availability belongs to the doctor
            if ($availability->doctor_id !== $doctor->id) {
                return ApiResponseService::validationError('The selected availability does not belong to this doctor.');
            }

            // Check if availability is active
            if (! $availability->is_available) {
                return ApiResponseService::validationError('This time slot is not available for booking.');
            }

            // Check if availability is blocked for the selected date
            if ($availability->isBlockedOnDate($data['appointment_date'])) {
                return ApiResponseService::validationError('This time slot is blocked for the selected date.');
            }

            // Validate consultation type matches
            if ($availability->consultation_type !== $data['consultation_type']) {
                return ApiResponseService::validationError('Consultation type does not match the selected availability.');
            }

            // Validate OPD type for in-person consultations
            if ($data['consultation_type'] === 'in-person') {
                // OPD type is required for in-person consultations
                if (empty($data['opd_type'])) {
                    return ApiResponseService::validationError('OPD type is required for in-person consultations. Please specify general or private.');
                }

                // Validate OPD type matches availability
                if ($availability->opd_type && $availability->opd_type !== $data['opd_type']) {
                    return ApiResponseService::validationError('OPD type does not match the selected availability. Expected: ' . ucfirst($availability->opd_type) . ', Provided: ' . ucfirst($data['opd_type']));
                }
            }

            // Validate appointment date matches availability date
            $requestedDate = Carbon::parse($data['appointment_date'])->startOfDay();

            $availabilityService = app(DoctorAvailabilityService::class);
            $isRecurringTemplate = $availabilityService->isRecurringTemplate($availability);

            if ($isRecurringTemplate) {
                $recurringStart = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->startOfDay() : null;
                $recurringEnd = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->startOfDay() : null;

                // 1. Check range
                if ($recurringStart && $requestedDate->lt($recurringStart)) {
                    return ApiResponseService::validationError('Appointment date cannot be before the recurring availability start date (' . $recurringStart->format('Y-m-d') . ').');
                }

                if ($recurringEnd && $requestedDate->gt($recurringEnd)) {
                    return ApiResponseService::validationError('Appointment date is after the recurring availability end date (' . $recurringEnd->format('Y-m-d') . ').');
                }

                $targetDayOfWeek = $availabilityService->recurringDayOfWeek($availability, $recurringStart ?: $requestedDate);
                if ($targetDayOfWeek && strtolower($requestedDate->format('l')) !== strtolower($targetDayOfWeek)) {
                    return ApiResponseService::validationError("This slot is only available on {$targetDayOfWeek}s. The selected date is a " . $requestedDate->format('l') . ".");
                }
            } else {
                $availabilityDate = $availability->date ? Carbon::parse($availability->date)->startOfDay() : null;

                if ($availabilityDate && !$requestedDate->equalTo($availabilityDate)) {
                    return ApiResponseService::validationError('Appointment date does not match the selected availability date.');
                }
            }

            // Validate appointment time is within availability slot
            $effectiveSlot = $availabilityService->effectiveValuesForDate($availability, $requestedDate);

            if (in_array($effectiveSlot['status'], ['blocked', 'cancelled'], true)) {
                return ApiResponseService::validationError('This time slot is blocked for the selected date.');
            }

            if ($childSlotValidationResponse = $this->validateChildOnlySlot($availability, $patient)) {
                return $childSlotValidationResponse;
            }

            $appointmentTime = Carbon::parse($data['appointment_time'])->format('H:i:s');
            $startTime = $effectiveSlot['start_time'] ? Carbon::parse($effectiveSlot['start_time'])->format('H:i:s') : null;
            $endTime = $effectiveSlot['end_time'] ? Carbon::parse($effectiveSlot['end_time'])->format('H:i:s') : null;
            $effectiveCapacity = (int) ($effectiveSlot['capacity'] ?? 1);
            $effectiveFee = (float) ($effectiveSlot['consultation_fee'] ?? 0);
            $override = $effectiveSlot['override'];

            if ($startTime && $endTime && ($appointmentTime < $startTime || $appointmentTime > $endTime)) {
                return ApiResponseService::validationError('Appointment time must be within the selected availability slot.');
            }

            // ---------------- LOCK START ----------------
            // Key based on doctor, availability and date to prevent overbooking the same slot
            $lockKey = 'booking_slot_' . $availability->id . '_' . $requestedDate->toDateString();
            $lock = Cache::lock($lockKey, 30);

            try {
                // Block for up to 10 seconds to allow other concurrent requests to finish
                $lock->block(10);

                return DB::transaction(function () use ($requestedDate, $startTime, $endTime, $availability, $data, $patient, $doctor, $useMockPayment, $isAdminBooking, $adminSkipPayment, $effectiveCapacity, $effectiveFee, $override) {

                    // 1. Capacity check INSIDE the transaction to prevent overbooking
                    $existingCount = app(SlotCapacityService::class)->bookedCount(
                        doctorId: $doctor->id,
                        date: $requestedDate,
                        startTime: $startTime,
                        availabilityId: $availability->id,
                        consultationType: $data['consultation_type'],
                    );

                    if ($existingCount >= $effectiveCapacity) {
                        return ApiResponseService::error(
                            'This time slot is fully booked. Please select another slot.',
                            ['message' => 'Slot capacity reached.'],
                            422
                        );
                    }

                    // 2. Idempotency check: see if THIS patient already has an appointment for this slot
                    $appointment = Appointment::where('patient_id', $patient->id)
                        ->where('availability_id', $availability->id)
                        ->whereDate('appointment_date', $requestedDate)
                        ->whereIn('status', [
                            AppointmentStatus::PENDING->value,
                            AppointmentStatus::CONFIRMED->value,
                            AppointmentStatus::RESCHEDULED->value,
                        ])
                        ->latest()
                        ->first();

                    if ($appointment) {
                        // Check if already paid/confirmed in both tables as requested
                        $hasPaidPayment = $appointment->payment()
                            ->where('status', PaymentStatus::PAID->value)
                            ->exists();

                        if ($appointment->status === AppointmentStatus::CONFIRMED->value || $hasPaidPayment) {
                            return ApiResponseService::error(
                                'This appointment is already booked.',
                                ['message' => 'Appointment already booked.'],
                                422
                            );
                        }

                        // Reset created_at to now so it looks like a fresh booking attempt (requested by user)
                        $appointment->created_at = now();
                        $this->applyAdminPaymentMetadata($appointment, $isAdminBooking, $adminSkipPayment);
                        $appointment->save();

                        if ($adminSkipPayment) {
                            $appointment->update(['status' => AppointmentStatus::CONFIRMED->value]);
                            NotificationService::notifyAppointmentConfirmed($appointment);
                            $appointment->load(['doctor.user', 'patient.user', 'availability', 'doctor.departments']);

                            if ($appointment->consultation_type === 'video') {
                                CreateVideoRoomJob::dispatch($appointment->id);
                            }

                            return $this->handleSuccess($appointment, null);
                        }

                        if ($useMockPayment) {
                            $payment = $this->markAppointmentAsMockPaid($appointment);
                            $appointment->load(['doctor.user', 'patient.user', 'payment', 'availability', 'doctor.departments']);

                            return $this->handleSuccess($appointment, $payment);
                        }

                        // Refresh payment order to ensure valid key/order pair
                        $paymentOrderResult = $this->paymentService->createPaymentForAppointment($appointment);
                        $this->paymentOrderResult = $paymentOrderResult;

                        // Reload payment relationship since we might have created a new one
                        $appointment->load('payment');

                        return $this->handleSuccess($appointment, $appointment->payment);
                    }

                    // 3. Create appointment
                    $consultationFee = $effectiveFee;
                    $appointment = Appointment::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'availability_id' => $availability->id,
                        'availability_override_id' => $override?->id,
                        'appointment_date' => $requestedDate->toDateString(),
                        'appointment_time' => $startTime,
                        'appointment_end_time' => $endTime,
                        'status' => AppointmentStatus::PENDING->value,
                        'consultation_type' => $data['consultation_type'],
                        'notes' => $data['notes'] ?? null,
                        'fee_amount' => $consultationFee,
                        'booking_source' => $isAdminBooking ? 'admin' : 'patient',
                        'admin_payment_type' => $isAdminBooking
                            ? ($adminSkipPayment ? 'without_payment' : 'with_payment')
                            : null,
                        'payment_waived_by' => $adminSkipPayment ? optional(request()->user())->id : null,
                        'payment_waived_at' => $adminSkipPayment ? now() : null,
                        'slug' => Str::slug($doctor->first_name . '-' . $patient->id . '-' . time()),
                    ]);

                    $payment = null;

                    if ($consultationFee <= 0 || $adminSkipPayment) {
                        $appointment->update(['status' => AppointmentStatus::CONFIRMED->value]);
                        NotificationService::notifyAppointmentConfirmed($appointment);

                        if ($appointment->consultation_type === 'video') {
                            CreateVideoRoomJob::dispatch($appointment->id);
                        }
                    } elseif ($useMockPayment) {
                        $payment = $this->markAppointmentAsMockPaid($appointment);
                    } else {
                        // 4. Create/Retrieve payment order
                        $paymentOrderResult = $this->paymentService->createPaymentForAppointment($appointment);
                        $this->paymentOrderResult = $paymentOrderResult;
                    }

                    // Load relationships for view
                    $appointment->load(['doctor.user', 'patient.user', 'payment']);
                    $payment ??= $appointment->payment;

                    return $this->handleSuccess($appointment, $payment);
                });
            } catch (LockTimeoutException $e) {
                return ApiResponseService::error(
                    'Booking is taking longer than expected. Please check your appointments list.',
                    ['message' => 'Booking is taking longer than expected. Please check your appointments list.'],
                    429
                );
            } finally {
                optional($lock)->release();
            }
            // ---------------- LOCK END ----------------

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            Log::error('Appointment booking error: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponseService::serverError($e);
        }
    }


    public function reschedule(Request $request, WherebyService $wherebyService)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'appointment_id' => ['required', 'uuid'],
            'availability_id' => ['required', 'uuid'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $data = $validator->validated();
        $appointment = Appointment::with(['doctor', 'patient', 'videoConsultation'])
            ->findOrFail($data['appointment_id']);


        // ---------------- LOCK START ----------------
        $lockKey = 'reschedule_appointment_' . $appointment->id;
        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            $appointment->refresh();


            if (AppointmentStatus::equals($appointment->status, AppointmentStatus::COMPLETED)) {
                return ApiResponseService::validationError('Cannot reschedule a completed appointment.');
            }

            if (AppointmentStatus::equals($appointment->status, AppointmentStatus::CANCELLED)) {
                return ApiResponseService::validationError('Cannot reschedule a cancelled appointment.');
            }

            // Normalize appointment date safely
            $appointmentDate = $appointment->appointment_date instanceof Carbon
                ? $appointment->appointment_date->format('Y-m-d')
                : Carbon::parse($appointment->appointment_date)->format('Y-m-d');

            // Normalize time safely (handles H:i or H:i:s)
            $appointmentTime = strlen($appointment->appointment_time) === 5
                ? $appointment->appointment_time . ':00'
                : $appointment->appointment_time;

            $appointmentAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                "{$appointmentDate} {$appointmentTime}"
            );

            if (now()->greaterThanOrEqualTo($appointmentAt->copy()->subHour())) {
                return ApiResponseService::validationError('Rescheduling allowed only until 1 hour before appointment start.');
            }

            $doctor = $appointment->doctor;
            if (! $doctor) {
                return ApiResponseService::notFound();
            }

            $availability = DoctorAvailability::findOrFail($data['availability_id']);

            if ($availability->doctor_id !== $doctor->id) {
                return ApiResponseService::validationError('The selected availability does not belong to this doctor.');
            }

            if (! $availability->is_available) {
                return ApiResponseService::validationError('This time slot is not available for booking.');
            }

            // Check if availability is blocked for the selected date
            if ($availability->isBlockedOnDate($data['appointment_date'])) {
                return ApiResponseService::validationError('This time slot is blocked for the selected date.');
            }

            $newAppointmentDate = Carbon::parse($data['appointment_date'])->format('Y-m-d');

            $newAppointmentTime = strlen($data['appointment_time']) === 5
                ? $data['appointment_time'] . ':00'
                : $data['appointment_time'];
            $availabilityService = app(DoctorAvailabilityService::class);
            $effectiveSlot = $availabilityService->effectiveValuesForDate($availability, $newAppointmentDate);

            if (in_array($effectiveSlot['status'], ['blocked', 'cancelled'], true)) {
                return ApiResponseService::validationError('This time slot is blocked for the selected date.');
            }

            if ($childSlotValidationResponse = $this->validateChildOnlySlot($availability, $appointment->patient)) {
                return $childSlotValidationResponse;
            }

            // Check if rescheduling to the same slot
            if (
                $appointment->availability_id === $availability->id &&
                $appointmentDate === $newAppointmentDate &&
                $appointmentTime === $newAppointmentTime
            ) {
                return ApiResponseService::validationError('You cannot reschedule to the same time slot.');
            }

            $newAppointmentEndTime = $effectiveSlot['end_time']
                ? Carbon::parse($effectiveSlot['end_time'])->format('H:i:s')
                : null;


            if ($availabilityService->isRecurringTemplate($availability)) {
                $recurringStart = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->startOfDay() : null;
                $recurringEnd = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->startOfDay() : null;
                $selectedDate = Carbon::parse($newAppointmentDate)->startOfDay();

                if ($recurringStart && $selectedDate->lt($recurringStart)) {
                    return ApiResponseService::validationError('Appointment date cannot be before the recurring availability start date (' . $recurringStart->format('Y-m-d') . ').');
                }

                if ($recurringEnd && $selectedDate->gt($recurringEnd)) {
                    return ApiResponseService::validationError('Appointment date is after the recurring availability end date (' . $recurringEnd->format('Y-m-d') . ').');
                }

                $targetDayOfWeek = $availabilityService->recurringDayOfWeek($availability, $recurringStart ?: $selectedDate);
                if ($targetDayOfWeek && strtolower($selectedDate->format('l')) !== strtolower($targetDayOfWeek)) {
                    return ApiResponseService::validationError("This slot is only available on {$targetDayOfWeek}s. The selected date is a " . $selectedDate->format('l') . '.');
                }
            } else {
                $availabilityDate = $availability->date
                    ? Carbon::parse($availability->date)->format('Y-m-d')
                    : null;

                if ($availabilityDate && $newAppointmentDate !== $availabilityDate) {
                    return ApiResponseService::validationError('Appointment date does not match the selected availability date.');
                }
            }

            $startTime = $effectiveSlot['start_time']
                ? Carbon::parse($effectiveSlot['start_time'])->format('H:i:s')
                : null;

            $endTime = $effectiveSlot['end_time']
                ? Carbon::parse($effectiveSlot['end_time'])->format('H:i:s')
                : null;

            if ($startTime && $endTime && ($newAppointmentTime < $startTime || $newAppointmentTime > $endTime)) {
                return ApiResponseService::validationError('Appointment time must be within the selected availability slot.');
            }

            $newConsultationType = $availability->consultation_type ?? $appointment->consultation_type;

            $existingCount = app(SlotCapacityService::class)->bookedCount(
                doctorId: $doctor->id,
                date: $newAppointmentDate,
                startTime: $startTime,
                availabilityId: $availability->id,
                consultationType: $newConsultationType,
                excludeAppointmentId: $appointment->id,
            );

            if ($existingCount >= (int) ($effectiveSlot['capacity'] ?? 1)) {
                return ApiResponseService::error(
                    'This time slot is fully booked. Please select another slot.',
                    ['message' => 'Slot is fully booked.'],
                    422
                );
            }

            $newConsultationFee = (float) ($effectiveSlot['consultation_fee'] ?? 0);

            /**
             * ✅ UPDATE + STATUS = RESCHEDULED
             */
            $appointment->update([
                'availability_id' => $availability->id,
                'availability_override_id' => $effectiveSlot['override']?->id,
                'appointment_date' => $newAppointmentDate,
                'appointment_time' => $newAppointmentTime,
                'appointment_end_time' => $newAppointmentEndTime,
                'consultation_type' => $newConsultationType,
                'fee_amount' => $newConsultationFee,
                'status' => AppointmentStatus::RESCHEDULED->value,
            ]);

            NotificationService::notifyAppointmentRescheduled($appointment);


            if ($appointment->consultation_type === 'video') {
                if ($appointment->videoConsultation) {
                    $wherebyService->regenerateUrls($appointment->videoConsultation);
                } else {
                    CreateVideoRoomJob::dispatch($appointment->id);
                }
            }
            return ApiResponseService::success(
                'responses.appointment.rescheduled',
                data: [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'date' => $appointment->appointment_date,
                    'time' => Carbon::parse($appointment->appointment_time)->format('h:i A') . ' - ' . Carbon::parse($appointment->appointment_end_time)->format('h:i A'),
                    'consultation_type' => $appointment->consultation_type,
                    'fee_amount' => $appointment->fee_amount,
                    'appointment_status' => $appointment->status,
                ]
            );
        } catch (LockTimeoutException $e) {
            return ApiResponseService::rateLimited();
        } finally {
            optional($lock)->release();
        }
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'appointment_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $appointment = Appointment::findOrFail($validated['appointment_id']);

        $lockKey = 'cancel_appointment_' . $appointment->id;
        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            // Refresh model to get latest status
            $appointment->refresh();

            // Only allow cancellation if CONFIRMED or RESCHEDULED
            if (
                ! AppointmentStatus::equals($appointment->status, AppointmentStatus::CONFIRMED) &&
                ! AppointmentStatus::equals($appointment->status, AppointmentStatus::RESCHEDULED)
            ) {
                return ApiResponseService::validationError('Only appointments with status CONFIRMED or RESCHEDULED can be cancelled.');
            }

            $appointmentAt = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);

            if (now()->greaterThanOrEqualTo($appointmentAt->copy()->subHour())) {
                return ApiResponseService::validationError('Cancellation is only allowed up to 1 hour before the appointment time.');
            }

            $appointment->update([
                'status' => AppointmentStatus::CANCELLED->value,
            ]);

            NotificationService::notifyAppointmentCancelled($appointment, 'patient');


            return ApiResponseService::success(
                responseKey: 'responses.appointment.cancelled',
                data: [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status,
                ]
            );
        } catch (LockTimeoutException $e) {
            return ApiResponseService::rateLimited();
        } finally {
            optional($lock)->release();
        }
    }
    /**
     * Handle successful booking
     */
    protected function handleSuccess(Appointment $appointment, $payment = null)
    {
        if (! request()->expectsJson() && ! request()->is('api/*')) {
            return null;
        }

        $appointment->loadMissing(['doctor.user', 'patient.user']);

        $patientUser = $appointment->patient?->user;
        $doctorUser = $appointment->doctor?->user;

        $basePayload = [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'consultation_type' => $appointment->consultation_type,
        ];

        $paymentData = null;

        if ($payment) {
            $paymentData = [
                'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : ($payment->status ?? PaymentStatus::PENDING->value),
                'order_id' => $payment->razorpay_order_id ?? null,
                'payment_id' => $payment->razorpay_payment_id ?? null,
                'amount' => $payment->amount ?? null,
                'amount_paise' => (int) round(($payment->amount ?? 0) * 100),
                'razorpay_key_id' => config('services.razorpay.key_id', env('RAZORPAY_KEY_ID')),
                'payment_required' => false,
                'mock_payment' => $payment->payment_method === 'mock',
            ];
        } elseif ($this->paymentOrderResult) {
            // Include payment order details for Razorpay popup
            $paymentData = [
                'status' => PaymentStatus::PENDING->value,
                'order_id' => $this->paymentOrderResult['order']['id'] ?? null,
                'amount_rupees' => $this->paymentOrderResult['amount_rupees'] ?? 0,
                'amount_paise' => $this->paymentOrderResult['amount_paise'] ?? 0,
                'razorpay_key_id' => $this->paymentOrderResult['key_id'] ?? null,
                'payment_required' => ($this->paymentOrderResult['amount_rupees'] ?? 0) > 0,
            ];
        } elseif (($appointment->booking_source ?? null) === 'admin' && ($appointment->admin_payment_type ?? null) === 'without_payment') {
            $paymentData = [
                'status' => 'admin_without_payment',
                'amount' => 0,
                'amount_paise' => 0,
                'payment_required' => false,
                'admin_payment_type' => 'without_payment',
            ];
        }

        return ApiResponseService::created(
            'responses.appointment.created',
            [
                'appointment' => [
                    'id' => $appointment->id,
                    'slug' => $appointment->slug,
                    'date' => $appointment->appointment_date,
                    'time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                ],
                'payment' => $paymentData,
            ]
        );
    }

    protected function canAdminBookWithoutPayment($user): bool
    {
        if (! $user) {
            return false;
        }

        foreach (['super_admin', 'admin', 'doctor_manager', 'receptionist'] as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }
        }

        return method_exists($user, 'can')
            && (
                $user->can('book-appointment.manage_own')
                || $user->can('book-appointment.view')
                || $user->can('appointments.create')
            );
    }

    protected function applyAdminPaymentMetadata(Appointment $appointment, bool $isAdminBooking, bool $adminSkipPayment): void
    {
        if (! $isAdminBooking) {
            return;
        }

        $appointment->booking_source = 'admin';
        $appointment->admin_payment_type = $adminSkipPayment ? 'without_payment' : 'with_payment';

        if ($adminSkipPayment) {
            $appointment->payment_waived_by = optional(request()->user())->id;
            $appointment->payment_waived_at = now();
        }
    }

    protected function validateChildOnlySlot(DoctorAvailability $availability, ?Patient $patient)
    {
        if (! (bool) ($availability->is_child_only ?? false)) {
            return null;
        }

        $childAgeLimit = SettingService::getChildAgeLimit();
        $patientAge = $patient?->age;

        if ($patientAge === null || $patientAge === '' || ! is_numeric($patientAge) || (int) $patientAge > $childAgeLimit) {
            return ApiResponseService::validationError(
                "This slot is for children only. Patient age must be {$childAgeLimit} years or below."
            );
        }

        return null;
    }

    /**
     * Verify payment after Razorpay checkout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        Log::info('Razorpay verifyPayment hit', [
            'request' => $request->all(),
        ]);
        $validationRules = [
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'appointment_id' => ['required', 'uuid', 'exists:appointments,id'],
            'razorpay_signature' => ['nullable'],
        ];

        try {
            $data = $request->validate($validationRules);
            Log::info('Razorpay verifyPayment hit', [
                'appointment_id' => $data['appointment_id'] ?? null,
                'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
                'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
            ]);

            // ---------------- LOCK START ----------------
            // Use appointment_id for the lock to prevent multiple simultaneous verifications for the same booking
            $lockKey = 'verify_appointment_' . $data['appointment_id'];
            $lock = Cache::lock($lockKey, 30);

            try {
                // Block for up to 5 seconds. This allows a second request to wait
                // and then return the result of the first request if it succeeds.
                $lock->block(5);

                $appointment = Appointment::with(['payment', 'doctor.departments', 'availability'])->findOrFail($data['appointment_id']);
                $existingPayment = $appointment->payment;

                // Already confirmed → idempotent success (Very common if frontend hammers)
                if (AppointmentStatus::equals($appointment->status, AppointmentStatus::CONFIRMED)) {
                    return $this->returnFullVerificationResponse($appointment, $existingPayment, 'Payment already verified');
                }

                if (! $existingPayment) {
                    return ApiResponseService::error('Invalid payment attempt', ['message' => 'No payment found'], 400);
                }

                // Order mismatch protection
                if ($existingPayment->razorpay_order_id !== $data['razorpay_order_id']) {
                    return ApiResponseService::error('Order mismatch', ['message' => 'Invalid order id'], 400);
                }

                // Verify payment via Razorpay API
                $payment = $this->paymentService->verifyPayment([
                    'razorpay_payment_id' => $data['razorpay_payment_id'],
                    'razorpay_order_id' => $data['razorpay_order_id'],
                    'appointment_id' => $data['appointment_id'],
                ]);

                if (! $payment) {
                    return ApiResponseService::error('responses.operation_failed', ['message' => 'Payment verification failed'], 400);
                }

                // ---------------- SUCCESS FLOW ----------------

                $status = $payment->status instanceof PaymentStatus
                    ? $payment->status->value
                    : strtolower((string) $payment->status);
                Log::info(
                    'Razorpay payment marked ' . $status,
                    [
                        'appointment_id' => $appointment->id,
                        'payment_id' => $payment->id ?? null,
                        'status' => $status,
                    ]
                );
                if ($status === PaymentStatus::PAID->value) {

                    Log::info('Razorpay payment marked PAID', [
                        'appointment_id' => $appointment->id,
                        'payment_id' => $payment->id ?? null,
                        'status' => $status,
                    ]);

                    // Refresh the appointment model in memory to reflect database changes
                    $appointment->refresh();

                    $this->finalizeSuccessfulPayment($appointment, $payment, true);

                    $appointment->load([
                        'payment',
                        'doctor.departments',
                        'availability'
                    ]);
                }

                if ($status === PaymentStatus::FAILED->value) {

                    Log::warning('Razorpay payment marked FAILED', [
                        'appointment_id' => $appointment->id,
                        'payment_id' => $payment->id ?? null,
                        'status' => $status,
                    ]);

                    $appointment->update([
                        'status' => AppointmentStatus::FAILED->value,
                    ]);

                    NotificationService::notifyAppointmentFailed(
                        $appointment,
                        'Payment failed during checkout.'
                    );
                }

                return $this->returnFullVerificationResponse($appointment, $payment, 'Payment verified successfully.');
            } catch (LockTimeoutException $e) {
                return ApiResponseService::rateLimited();
            } finally {
                optional($lock)->release();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponseService::error(
                $e->getMessage(),
                ['message' => 'Verification process failed.'],
                400
            );
        }
    }

    /**
     * Return full verification response data
     */
    protected function returnFullVerificationResponse(Appointment $appointment, $payment, string $message)
    {
        Log::info('Booking verified', [
            'appointment_id' => $appointment->id,
            'payment_id' => $payment->id ?? null,
            'status' => $payment->status instanceof PaymentStatus
                ? $payment->status->value
                : strtolower((string) $payment->status),
            'message' => $message,
        ]);
        $consultationType = $appointment->consultation_type;
        $consultationTypeLabel = $consultationType === 'video'
            ? 'Online Consultation'
            : ($consultationType === 'clinic' || $consultationType === 'in-person'
                ? 'Book In-Clinic Appointment'
                : ucfirst($consultationType));

        $s = $appointment->availability;

        return ApiResponseService::success(
            data: [
                'id' => $appointment->id ?? null,
                'payment_status' => $payment->status?->value,
                'payment_slip_url' => storage_url($payment->receipt_pdf ?? null),
                'doctor_name' => $appointment->doctor
                    ? trim(($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''))
                    : null,
                'doctor_department' => optional($appointment->doctor?->departments->first())->name ?? null,
                'doctor_avatar' => storage_url($appointment->doctor?->avatar ?? null),
                'consultation_type' => $appointment->consultation_type ?? null,
                'consultation_type_label' => $consultationTypeLabel ?? null,
                'schedule_date' => ! empty($appointment->appointment_date)
                    ? Carbon::parse($appointment->appointment_date)->format('l, F d, Y')
                    : null,
                'schedule_time' => ($s && $s->start_time && $s->end_time)
                    ? Carbon::parse($s->start_time)->format('h:i A') . ' - ' . Carbon::parse($s->end_time)->format('h:i A')
                    : null,
            ],
            extra: [
                'message' => $message,
            ]
        );
    }

    protected function markAppointmentAsMockPaid(Appointment $appointment): Payment
    {
        $payment = Payment::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'amount' => (float) ($appointment->fee_amount ?? 0),
                'payment_method' => 'mock',
                'status' => PaymentStatus::PAID->value,
                'transaction_id' => 'mock_txn_' . $appointment->id,
                'razorpay_payment_id' => 'mock_pay_' . $appointment->id,
                'razorpay_order_id' => 'mock_order_' . $appointment->id,
                'captured' => true,
                'notes' => [
                    'mode' => 'mock',
                    'source' => 'book_appointment_api',
                ],
                'full_response' => json_encode([
                    'mode' => 'mock',
                    'status' => PaymentStatus::PAID->value,
                    'appointment_id' => $appointment->id,
                ]),
            ]
        );

        $this->finalizeSuccessfulPayment($appointment, $payment);

        return $payment->fresh();
    }

    protected function finalizeSuccessfulPayment(Appointment $appointment, Payment $payment, bool $forceNotification = false): void
    {
        $appointment->update([
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        if ($forceNotification || ! AppointmentStatus::equals($appointment->status, AppointmentStatus::CONFIRMED)) {
            NotificationService::notifyAppointmentConfirmed($appointment);
        }

        GenerateReceiptJob::dispatch($payment->id);
        CreateVideoRoomJob::dispatch($appointment->id);
        SendBookingEmailJob::dispatch($appointment->id, $payment->id)->delay(now()->addSeconds(10));
    }

    /**
     * Razorpay Webhook Handler
     */
    public function razorpayWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('X-Razorpay-Signature');
            $secret = config('services.razorpay.webhook_secret');

            if (! $secret || ! $signature) {
                Log::error('Razorpay webhook missing secret or signature');

                return response()->json(['status' => 'invalid'], 200);
            }

            $generatedSignature = hash_hmac('sha256', $payload, $secret);

            if (! hash_equals($generatedSignature, $signature)) {
                Log::error('Invalid Razorpay webhook signature');

                return response()->json(['status' => 'invalid'], 200);
            }

            $data = json_decode($payload, true);

            if (! is_array($data)) {
                Log::error('Invalid Razorpay webhook payload');

                return response()->json(['status' => 'invalid'], 200);
            }

            ProcessRazorpayWebhook::dispatch($data);

            return response()->json(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            Log::error('Razorpay webhook handler error', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json(['status' => 'error'], 200);
        }
    }
}