<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Patient;
use App\Models\Payment;
use App\Jobs\{CreateVideoRoomJob, GenerateReceiptJob, SendBookingEmailJob};
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Books an appointment during patient registration (no authenticated HTTP user yet).
 * Mirrors {@see \App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController::book} core logic.
 */
class RegistrationBookingService
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    /**
     * @param  array<string, mixed>  $data  doctor_id, availability_id, appointment_date, appointment_time, consultation_type, opd_type?, visit_reason?
     * @return array{appointment: Appointment, payment: ?Payment, payment_order: ?array}
     */
public function book(Patient $patient, array $data, bool $useMockPayment): array
    {
        Log::info('Registration booking started', $this->buildContext($patient, $data, $useMockPayment));

        $doctor = Doctor::findOrFail($data['doctor_id']);
        $availability = DoctorAvailability::findOrFail($data['availability_id']);

        $this->logCondition('doctor_availability_match', $availability->doctor_id === $doctor->id, $patient, $data, $useMockPayment, [
            'resolved_doctor_id' => $doctor->id,
            'availability_doctor_id' => $availability->doctor_id,
        ]);

        if ($availability->doctor_id !== $doctor->id) {
            $this->failValidation($patient, $data, $useMockPayment, 'doctor_availability_match', 'availability_id', 'The selected availability does not belong to this doctor.');
        }

        $this->logCondition('availability_is_active', (bool) $availability->is_available, $patient, $data, $useMockPayment);

        if (! $availability->is_available) {
            $this->failValidation($patient, $data, $useMockPayment, 'availability_is_active', 'availability_id', 'This time slot is not available for booking.');
        }

        if ($availability->isBlockedOnDate($data['appointment_date'])) {
            $this->failValidation($patient, $data, $useMockPayment, 'availability_is_active', 'availability_id', 'This time slot is blocked for the selected date.');
        }

        $this->logCondition('consultation_type_matches', $availability->consultation_type === $data['consultation_type'], $patient, $data, $useMockPayment, [
            'availability_consultation_type' => $availability->consultation_type,
            'requested_consultation_type' => $data['consultation_type'] ?? null,
        ]);

        if ($availability->consultation_type !== $data['consultation_type']) {
            $this->failValidation($patient, $data, $useMockPayment, 'consultation_type_matches', 'consultation_type', 'Consultation type does not match the selected availability.');
        }

        if ($data['consultation_type'] === 'in-person') {
            $this->logCondition('opd_type_present_for_in_person', ! empty($data['opd_type']), $patient, $data, $useMockPayment, [
                'requested_opd_type' => $data['opd_type'] ?? null,
            ]);

            if (empty($data['opd_type'])) {
                $this->failValidation($patient, $data, $useMockPayment, 'opd_type_present_for_in_person', 'opd_type', 'OPD type is required for in-person consultations.');
            }

            $matchesOpdType = ! $availability->opd_type || $availability->opd_type === $data['opd_type'];
            $this->logCondition('opd_type_matches', $matchesOpdType, $patient, $data, $useMockPayment, [
                'availability_opd_type' => $availability->opd_type,
                'requested_opd_type' => $data['opd_type'] ?? null,
            ]);

            if ($availability->opd_type && $availability->opd_type !== $data['opd_type']) {
                $this->failValidation($patient, $data, $useMockPayment, 'opd_type_matches', 'opd_type', 'OPD type does not match the selected availability.');
            }
        }

        $requestedDate = Carbon::parse($data['appointment_date'])->startOfDay();

        $availabilityService = app(DoctorAvailabilityService::class);
        $isRecurringTemplate = $availabilityService->isRecurringTemplate($availability);

        if ($isRecurringTemplate) {
            $recurringStart = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->startOfDay() : null;
            $recurringEnd = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->startOfDay() : null;

            $this->logCondition('recurring_date_after_start', ! ($recurringStart && $requestedDate->lt($recurringStart)), $patient, $data, $useMockPayment, [
                'recurring_start_date' => $recurringStart?->toDateString(),
                'requested_date' => $requestedDate->toDateString(),
            ]);

            if ($recurringStart && $requestedDate->lt($recurringStart)) {
                $this->failValidation($patient, $data, $useMockPayment, 'recurring_date_after_start', 'appointment_date', 'Appointment date cannot be before the recurring availability start date (' . $recurringStart->format('Y-m-d') . ').');
            }

            $this->logCondition('recurring_date_before_end', ! ($recurringEnd && $requestedDate->gt($recurringEnd)), $patient, $data, $useMockPayment, [
                'recurring_end_date' => $recurringEnd?->toDateString(),
                'requested_date' => $requestedDate->toDateString(),
            ]);

            if ($recurringEnd && $requestedDate->gt($recurringEnd)) {
                $this->failValidation($patient, $data, $useMockPayment, 'recurring_date_before_end', 'appointment_date', 'Appointment date is after the recurring availability end date (' . $recurringEnd->format('Y-m-d') . ').');
            }

            $targetDayOfWeek = $availabilityService->recurringDayOfWeek($availability, $recurringStart ?: $requestedDate);
            $matchesDayOfWeek = ! $targetDayOfWeek || strtolower($requestedDate->format('l')) === strtolower($targetDayOfWeek);
            $this->logCondition('recurring_day_of_week_matches', $matchesDayOfWeek, $patient, $data, $useMockPayment, [
                'target_day_of_week' => $targetDayOfWeek,
                'requested_day_of_week' => $requestedDate->format('l'),
            ]);
            if (! $matchesDayOfWeek) {
                $this->failValidation($patient, $data, $useMockPayment, 'recurring_day_of_week_matches', 'appointment_date', "This slot is only available on {$targetDayOfWeek}s. The selected date is a " . $requestedDate->format('l') . '.');
            }

        } else {
            $availabilityDate = $availability->date ? Carbon::parse($availability->date)->startOfDay() : null;

            $this->logCondition('fixed_date_matches', ! ($availabilityDate && ! $requestedDate->equalTo($availabilityDate)), $patient, $data, $useMockPayment, [
                'availability_date' => $availabilityDate?->toDateString(),
                'requested_date' => $requestedDate->toDateString(),
            ]);

            if ($availabilityDate && ! $requestedDate->equalTo($availabilityDate)) {
                $this->failValidation($patient, $data, $useMockPayment, 'fixed_date_matches', 'appointment_date', 'Appointment date does not match the selected availability date.');
            }
        }

        $effectiveSlot = $availabilityService->effectiveValuesForDate($availability, $requestedDate);

        if (in_array($effectiveSlot['status'], ['blocked', 'cancelled'], true)) {
            $this->failValidation($patient, $data, $useMockPayment, 'availability_is_active', 'availability_id', 'This time slot is blocked for the selected date.');
        }

        $appointmentTime = Carbon::parse($data['appointment_time'])->format('H:i:s');
        $startTime = $effectiveSlot['start_time'] ? Carbon::parse($effectiveSlot['start_time'])->format('H:i:s') : null;
        $endTime = $effectiveSlot['end_time'] ? Carbon::parse($effectiveSlot['end_time'])->format('H:i:s') : null;
        $effectiveCapacity = (int) ($effectiveSlot['capacity'] ?? 1);
        $effectiveFee = (float) ($effectiveSlot['consultation_fee'] ?? 0);
        $override = $effectiveSlot['override'];

        $isTimeWithinSlot = ! ($startTime && $endTime && ($appointmentTime < $startTime || $appointmentTime > $endTime));
        $this->logCondition('appointment_time_within_slot', $isTimeWithinSlot, $patient, $data, $useMockPayment, [
            'requested_time' => $appointmentTime,
            'slot_start_time' => $startTime,
            'slot_end_time' => $endTime,
        ]);

        if (! $isTimeWithinSlot) {
            $this->failValidation($patient, $data, $useMockPayment, 'appointment_time_within_slot', 'appointment_time', 'Appointment time must be within the selected availability slot.');
        }


        $notes = null;
        if (! empty($data['visit_reason'])) {
            $notes = ['visit_reason' => $data['visit_reason']];
        }

        $lockKey = 'booking_slot_' . $availability->id . '_' . $requestedDate->toDateString();
        $lock = Cache::lock($lockKey, 30);

        try {
            Log::info('Registration booking waiting for slot lock', array_merge(
                $this->buildContext($patient, $data, $useMockPayment),
                ['lock_key' => $lockKey]
            ));
            $lock->block(10);

            return DB::transaction(function () use ($requestedDate, $availability, $data, $patient, $doctor, $useMockPayment, $notes, $effectiveCapacity, $effectiveFee, $override, $startTime, $endTime) {
                $paymentOrderResult = null;
                $existingCount = app(SlotCapacityService::class)->bookedCount(
                    doctorId: $doctor->id,
                    date: $requestedDate,
                    startTime: $startTime,
                    availabilityId: $availability->id,
                    consultationType: $data['consultation_type'],
                );

                Log::info('Registration booking slot capacity evaluated', array_merge(
                    $this->buildContext($patient, $data, $useMockPayment),
                    [
                        'existing_count' => $existingCount,
                        'capacity' => $effectiveCapacity,
                    ]
                ));

                if ($existingCount >= $effectiveCapacity) {
                    $this->failValidation($patient, $data, $useMockPayment, 'slot_capacity_available', 'availability_id', 'This time slot is fully booked. Please select another slot.', [
                        'existing_count' => $existingCount,
                        'capacity' => $effectiveCapacity,
                    ]);
                }

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

                Log::info('Registration booking existing appointment lookup completed', array_merge(
                    $this->buildContext($patient, $data, $useMockPayment),
                    [
                        'existing_appointment_found' => (bool) $appointment,
                        'existing_appointment_id' => $appointment?->id,
                        'existing_appointment_status' => $appointment?->status,
                    ]
                ));

                if ($appointment) {
                    $hasPaidPayment = $appointment->payment()
                        ->where('status', PaymentStatus::PAID->value)
                        ->exists();

                    Log::info('Registration booking existing appointment payment check', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        [
                            'existing_appointment_id' => $appointment->id,
                            'has_paid_payment' => $hasPaidPayment,
                        ]
                    ));

                    if ($appointment->status === AppointmentStatus::CONFIRMED->value || $hasPaidPayment) {
                        $this->failValidation($patient, $data, $useMockPayment, 'existing_appointment_is_rebookable', 'availability_id', 'This appointment is already booked.', [
                            'existing_appointment_id' => $appointment->id,
                            'existing_appointment_status' => $appointment->status,
                            'has_paid_payment' => $hasPaidPayment,
                        ]);
                    }

                    $appointment->created_at = now();
                    $appointment->save();

                    if ($useMockPayment) {
                        Log::info('Registration booking using mock payment for existing appointment', array_merge(
                            $this->buildContext($patient, $data, $useMockPayment),
                            ['appointment_id' => $appointment->id]
                        ));
                        $payment = $this->markAppointmentAsMockPaid($appointment);
                        $appointment->load(['doctor.user', 'patient.user', 'payment', 'availability', 'doctor.departments']);

                        return [
                            'appointment' => $appointment,
                            'payment' => $payment,
                            'payment_order' => null,
                        ];
                    }

                    Log::info('Registration booking creating Razorpay order for existing appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        ['appointment_id' => $appointment->id]
                    ));
                    $paymentOrderResult = $this->paymentService->createPaymentForAppointment($appointment);
                    Log::info('Registration booking received payment order for existing appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        [
                            'appointment_id' => $appointment->id,
                            'payment_order' => $this->summarizePaymentOrder($paymentOrderResult),
                        ]
                    ));

                    $appointment->load('payment');

                    return [
                        'appointment' => $appointment,
                        'payment' => $appointment->payment,
                        'payment_order' => $paymentOrderResult,
                    ];
                }

                $consultationFee = $effectiveFee;
                Log::info('Registration booking creating new appointment', array_merge(
                    $this->buildContext($patient, $data, $useMockPayment),
                    [
                        'consultation_fee' => $consultationFee,
                        'slot_start_time' => $startTime,
                        'slot_end_time' => $endTime,
                    ]
                ));
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
                    'visit_reason' => $notes,
                    'fee_amount' => $consultationFee,
                    'slug' => Str::slug($doctor->first_name . '-' . $patient->id . '-' . time()),
                ]);

                $payment = null;

                if ($consultationFee <= 0) {
                    Log::info('Registration booking confirming zero-fee appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        ['appointment_id' => $appointment->id]
                    ));
                    $appointment->update(['status' => AppointmentStatus::CONFIRMED->value]);
                    $appointment->assignQueueNumber();
                    NotificationService::notifyAppointmentConfirmed($appointment);

                    if ($appointment->consultation_type === 'video') {
                        CreateVideoRoomJob::dispatch($appointment->id);
                    }
                } elseif ($useMockPayment) {
                    Log::info('Registration booking using mock payment for new appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        ['appointment_id' => $appointment->id]
                    ));
                    $payment = $this->markAppointmentAsMockPaid($appointment);
                } else {
                    Log::info('Registration booking creating Razorpay order for new appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        [
                            'appointment_id' => $appointment->id,
                            'consultation_fee' => $consultationFee,
                        ]
                    ));
                    $paymentOrderResult = $this->paymentService->createPaymentForAppointment($appointment);
                    Log::info('Registration booking received payment order for new appointment', array_merge(
                        $this->buildContext($patient, $data, $useMockPayment),
                        [
                            'appointment_id' => $appointment->id,
                            'payment_order' => $this->summarizePaymentOrder($paymentOrderResult),
                        ]
                    ));
                }

                $appointment->load(['doctor.user', 'patient.user', 'payment']);
                $payment ??= $appointment->payment;

                return [
                    'appointment' => $appointment,
                    'payment' => $payment,
                    'payment_order' => $paymentOrderResult,
                ];
            });

        } catch (ValidationException $e) {
            Log::warning('Registration booking validation failed', [
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'] ?? null,
                'availability_id' => $data['availability_id'] ?? null,
                'appointment_date' => $data['appointment_date'] ?? null,
                'consultation_type' => $data['consultation_type'] ?? null,
                'use_mock_payment' => $useMockPayment,
                'validation_errors' => $e->errors(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);

            throw $e;
        } catch (LockTimeoutException $e) {
            Log::warning('Registration booking lock timeout', [
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'] ?? null,
                'availability_id' => $data['availability_id'] ?? null,
                'appointment_date' => $data['appointment_date'] ?? null,
                'consultation_type' => $data['consultation_type'] ?? null,
                'use_mock_payment' => $useMockPayment,
                'error' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);

            throw ValidationException::withMessages(['availability_id' => ['Booking is taking longer than expected. Please try again.']]);
        } catch (\Throwable $e) {
            Log::error('Registration booking failed', [
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'] ?? null,
                'availability_id' => $data['availability_id'] ?? null,
                'appointment_date' => $data['appointment_date'] ?? null,
                'consultation_type' => $data['consultation_type'] ?? null,
                'use_mock_payment' => $useMockPayment,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            optional($lock)->release();
        }
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
                    'source' => 'registration_complete',
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

    protected function finalizeSuccessfulPayment(Appointment $appointment, Payment $payment): void
    {
        if (! AppointmentStatus::equals($appointment->status, AppointmentStatus::CONFIRMED)) {
            $appointment->update([
                'status' => AppointmentStatus::CONFIRMED->value,
            ]);

            $appointment->assignQueueNumber();

            NotificationService::notifyAppointmentConfirmed($appointment);
        }

        GenerateReceiptJob::dispatch($payment->id);
        CreateVideoRoomJob::dispatch($appointment->id);
        SendBookingEmailJob::dispatch($appointment->id, $payment->id)->delay(now()->addSeconds(10));
    }

    protected function logCondition(
        string $condition,
        bool $passed,
        Patient $patient,
        array $data,
        bool $useMockPayment,
        array $extra = []
    ): void {
        Log::info('Registration booking condition evaluated', array_merge(
            $this->buildContext($patient, $data, $useMockPayment),
            $extra,
            [
                'condition' => $condition,
                'passed' => $passed,
            ]
        ));
    }

    protected function failValidation(
        Patient $patient,
        array $data,
        bool $useMockPayment,
        string $condition,
        string $field,
        string $message,
        array $extra = []
    ): never {
        Log::warning('Registration booking condition failed', array_merge(
            $this->buildContext($patient, $data, $useMockPayment),
            $extra,
            [
                'condition' => $condition,
                'field' => $field,
                'message' => $message,
            ]
        ));

        throw ValidationException::withMessages([$field => [$message]]);
    }

    protected function buildContext(Patient $patient, array $data, bool $useMockPayment): array
    {
        return [
            'patient_id' => $patient->id,
            'doctor_id' => $data['doctor_id'] ?? null,
            'availability_id' => $data['availability_id'] ?? null,
            'appointment_date' => $data['appointment_date'] ?? null,
            'appointment_time' => $data['appointment_time'] ?? null,
            'consultation_type' => $data['consultation_type'] ?? null,
            'opd_type' => $data['opd_type'] ?? null,
            'payment_mode' => $data['payment_mode'] ?? null,
            'use_mock_payment' => $useMockPayment,
        ];
    }

    protected function summarizePaymentOrder(?array $paymentOrderResult): array
    {
        return [
            'has_payment_order' => (bool) $paymentOrderResult,
            'order_id' => $paymentOrderResult['order']['id'] ?? null,
            'amount_paise' => $paymentOrderResult['amount_paise'] ?? null,
            'amount_rupees' => $paymentOrderResult['amount_rupees'] ?? null,
            'has_key_id' => ! empty($paymentOrderResult['key_id']),
        ];
    }
}
