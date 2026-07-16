<x-filament-panels::page>
    <div class="admin-booking-page w-full mx-auto space-y-6">
        <div class="border border-gray-200 bg-white shadow-sm rounded-lg">
            <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-950">Book Appointment</h2>
                    <p class="mt-1 max-w-4xl text-sm leading-6 text-gray-600">
                        Select the patient, doctor, appointment date, and available time slot from one admin screen.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $this->paymentMode === 'Mock' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                        Payment Mode: {{ $this->paymentMode }}
                    </span>
                </div>
            </div>
        </div>

        <div class="booking-form-shell">
            {{ $this->form }}
        </div>

        <?php if ($this->autoCheckout && !empty($this->autoCheckout['payment']['order_id'])) {
            $autoCheckoutPayload = [
                'appointment_id' => $this->autoCheckout['appointment_id'],
                'verify_url' => '/api/v2/test-verify-payment',
                'payment' => [
                    'order_id' => $this->autoCheckout['payment']['order_id'],
                    'amount_paise' => $this->autoCheckout['payment']['amount_paise'],
                    'razorpay_key_id' => $this->autoCheckout['payment']['razorpay_key_id'],
                ],
            ];
            $autoCheckoutPayloadJson = htmlspecialchars(
                json_encode($autoCheckoutPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
                ENT_QUOTES,
                'UTF-8'
            );
        ?>
        <section class="p-5 border border-emerald-200 shadow-sm rounded-lg bg-emerald-50/70">
            <h3 class="mb-2 text-base font-semibold text-emerald-900">Continue Payment</h3>
            <p class="mb-4 text-sm text-emerald-800">
                Appointment is created. Click below to open Razorpay checkout.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" data-checkout-payload="<?php echo $autoCheckoutPayloadJson; ?>"
                    onclick="window.openRazorpayCheckout(JSON.parse(this.dataset.checkoutPayload))"
                    class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2">
                    <x-heroicon-o-credit-card class="h-5 w-5" />
                    Pay Now
                </button>
                <span class="text-xs text-emerald-900">
                    Order: {{ $this->autoCheckout['payment']['order_id'] }}
                </span>
            </div>
        </section>
        <?php } ?>

        <?php if ($this->availabilityDetails) { ?>
        <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-lg">
            <div class="flex flex-col gap-1 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-base font-semibold text-gray-950">Selected Slot</h3>
                <span
                    class="inline-flex w-fit rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 ring-1 ring-green-200">
                    Ready to book
                </span>
            </div>
            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-4">
                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Doctor</span>
                    <p class="mt-1 font-medium text-gray-900">{{ $this->availabilityDetails['doctor_name'] }}</p>
                </div>
                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Date</span>
                    <p class="mt-1 font-medium text-gray-900">{{ $this->availabilityDetails['date'] }}</p>
                </div>
                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Time</span>
                    <p class="mt-1 font-medium text-gray-900">{{ $this->availabilityDetails['start_time'] }} -
                        {{ $this->availabilityDetails['end_time'] }}</p>
                </div>
                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Fee</span>
                    <p class="mt-1 font-semibold text-gray-900">
                        ₹{{ number_format($this->availabilityDetails['consultation_fee'], 2) }}</p>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 ring-1 ring-gray-200">
                    {{ !empty($this->availabilityDetails['is_recurring']) ? 'Recurring slot' : 'One-time slot' }}
                </span>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700 ring-1 ring-blue-200">
                    {{ ucfirst($this->availabilityDetails['consultation_type']) }}
                </span>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 ring-1 ring-gray-200">
                    Capacity {{ $this->availabilityDetails['capacity'] }}
                </span>
                <?php if ($this->availabilityDetails['is_available']) { ?>
                <span class="rounded-full bg-green-50 px-3 py-1 text-green-700 ring-1 ring-green-200">Available</span>
                <?php } else { ?>
                <span class="rounded-full bg-red-50 px-3 py-1 text-red-700 ring-1 ring-red-200">Not
                    Available</span>
                <?php } ?>
                <?php if (!empty($this->availabilityDetails['recurring_start_date'])) { ?>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 ring-1 ring-gray-200">
                    From {{ $this->availabilityDetails['recurring_start_date'] }}
                </span>
                <?php } ?>
                <?php if (!empty($this->availabilityDetails['recurring_end_date'])) { ?>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 ring-1 ring-gray-200">
                    Until {{ $this->availabilityDetails['recurring_end_date'] }}
                </span>
                <?php } ?>
            </div>
        </section>
        <?php } ?>

        <div
            class="sticky bottom-0 z-10 -mx-1 border-t border-gray-200 bg-gray-50/95 px-1 py-3 backdrop-blur sm:static sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-0">
            <div class="flex flex-col justify-end gap-3 sm:flex-row">
                <?php if ($this->showResult) { ?>
                    <?php if ($this->result && $this->result['status'] === 'success') { ?>
                    <x-filament::button wire:click="closeResultModal" color="gray" icon="heroicon-o-check">
                        Done
                    </x-filament::button>
                    <?php } else { ?>
                    <x-filament::button wire:click="clearResult" color="gray" icon="heroicon-o-x-mark">
                        Clear Result
                    </x-filament::button>
                    <?php } ?>
                <?php } ?>

                <?php if (!$this->showResult || ($this->result && $this->result['status'] !== 'success')) { ?>
                <x-filament::button wire:click="confirmBooking" color="primary" icon="heroicon-o-calendar-days">
                    {{ $this->isRescheduleMode() ? 'Reschedule Appointment' : 'Book Appointment' }}
                </x-filament::button>
                <?php } ?>
            </div>
        </div>

        <?php if ($this->showBookingConfirmation) { ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 px-4"
            style="background: #00000075;">
            <div class="w-full max-w-md rounded-lg bg-white shadow-xl ring-1 ring-gray-950/10">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-950">
                        {{ $this->isRescheduleMode() ? 'Confirm Appointment Reschedule' : 'Confirm Appointment Booking' }}
                    </h3>
                    <p class="mt-1 text-sm leading-6 text-gray-600">
                        {{ $this->isRescheduleMode() ? 'This will move the selected existing appointment to the new slot.' : 'This will create a real appointment booking for the selected patient and slot.' }}
                    </p>
                </div>
                <div class="px-5 py-4">
                    <?php if ($this->availabilityDetails) { ?>
                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Doctor</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $this->availabilityDetails['doctor_name'] }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $this->availabilityDetails['date'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Time</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($this->availabilityDetails['start_time'])->format('h:i A') }}
                                - {{ \Carbon\Carbon::parse($this->availabilityDetails['end_time'])->format('h:i A') }}

                            </dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fee</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                ₹{{ number_format($this->availabilityDetails['consultation_fee'], 2) }}
                            </dd>
                        </div>
                    </dl>
                    <?php } ?>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 rounded-b-lg">
                    <x-filament::button wire:click="cancelBookingConfirmation" color="gray">
                        Cancel
                    </x-filament::button>
                    <x-filament::button wire:click="submitConfirmedBooking" color="primary"
                        icon="heroicon-o-calendar-days">
                        {{ $this->isRescheduleMode() ? 'Confirm Reschedule' : 'Confirm Booking' }}
                    </x-filament::button>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php if ($this->showResult && $this->result) { ?>
        <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-lg">
            <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Booking Status</h2>
                <div class="flex items-center gap-2">
                    <?php if ($this->result['status'] === 'success') { ?>
                    <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">
                        Success
                    </span>
                    <?php } else { ?>
                    <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">
                        Error
                    </span>
                    <?php } ?>
                </div>
            </div>

            <div class="space-y-4">
                <?php
                $resData = $this->result['response']['data'] ?? [];
                $appointmentId = $resData['appointment']['id'] ?? $resData['appointment_id'] ?? $resData['id'] ?? 'N/A';

                $appointmentDate = $resData['appointment']['date'] ?? $resData['date'] ?? null;
                if ($appointmentDate) {
                    $appointmentDate = \Carbon\Carbon::parse($appointmentDate)->format('D, d M Y');
                } else {
                    $appointmentDate = $resData['schedule_date'] ?? 'N/A';
                }

                $appointmentTime = $resData['appointment']['time'] ?? $resData['time'] ?? null;
                if ($appointmentTime) {
                    $appointmentTime = str_contains((string) $appointmentTime, ' - ')
                        ? $appointmentTime
                        : \Carbon\Carbon::parse($appointmentTime)->format('h:i A');
                } else {
                    $appointmentTime = $resData['schedule_time'] ?? 'N/A';
                }

                $paymentStatus = $resData['payment']['status'] ?? $resData['payment_status'] ?? 'Pending';
                $appointmentStatus = $resData['appointment']['status'] ?? $resData['appointment_status'] ?? (strtolower($paymentStatus) === 'paid' ? 'confirmed' : 'Pending');
                $isAdminWithoutPayment = $paymentStatus === 'admin_without_payment';
                $paymentStatusLabel = $isAdminWithoutPayment
                    ? 'Admin No Payment'
                    : str($paymentStatus)->replace(['_', '-'], ' ')->title()->toString();

                $paymentAmount = $resData['payment']['amount'] ?? $resData['payment']['amount_rupees'] ?? null;
                $paymentOrderId = $resData['payment']['order_id'] ?? null;
                $isMockPayment = (bool) ($resData['payment']['mock_payment'] ?? false);
                $razorpayKeyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));

                $canShowPayBtn = !$isMockPayment && $paymentOrderId && $paymentAmount && $razorpayKeyId && strtolower($paymentStatus) !== 'paid';
                ?>

                <?php if ($this->result['status'] === 'success') { ?>
                <div class="p-4 border border-green-200 rounded-lg bg-green-50">
                    <p class="text-sm font-medium text-green-900">
                        {{ $this->result['message'] ?? 'Appointment created successfully.' }}</p>
                </div>

                <div class="overflow-hidden border border-gray-200 rounded-lg">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <th class="w-56 px-4 py-3 font-medium text-left text-gray-600 bg-gray-50">
                                    Appointment ID</th>
                                <td class="px-4 py-3 font-mono text-xs text-gray-900 break-all select-all">
                                    {{ $appointmentId }}
                                </td>
                            </tr>
                            <tr>
                                <th class="px-4 py-3 font-medium text-left text-gray-600 bg-gray-50">Date</th>
                                <td class="px-4 py-3 text-gray-900">{{ $appointmentDate }}</td>
                            </tr>
                            <tr>
                                <th class="px-4 py-3 font-medium text-left text-gray-600 bg-gray-50">Time</th>
                                <td class="px-4 py-3 text-gray-900">{{ $appointmentTime }}</td>
                            </tr>
                            <tr>
                                <th class="px-4 py-3 font-medium text-left text-gray-600 bg-gray-50">Status</th>
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ ucfirst($appointmentStatus) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 mt-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Payment
                                Status</span>
                            <p class="mt-1 font-semibold {{ strtolower($paymentStatus) === 'paid' || $isAdminWithoutPayment ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $paymentStatusLabel }}
                            </p>
                        </div>
                        <?php if ($paymentAmount !== null) { ?>
                        <div>
                            <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Amount</span>
                            <p class="mt-1 font-semibold text-gray-900">
                                ₹{{ number_format((float) $paymentAmount, 2) }}
                            </p>
                        </div>
                        <?php } ?>
                        <div>
                            <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Mode</span>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ $isAdminWithoutPayment ? 'Admin No Payment' : ($isMockPayment ? 'Mock Mode' : 'Online Payment') }}</p>
                        </div>
                    </div>

                    <?php if ($canShowPayBtn) { ?>
                    <?php
                    $razorpayPayment = [
                        'order_id' => $paymentOrderId,
                        'razorpay_key_id' => $razorpayKeyId,
                        'amount_paise' => $resData['payment']['amount_paise'] ?? (int) round((float) $paymentAmount * 100),
                        'amount_rupees' => (float) $paymentAmount,
                    ];
                    $razorpayPaymentJson = htmlspecialchars(json_encode($razorpayPayment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    ?>

                    <div class="mt-4">
                        <button type="button" id="razorpay-pay-button" data-payment="<?php echo $razorpayPaymentJson; ?>"
                            data-appointment-id="{{ $appointmentId }}"
                            onclick="openRazorpayCheckout({ payment: JSON.parse(this.dataset.payment), appointment_id: this.dataset.appointmentId, verify_url: '/api/v2/test-verify-payment' })"
                            class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500">
                            Pay ₹{{ number_format($razorpayPayment['amount_rupees'], 2) }}
                        </button>
                    </div>
                    <?php } elseif (strtolower($paymentStatus) !== 'paid' && !$isAdminWithoutPayment) { ?>
                    <div class="p-3 mt-4 text-sm border rounded-lg border-amber-200 bg-amber-50 text-amber-800">
                        <?php if ($isMockPayment) { ?>
                        Mock payment mode is active. Razorpay checkout button is hidden.
                        <?php } else { ?>
                        Razorpay order details are not available. Payment button is hidden.
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } elseif ($this->result['status'] === 'error') { ?>
                <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                    <p class="text-sm font-medium text-red-900">
                        {{ $this->result['message'] ?? 'Unable to book this appointment. Please select another slot and try again.' }}
                    </p>
                </div>
                <?php } ?>

                <?php if ($this->canShowDebugResponse()) { ?>
                <details class="mt-4 border border-gray-200 rounded-lg overflow-hidden">
                    <summary class="text-xs font-semibold text-gray-700 bg-gray-50 px-4 py-2.5 cursor-pointer hover:bg-gray-100/80 transition select-none flex items-center justify-between">
                        <span>Developer response details</span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-500" />
                    </summary>
                    <div class="p-4 overflow-x-auto bg-gray-900 border-t border-gray-200">
                        <pre class="text-xs text-green-400 font-mono leading-relaxed">{{ json_encode($this->result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </details>
                <?php } ?>
            </div>
        </section>
        <?php } ?>
    </div>
    <style>
        .admin-booking-page .booking-form-shell>form,
        .admin-booking-page .booking-form-shell>div {
            display: grid;
            gap: 1.5rem;
        }

        .admin-booking-page .patient-type-radio {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 180px));
            gap: .75rem;
            width: fit-content;
        }

        .admin-booking-page .patient-type-radio .fi-fo-radio-label {
            min-height: 42px;
            border: 1px solid rgb(203 213 225);
            border-radius: .5rem;
            background: rgb(255 255 255);
            color: rgb(51 65 85);
            padding: .625rem .75rem;
            font-weight: 650;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .admin-booking-page .patient-type-radio .fi-fo-radio-label:hover {
            border-color: var(--app-primary-hex);
            color: var(--app-primary-hex);
        }

        .admin-booking-page .patient-type-radio .fi-fo-radio-label:has(.fi-radio-input:checked) {
            border-color: var(--app-primary-hex);
            background: var(--app-primary-hex-12);
            color: var(--app-primary-hex);
            box-shadow: inset 0 0 0 1px var(--app-primary-hex);
        }

        .admin-booking-page .patient-type-radio .fi-radio-input {
            accent-color: var(--app-primary-hex);
        }

        .admin-booking-page .patient-type-radio .fi-radio-input:checked {
            border-color: var(--app-primary-hex);
            background-color: var(--app-primary-hex);
        }

        @media (max-width: 640px) {
            .admin-booking-page .patient-type-radio {
                grid-template-columns: 1fr;
                width: 100%;
            }
        }
    </style>
    <script>
        const showBookingDebugResponse = <?php echo $this->canShowDebugResponse() ? 'true' : 'false'; ?>;

        const loadRazorpayScript = () => {

            if (window.Razorpay) {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {

                const existing = document.querySelector(
                    'script[data-razorpay-checkout="1"]'
                );

                if (existing) {

                    existing.addEventListener(
                        'load',
                        () => resolve(), {
                            once: true
                        }
                    );

                    existing.addEventListener(
                        'error',
                        () => reject(
                            new Error(
                                'Failed to load Razorpay script'
                            )
                        ), {
                            once: true
                        }
                    );

                    return;
                }

                const script =
                    document.createElement('script');

                script.src =
                    'https://checkout.razorpay.com/v1/checkout.js';

                script.setAttribute(
                    'data-razorpay-checkout',
                    '1'
                );

                script.onload = () => resolve();

                script.onerror = () =>
                    reject(
                        new Error(
                            'Failed to load Razorpay script'
                        )
                    );

                document.head.appendChild(script);
            });
        };

        window.openRazorpayCheckout =
            async function(payload) {

                console.log(
                    'OPENING RAZORPAY =>',
                    payload
                );

                if (
                    !payload ||
                    !payload.payment ||
                    !payload.payment.order_id ||
                    !payload.payment.razorpay_key_id
                ) {

                    console.warn(
                        'Invalid Razorpay payload',
                        payload
                    );

                    return;
                }

                try {

                    await loadRazorpayScript();

                } catch (error) {

                    console.error(error);
                    return;
                }

                const options = {

                    key: payload.payment.razorpay_key_id,

                    amount: Number(
                        payload.payment.amount_paise || 0
                    ),

                    currency: 'INR',

                    name: 'CMC Ludhiana Hospital',

                    description: 'Appointment Booking',

                    order_id: payload.payment.order_id,

                    handler: function(response) {

                        fetch(
                                payload.verify_url ||
                                '/api/v2/test-verify-payment', {

                                    method: 'POST',

                                    headers: {
                                        'Content-Type': 'application/json',

                                        'Accept': 'application/json'
                                    },

                                    body: JSON.stringify({

                                        appointment_id: payload.appointment_id,

                                        razorpay_order_id: response.razorpay_order_id,

                                        razorpay_payment_id: response.razorpay_payment_id,

                                        razorpay_signature: response.razorpay_signature,
                                    }),
                                }
                            )
                            .then(res => res.json())
                            .then(json => {

                                console.log(
                                    'VERIFY RESPONSE =>',
                                    json
                                );

                                if (
                                    json.data &&
                                    json.data.payment_status === 'paid'
                                ) {
                                    @this.call('handlePaymentSuccess', json);
                                } else {
                                    @this.call('handlePaymentFailure', json);
                                }
                            })
                            .catch(err => {

                                console.error(
                                    'VERIFY ERROR =>',
                                    err
                                );
                            });
                    }
                };

                try {

                    const razorpay =
                        new Razorpay(options);

                    razorpay.open();

                } catch (error) {

                    console.error(
                        'RAZORPAY OPEN ERROR =>',
                        error
                    );
                }
            };
    </script>
</x-filament-panels::page>
