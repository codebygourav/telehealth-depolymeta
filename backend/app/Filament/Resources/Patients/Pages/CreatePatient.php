<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Enums\AuthStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Patients\PatientResource;
use App\Mail\PatientRegistrationCompleteMail;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RegistrationBookingService;
use App\Services\SettingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePatient extends CreateRecord
{
    protected string $view = 'filament.resources.patients.pages.create-patient';

    public ?array $autoCheckout = null;
    protected ?User $createdUser = null;
    protected ?Patient $createdPatient = null;
    protected ?\App\Models\Appointment $createdAppointment = null;
    protected ?string $createdPassword = null;
    protected ?string $passwordNote = null;
    protected ?string $redirectToOnlinePaymentUrl = null;
    protected ?array $onlineCheckoutPayload = null;
    protected ?string $onlineCheckoutSkipReason = null;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected static string $resource = PatientResource::class;

    public function mount(): void
    {
        parent::mount();

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = 'app';
        $data['create_user_account'] = true;
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        Log::info('CreatePatient handleRecordCreation started', [
            'payload' => $this->maskSensitiveFormData($data),
        ]);

        try {
            return DB::transaction(function () use ($data) {
                Log::info('CreatePatient transaction entered', [
                    'registration_email' => $data['registration_email'] ?? $data['email'] ?? null,
                    'book_appointment' => (bool) ($data['book_appointment'] ?? false),
                    'payment_mode' => $data['payment_mode'] ?? null,
                ]);

            $email = trim((string) ($data['registration_email'] ?? $data['user_email'] ?? $data['email'] ?? ''));
                Log::info('CreatePatient resolved email', [
                    'email' => $email,
                ]);

            if ($email === '') {
                Log::warning('CreatePatient missing patient email');
                throw ValidationException::withMessages(['registration_email' => ['Patient email is required.']]);
            }
            $existingUser = User::where('email', $email)->first();
            if ($existingUser?->patient()->exists()) {
                Log::warning('CreatePatient email already linked to patient profile', [
                    'email' => $email,
                    'user_id' => $existingUser->id,
                ]);
                throw ValidationException::withMessages([
                    'registration_email' => ['This email already has a patient profile.'],
                ]);
            }

            $registration = Registration::where('email', $email)->first();
            $status = $registration?->status instanceof AuthStatus ? $registration->status->value : $registration?->status;
                Log::info('CreatePatient registration lookup result', [
                    'email' => $email,
                    'registration_found' => (bool) $registration,
                    'registration_id' => $registration?->id,
                    'status' => $status,
                    'email_verified' => $registration?->email_verified,
                ]);
            if (! $registration) {
                throw ValidationException::withMessages([
                    'registration_email' => ['Registration record not found. Please send OTP first.'],
                ]);
            }
            if ($status !== AuthStatus::verified->value) {
                $enteredOtp = trim((string) ($data['email_otp'] ?? ''));
                    Log::info('CreatePatient entering OTP verification branch', [
                        'email' => $email,
                        'status' => $status,
                        'has_email_otp' => $enteredOtp !== '',
                    ]);

                if ($enteredOtp === '') {
                    Log::warning('CreatePatient missing OTP for unverified registration', [
                        'email' => $email,
                        'status' => $status,
                    ]);
                    throw ValidationException::withMessages([
                        'email_otp' => ['Enter OTP to continue registration.'],
                    ]);
                }

                $otpValid = app(OtpService::class)->verifyOtp($email, $enteredOtp, 'registration');
                    Log::info('CreatePatient OTP verification completed', [
                        'email' => $email,
                        'otp_valid' => $otpValid,
                    ]);
                if (! $otpValid) {
                    throw ValidationException::withMessages([
                        'email_otp' => ['Invalid or expired OTP. Please resend and try again.'],
                    ]);
                }

                $registration->update([
                    'email_verified' => true,
                    'status' => AuthStatus::verified->value,
                ]);
                app(OtpService::class)->deleteOtp($email, 'registration');
                $status = AuthStatus::verified->value;
                    Log::info('CreatePatient registration status updated after OTP verification', [
                        'registration_id' => $registration->id,
                        'status' => $status,
                    ]);
            }

            if ($status !== AuthStatus::verified->value) {
                Log::warning('CreatePatient registration not verified after checks', [
                    'email' => $email,
                    'status' => $status,
                ]);
                throw ValidationException::withMessages([
                    'email_otp' => ['Email verification is required before creating patient registration.'],
                ]);
            }

            $plainPassword = (string) ($data['user_password'] ?? '');
            if (! $existingUser && strlen($plainPassword) < 8) {
                Log::warning('CreatePatient password too short for new user', [
                    'email' => $email,
                    'password_length' => strlen($plainPassword),
                ]);
                throw ValidationException::withMessages(['user_password' => ['Password must be at least 8 characters.']]);
            }

            if ($existingUser) {
                $user = $existingUser;

                $user->forceFill([
                    'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    'phone' => $data['mobile_no'] ?? ($user->phone ?? null),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'status' => AuthStatus::registered->value,
                ]);

                if ($plainPassword !== '') {
                    $user->password = Hash::make($plainPassword);
                    $this->createdPassword = $plainPassword;
                }

                $user->save();
                if (! $user->hasRole('patient')) {
                    $user->assignRole('patient');
                }

                Log::info('CreatePatient reused existing user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'password_updated' => $plainPassword !== '',
                ]);
            } else {
                $this->createdPassword = $plainPassword;

                $user = User::create([
                    'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    'slug' => Str::slug(($data['first_name'] ?? '') . '-' . ($data['last_name'] ?? '') . '-' . Str::random(6)),
                    'email' => $email,
                    'phone' => $data['mobile_no'] ?? null,
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                    'status' => AuthStatus::registered->value,
                    'avatar' => $data['avatar'] ?? null,
                ]);
                $user->assignRole('patient');
                Log::info('CreatePatient user created', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            $data['user_id'] = $user->id;
            $data['user_email'] = $email;
            $data['email'] = $email;

            $bookAppointment = (bool) ($data['book_appointment'] ?? false);
            $paymentMode = (string) ($data['payment_mode'] ?? 'cash');
            $cashTransactionId = (string) ($data['cash_transaction_id'] ?? '');
            $bookingPayload = [
                'doctor_id' => $data['doctor_id'] ?? null,
                'availability_id' => $data['availability_id'] ?? null,
                'appointment_date' => $data['appointment_date'] ?? null,
                'appointment_time' => $data['appointment_time'] ?? null,
                'consultation_type' => $data['consultation_type'] ?? null,
                'opd_type' => $data['opd_type'] ?? null,
                'visit_reason' => $data['visit_reason'] ?? null,
                // Not used by RegistrationBookingService::book, but useful for debugging.
                'payment_mode' => $paymentMode,
            ];
                Log::info('CreatePatient booking intent resolved', [
                    'book_appointment' => $bookAppointment,
                    'payment_mode' => $paymentMode,
                    'has_cash_transaction_id' => $cashTransactionId !== '',
                    'booking_payload' => $bookingPayload,
                ]);

            unset(
                $data['registration_email'],
                $data['email_otp'],
                $data['email_verified_flag'],
                $data['payment_mode'],
                $data['cash_transaction_id'],
                $data['book_appointment'],
                $data['department_id'],
                $data['doctor_id'],
                $data['availability_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['consultation_type'],
                $data['opd_type'],
                $data['visit_reason'],
                $data['user_password'],
                $data['user_email']
            );

            /** @var Patient $patient */
            $patient = $this->getModel()::create($data);
                Log::info('CreatePatient patient created', [
                    'patient_id' => $patient->id,
                    'user_id' => $user->id,
                ]);

            $registration->update(['status' => AuthStatus::registered->value]);
                Log::info('CreatePatient registration marked registered', [
                    'registration_id' => $registration->id,
                    'status' => AuthStatus::registered->value,
                ]);

            $this->createdUser = $user;
            $this->createdPatient = $patient;
            $this->passwordNote = $this->createdPassword
                ? 'Use the password provided by the clinic admin during registration. For security, change it after first login.'
                : 'Use your existing account password to login.';

            if ($bookAppointment) {

                $isCash = $paymentMode != 'online';
                $useMockPayment = $isCash || SettingService::isAppointmentMockPaymentEnabled();
                Log::info('Patient create booking branch debug', [
                    'patient_id' => $patient->id,
                    'book_appointment' => $bookAppointment,
                    'payment_mode' => $paymentMode,
                    'is_cash' => $isCash,
                    'use_mock_payment' => $useMockPayment,
                    'booking_payload' => $bookingPayload,
                ]);
                try {
                    $booking = app(RegistrationBookingService::class)->book($patient, $bookingPayload, $useMockPayment);
                } catch (ValidationException $e) {
                    $messages = collect($e->errors())->flatten()->filter()->values()->all();

                    Notification::make()
                        ->title('Appointment booking failed')
                        ->body($this->formatValidationErrorNotificationBody($messages))
                        ->danger()
                        ->persistent()
                        ->send();

                    Log::warning('Patient create booking validation exception surfaced to UI', [
                        'patient_id' => $patient->id,
                        'booking_payload' => $bookingPayload,
                        'validation_errors' => $e->errors(),
                    ]);

                    throw $e;
                }
                $this->createdAppointment = $booking['appointment'] ?? null;
                if ($isCash) {
                    Log::info('Patient create entered cash branch', [
                        'patient_id' => $patient->id,
                        'has_payment' => isset($booking['payment']),
                    ]);
                    $payment = $booking['payment'];
                    $transactionId = $cashTransactionId !== '' ? $cashTransactionId : ('cash_txn_' . strtoupper(Str::random(10)));
                    $payment->update([
                        'payment_method' => 'cash',
                        'status' => PaymentStatus::PAID->value,
                        'captured' => true,
                        'transaction_id' => $transactionId,
                        'notes' => array_merge($payment->notes ?? [], [
                            'source' => 'admin_patient_create',
                            'cash_transaction_id' => $transactionId,
                        ]),
                    ]);
                } else {
                    Log::info('Patient create entered online branch', [
                        'patient_id' => $patient->id,
                    ]);
                    $payment = $booking['payment'] ?? null;
                    $paymentOrder = $booking['payment_order'] ?? null;
                    $isMockPayment = (string) ($payment?->payment_method ?? '') === 'mock';
                    $orderId = $paymentOrder['order']['id'] ?? null;
                    $amountPaise = (int) ($paymentOrder['amount_paise'] ?? 0);
                    $keyId = $paymentOrder['key_id'] ?? SettingService::getRazorpayKey();

                    if (! $isMockPayment && $this->createdAppointment && $orderId && $amountPaise > 0 && $keyId) {
                        $this->onlineCheckoutPayload = [
                            'appointment_id' => $this->createdAppointment->id,
                            'verify_url' => '/api/v2/test-verify-payment',
                            'redirect_url' => URL::to(PatientResource::getUrl('view', ['record' => $patient])),
                            'payment' => [
                                'order_id' => $orderId,
                                'amount_paise' => $amountPaise,
                                'razorpay_key_id' => $keyId,
                            ],
                        ];
                        Log::info('Patient create online checkout payload prepared', [
                            'patient_id' => $patient->id,
                            'appointment_id' => $this->createdAppointment->id,
                            'order_id' => $orderId,
                            'amount_paise' => $amountPaise,
                            'verify_url' => '/api/v2/test-verify-payment',
                        ]);
                        $this->redirectToOnlinePaymentUrl = URL::to(PatientResource::getUrl('create') . '?' . http_build_query([
                            'patient_id' => $patient->id,
                            'appointment_id' => $this->createdAppointment->id,
                            'order_id' => $orderId,
                            'amount_paise' => $amountPaise,
                            'key_id' => $keyId,
                            'verify_url' => '/api/v2/test-verify-payment',
                            'open_checkout' => 1,
                        ]));
                    } else {
                        $this->onlineCheckoutSkipReason = $isMockPayment
                            ? 'mock_payment_enabled'
                            : (! $paymentOrder
                                ? 'payment_order_not_created'
                                : (! $orderId
                                    ? 'missing_order_id'
                                    : (($amountPaise <= 0)
                                        ? 'invalid_amount'
                                        : (! $keyId
                                            ? 'missing_razorpay_key'
                                            : 'unknown_reason'))));

                        Log::info('Patient create skipped online checkout payload', [
                            'patient_id' => $patient->id,
                            'appointment_id' => $this->createdAppointment?->id,
                            'skip_reason' => $this->onlineCheckoutSkipReason,
                            'is_mock_payment' => $isMockPayment,
                            'has_payment_order' => (bool) $paymentOrder,
                            'order_id' => $orderId,
                            'amount_paise' => $amountPaise,
                            'has_key_id' => (bool) $keyId,
                        ]);
                    }
                }
            }


                Log::info('CreatePatient handleRecordCreation completed', [
                    'patient_id' => $patient->id,
                    'user_id' => $user->id,
                    'appointment_id' => $this->createdAppointment?->id,
                    'has_online_checkout_payload' => (bool) $this->onlineCheckoutPayload,
                    'redirect_to_online_payment_url' => $this->redirectToOnlinePaymentUrl,
                ]);

            return $patient;
            });
        } catch (ValidationException $e) {
            Log::warning('CreatePatient handleRecordCreation validation exception', [
                'errors' => $e->errors(),
                'payload' => $this->maskSensitiveFormData($data),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('CreatePatient handleRecordCreation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => $this->maskSensitiveFormData($data),
            ]);

            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        if (! $this->createdUser) {
            return;
        }

        try {
            if ($this->onlineCheckoutPayload) {
                session()->flash('razorpay_checkout_payload', $this->onlineCheckoutPayload);
                Log::info('Patient create checkout payload flashed to session', [
                    'appointment_id' => $this->onlineCheckoutPayload['appointment_id'] ?? null,
                    'order_id' => $this->onlineCheckoutPayload['payment']['order_id'] ?? null,
                ]);
                Notification::make()
                    ->title('Patient created. Opening Razorpay checkout.')
                    ->body('If popup is blocked, use the "Open Payment Checkout" button.')
                    ->success()
                    ->persistent()
                    ->send();
            } elseif ($this->createdAppointment && (($this->data['payment_mode'] ?? null) === 'online') && SettingService::isAppointmentMockPaymentEnabled()) {
                Notification::make()
                    ->title('Appointment booked successfully')
                    ->body('Mock payment mode is enabled, so the appointment has been booked successfully without opening Razorpay checkout.')
                    ->success()
                    ->persistent()
                    ->send();
            } elseif ($this->createdAppointment && (($this->data['payment_mode'] ?? null) === 'online')) {
                Notification::make()
                    ->title('Online checkout payload not prepared')
                    ->body('Reason: ' . ($this->onlineCheckoutSkipReason ?? 'unknown'))
                    ->warning()
                    ->persistent()
                    ->send();
            }

            $appointmentSummary = null;
            if ($this->createdAppointment) {
                $this->createdAppointment->loadMissing(['doctor', 'availability', 'payment']);
                $appointment = $this->createdAppointment;
                $payment = $appointment->payment;
                $paymentLabel = 'No payment required';
                if ($payment) {
                    $status = $payment->status instanceof PaymentStatus ? $payment->status->value : $payment->status;
                    $paymentLabel = ucfirst((string) $status);
                    if ($status === PaymentStatus::PENDING->value && $payment->razorpay_order_id) {
                        $paymentLabel = 'Pending - complete online payment';
                    }
                }
                $appointmentSummary = [
                    'Doctor' => $appointment->doctor
                        ? 'Dr. ' . trim(($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''))
                        : null,
                    'Date' => $appointment->appointment_date ? Carbon::parse($appointment->appointment_date)->format('l, d M Y') : null,
                    'Time window' => ($appointment->availability && $appointment->availability->start_time && $appointment->availability->end_time)
                        ? Carbon::parse($appointment->availability->start_time)->format('g:i A') . ' - ' . Carbon::parse($appointment->availability->end_time)->format('g:i A')
                        : null,
                    'Consultation type' => $appointment->consultation_type === 'video' ? 'Online (video)' : 'In-person',
                    'Payment' => $paymentLabel,
                ];
            }

            Mail::to($this->createdUser->email)->send(new PatientRegistrationCompleteMail(
                patientName: $this->createdUser->name,
                email: $this->createdUser->email,
                passwordNote: $this->passwordNote ?? 'Use the password shared by your clinic.',
                actualPassword: $this->createdPassword,
                appointmentSummary: $appointmentSummary,
            ));

        } catch (\Throwable $e) {
            Log::warning('Patient registration complete email failed: ' . $e->getMessage());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->redirectToOnlinePaymentUrl ?? parent::getRedirectUrl();
    }

    protected function formatValidationErrorNotificationBody(array $messages): string
    {
        $visibleMessages = array_slice($messages, 0, 5);
        $body = implode("\n", $visibleMessages);

        if (count($messages) > 5) {
            $body .= "\n...and " . (count($messages) - 5) . ' more issue(s).';
        }

        return $body !== '' ? $body : 'Validation failed while preparing the appointment booking.';
    }

    protected function maskSensitiveFormData(array $data): array
    {
        $masked = $data;

        foreach (['user_password', 'email_otp'] as $key) {
            if (array_key_exists($key, $masked)) {
                $masked[$key] = filled($masked[$key]) ? '[REDACTED]' : $masked[$key];
            }
        }

        return $masked;
    }
}
