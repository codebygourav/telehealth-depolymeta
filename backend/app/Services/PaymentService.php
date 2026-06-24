<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Patient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Enums\{PaymentStatus, AppointmentStatus};
use App\Jobs\{CreateVideoRoomJob, GenerateReceiptJob, SendBookingEmailJob};

class PaymentService
{
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id') ?? env('RAZORPAY_KEY_ID');
        $this->keySecret = config('services.razorpay.key_secret') ?? env('RAZORPAY_KEY_SECRET');
    }

    /**
     * ---------------------------------------------------------
     *  Normalize and save/update payment (ALL fields included)
     * ---------------------------------------------------------
     */
    public function saveOrUpdatePayment(array $src, $appointmentId = null, $rawResponse = null, $defaultMethod = 'razorpay')
    {
        $statusMap = [
            'captured'   => PaymentStatus::PAID->value,
            'authorized' => PaymentStatus::PENDING->value,
            'failed'     => PaymentStatus::FAILED->value,
            'refunded'   => PaymentStatus::REFUNDED->value,
            'created'    => PaymentStatus::PENDING->value,
        ];
        $status = $statusMap[$src['status'] ?? 'failed'] ?? PaymentStatus::FAILED->value;
        $amount = isset($src['amount']) ? ((int) $src['amount']) / 100 : 0;

        $unique = [];

        $orderId = $src['order_id'] ?? ($src['order']['id'] ?? null);

        if ($orderId) {
            $unique['razorpay_order_id'] = $orderId;
        } else {
            $unique['razorpay_payment_id'] = $src['id'];
        }

        return Payment::updateOrCreate(
            $unique,
            [
                'appointment_id'      => $appointmentId,
                'amount'              => $amount,
                'payment_method' => $src['method'] ?? $defaultMethod,
                'status'              => $status,
                'transaction_id'      => $src['id'] ?? null,
                'razorpay_payment_id' => $src['id'] ?? null,
                'razorpay_order_id'   => $src['order_id'] ?? null,
                'email'               => $src['email'] ?? null,
                'contact'             => $src['contact'] ?? null,
                'bank' => $src['bank']
                    ?? ($src['card']['issuer'] ?? null),
                'card_id'             => $src['card_id'] ?? null,
                'card_details' => $src['card'] ?? null,
                'vpa'                 => $src['vpa'] ?? null,
                'wallet'              => $src['wallet'] ?? null,
                'invoice_id'          => $src['invoice_id'] ?? null,
                'international'       => $src['international'] ?? false,
                'amount_refunded'     => ((int) ($src['amount_refunded'] ?? 0)) / 100,
                'refund_status'       => $src['refund_status'] ?? null,
                'captured'            => $src['captured'] ?? false,
                'fee'                 => ((int) ($src['fee'] ?? 0)) / 100,
                'tax'                 => ((int) ($src['tax'] ?? 0)) / 100,
                'error_code'          => $src['error_code'] ?? null,
                'error_description'   => $src['error_description'] ?? null,
                'error_source'        => $src['error_source'] ?? null,
                'error_step'          => $src['error_step'] ?? null,
                'error_reason'        => $src['error_reason'] ?? null,
                'acquirer_data'       => $src['acquirer_data'] ?? null,
                'notes'               => $src['notes'] ?? null,
                'razorpay_created_at' => isset($src['created_at']) ? date('Y-m-d H:i:s', $src['created_at']) : null,
                'full_response'       => is_string($rawResponse) ? $rawResponse : json_encode($rawResponse),
            ]
        );
    }

    /**
     * ---------------------------------------------------------
     *  Create payment order for existing appointment
     * ---------------------------------------------------------
     */
    public function createPaymentForAppointment(Appointment $appointment)
    {
        $amountInRupees = (float) ($appointment->fee_amount ?? 0);

        if ($amountInRupees <= 0 && $appointment->availability_id) {
            if (! $appointment->relationLoaded('availability')) {
                $appointment->load('availability');
            }

            if ($appointment->availability) {
                $amountInRupees = (float) ($appointment->availability->consultation_fee ?? 0);
            }
        }

        $amountInPaise = (int) round($amountInRupees * 100);

        // ---------------------------------------------------
        // ✅ Prevent duplicate Razorpay orders (SAFE VERSION)
        // ---------------------------------------------------

        $existing = Payment::where('appointment_id', $appointment->id)
            ->where('status', PaymentStatus::PENDING->value)
            ->whereNotNull('razorpay_order_id')
            ->latest()
            ->first();

        // Reuse ONLY if payment is recent (avoid stale pending orders)
        if ($existing && $existing->created_at->gt(now()->subMinutes(15))) {

            return [
                'order' => ['id' => $existing->razorpay_order_id],
                'amount_paise' => (int) round($existing->amount * 100),
                'amount_rupees' => (float) $existing->amount,
                'key_id' => $this->keyId,
            ];
        }

        // ---------------------------------------------------
        // 🚀 Create Razorpay Order
        // ---------------------------------------------------

        $order = null;

        if ($amountInPaise > 0) {

            try {

                $resp = Http::withBasicAuth($this->keyId, $this->keySecret)
                    ->withHeaders([
                        'X-Idempotency-Key' => 'order_' . $appointment->id,
                    ])
                    ->retry(3, 100, function ($exception, $request) {
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    })
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'payment_capture' => 1,
                    ]);

                if (! $resp->ok()) {
                    Log::error('Razorpay API Error', [
                        'status' => $resp->status(),
                        'body' => $resp->body(),
                        'appointment_id' => $appointment->id,
                    ]);
                    throw new \Exception('Razorpay order creation failed: ' . ($resp->json('error.description') ?? 'Unknown error'));
                }

                $order = $resp->json();
                $order['order_id'] = $order['id'];

                $paymentRecord = Payment::updateOrCreate(
                    ['appointment_id' => $appointment->id, 'status' => PaymentStatus::PENDING->value],
                    [
                        'razorpay_order_id' => $order['id'],
                        'amount'            => $amountInRupees,
                        'currency'          => 'INR',
                    ]
                );

                // Force update created_at as requested by user to reflect the new booking attempt
                $paymentRecord->created_at = now();
                $paymentRecord->save();
            } catch (\Throwable $e) {

                Log::error('Razorpay Order Error', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);

                throw new \Exception('Unable to create payment order: ' . $e->getMessage());
            }
        }

        return [
            'order' => $order,
            'amount_paise' => $amountInPaise,
            'amount_rupees' => $amountInRupees,
            'key_id' => $this->keyId,
        ];
    }


    public function createAppointmentOrder(array $data)
    {
        // Parse appointment date
        $data['appointment_date'] = Carbon::parse($data['appointment_date'])->format('Y-m-d');

        // Case: Time range "9:00 AM - 10:00 AM"
        if (preg_match('/(\d{1,2}:\d{2}\s*(AM|PM))\s*-\s*(\d{1,2}:\d{2}\s*(AM|PM))/i', $data['appointment_time'], $m)) {
            $data['start_time'] = Carbon::parse($m[1])->format('H:i:s');
            $data['end_time']   = Carbon::parse($m[3])->format('H:i:s');
            $data['appointment_time'] = $data['start_time'];
        }

        // Case: Single time like "9:00 AM"
        if (!isset($data['start_time']) && preg_match('/(\d{1,2}:\d{2}\s*(AM|PM))/i', $data['appointment_time'], $m)) {
            $data['appointment_time'] = Carbon::parse($m[1])->format('H:i:s');
        }

        // Validate patient + doctor
        $patient = Patient::findOrFail($data['patient_id']);
        $doctor  = Doctor::findOrFail($data['doctor_id']);

        // Get consultation fee from availability first, then doctor
        $amountInRupees = 0;
        if (!empty($data['availability_id'])) {
            $availability = DoctorAvailability::find($data['availability_id']);
            if ($availability) {
                $amountInRupees = (float) ($availability->consultation_fee ?? 0);
            }
        }



        $amountInPaise  = (int) round($amountInRupees * 100);

        $appointmentData = [
            'patient_id'       => $data['patient_id'],
            'doctor_id'        => $data['doctor_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'availability_id' => $data['availability_id'] ?? ($data['availability_id'] ?? null),
            'status'           => $data['status'] ?? AppointmentStatus::PENDING->value,
            'slug'             => 'appointment-' . Str::slug($patient->first_name . '-' . ($patient->last_name ?? '')) . '-' . $data['appointment_date'] . '-' . substr(uniqid(), -4),
            'consultation_type' => $data['consultation_type'] ?? 'in-person', // default
            'notes' => $data['notes'] ?? null,
            'fee_amount'       => $amountInRupees,
        ];


        $appointment = Appointment::create($appointmentData);

        // Create Razorpay order
        $order = null;
        if ($amountInPaise > 0) {
            try {
                $resp = Http::withBasicAuth($this->keyId, $this->keySecret)
                    ->withHeaders([
                        'X-Idempotency-Key' => 'order_' . $appointment->id,
                    ])
                    ->retry(3, 100, function ($exception, $request) {
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    })
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'payment_capture' => 1,
                        'notes' => [
                            'note' => 'booking appointment for this id: ' . ($appointment->id ?? ''),
                        ],
                    ]);

                if ($resp->ok()) {
                    $order = $resp->json();
                    $order['order_id'] = $order['id']; // <-- ADD THIS LINE

                    $this->saveOrUpdatePayment($order, $appointment->id, $order, 'razorpay');
                }
            } catch (\Exception $e) {
                Log::error('Razorpay order failed', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $appointment->id
                ]);
            }
        }

        return [
            'appointment'  => $appointment,
            'order'        => $order,
            'amount_paise' => $amountInPaise,
            'key_id'       => $this->keyId
        ];
    }



    public function verifyPayment(array $validated)
    {
        $resp = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->retry(3, 100, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->get("https://api.razorpay.com/v1/payments/{$validated['razorpay_payment_id']}");

        if ($resp->failed()) {
            return null;
        }

        $paymentJson = $resp->json();

        // Save payment
        $payment = $this->saveOrUpdatePayment(
            $paymentJson,
            $validated['appointment_id'],
            $paymentJson
        );

        $statusValue = $payment->status instanceof PaymentStatus
            ? $payment->status->value
            : (is_object($payment->status) ? ($payment->status->value ?? '') : (string)$payment->status);

        $isPaid = strtolower($statusValue) === 'paid';
        $isFailed = strtolower($statusValue) === 'failed';

        if ($isPaid) {
            $appointment = Appointment::find($validated['appointment_id']);
            if ($appointment) {
                $appointment->update(['status' => AppointmentStatus::CONFIRMED->value]);
                $appointment->assignQueueNumber();
            }
        }

        if ($isFailed) {
            $appointment = Appointment::find($validated['appointment_id']);
            if ($appointment) {
                $appointment->update(['status' => AppointmentStatus::FAILED->value]);
            }
        }

        return $payment;
    }


    /**
     * ---------------------------------------------------------
     *  Handle Razorpay webhook
     * ---------------------------------------------------------
     */
    public function processWebhook(array $data)
    {
        $event = $data['event'] ?? null;
        // Log::info('$event', ['event' => $event]);

        $paymentEntity = $data['payload']['payment']['entity'] ?? [];
        $orderEntity   = $data['payload']['order']['entity'] ?? [];

        $razorpayPaymentId = $paymentEntity['id'] ?? null;
        $razorpayOrderId   = $paymentEntity['order_id'] ?? ($orderEntity['id'] ?? null);

        // Fetch latest payment state from Razorpay
        $fresh = $paymentEntity;
        if ($razorpayPaymentId) {
            $resp = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->get("https://api.razorpay.com/v1/payments/{$razorpayPaymentId}");

            if ($resp->ok()) {
                $fresh = $resp->json();
            }
        }

        // Find appointment linked to this order
        $appointmentId = Payment::where('razorpay_order_id', $razorpayOrderId)
            ->value('appointment_id');

        if (! $appointmentId) {
            Log::warning('Razorpay webhook could not find appointment for order', [
                'event' => $event,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $razorpayOrderId,
            ]);

            return null;
        }

        // Save or update payment in DB
        $payment = $this->saveOrUpdatePayment($fresh, $appointmentId, $data);

        if ($appointmentId) {
            $isPaid = $payment->status === PaymentStatus::PAID
                || $payment->status === 'paid'
                || (is_object($payment->status) && isset($payment->status->value) && $payment->status->value === 'paid');

            $appointment = Appointment::with(['doctor.user', 'patient.user'])->find($appointmentId);

            if ($appointment) {
                if ($event === 'payment.captured' || $isPaid) {
                    if (! AppointmentStatus::equals($appointment->status, AppointmentStatus::CONFIRMED)) {
                        $appointment->update(['status' => AppointmentStatus::CONFIRMED->value]);
                        $appointment->assignQueueNumber();
 
                        // Send notifications as backup for webhook flow
                        NotificationService::notifyAppointmentConfirmed($appointment);
                    }

                    GenerateReceiptJob::dispatch($payment->id);
                    CreateVideoRoomJob::dispatch($appointment->id);
                    SendBookingEmailJob::dispatch($appointment->id, $payment->id)->delay(now()->addSeconds(10));
                }

                // Payment failed → Mark appointment failed
                if ($event === 'payment.failed') {
                    // Do not downgrade if already confirmed, rescheduled, or completed
                    if (!in_array($appointment->status->value ?? $appointment->status, [
                        AppointmentStatus::CONFIRMED->value,
                        AppointmentStatus::RESCHEDULED->value,
                        AppointmentStatus::COMPLETED->value
                    ])) {
                        $appointment->update(['status' => AppointmentStatus::FAILED->value]);
                    }
                }

                // Optional: authorized but not captured yet
                if ($event === 'payment.authorized') {
                    // Do not downgrade if already confirmed, rescheduled, or completed
                    if (!in_array($appointment->status->value ?? $appointment->status, [
                        AppointmentStatus::CONFIRMED->value,
                        AppointmentStatus::RESCHEDULED->value,
                        AppointmentStatus::COMPLETED->value
                    ])) {
                        $appointment->update(['status' => AppointmentStatus::PENDING->value]);
                    }
                }
            }
        }

        return $payment;
    }
}
