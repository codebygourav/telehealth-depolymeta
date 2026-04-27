<x-filament-panels::page>
    @push('scripts')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            function openRazorpay(payment, appointmentId) {
                console.log("Opening Razorpay with:", payment, appointmentId);

                // Prioritize the key from env if passed or fallback
                var razorpayKey = payment.razorpay_key_id || "{{ config('services.razorpay.key_id', env('RAZORPAY_KEY_ID')) }}";

                if (!payment.order_id) {
                    alert("Missing Razorpay order_id. Payment might fail.");
                }

                var options = {
                    key: razorpayKey,
                    amount: payment.amount_paise,
                    currency: "INR",
                    name: "CMC Telehealth",
                    description: "Appointment Booking",
                    order_id: payment.order_id,

                handler: function(response) {
                        fetch("/api/v2/test-verify-payment", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "Accept": "application/json"
                                },
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    appointment_id: appointmentId
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                console.log(response);
                                console.log(data);
                                alert(JSON.stringify(data, null, 2));
                            })
                            .catch(() => alert("Payment verification failed"));
                    }
                };

                new Razorpay(options).open();
            }
        </script>
    @endpush

    <div class="mx-auto w-full  space-y-6">
        <div class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50 shadow-sm">
            <div class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">Admin Appointment Booking Desk</h2>
                    <p class="max-w-3xl text-sm leading-6 text-gray-700">
                        Use this screen to create bookings on behalf of doctors and patients. Select an availability slot first;
                        recurring slots automatically load all valid appointment dates.
                    </p>
                </div>
                <div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $this->paymentMode === 'Mock' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                        Payment Mode: {{ $this->paymentMode }}
                    </span>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Booking Form</h3>
            {{ $this->form }}
        </section>

        <!-- Availability Details Section -->
        @if ($this->availabilityDetails)
            <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-blue-950">Selected Availability Details</h3>
                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Slot Type</span>
                        <p class="mt-1 text-blue-900">
                            {{ !empty($this->availabilityDetails['is_recurring']) ? 'Recurring' : 'One-time' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Date/Pattern</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['date'] }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Time</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['start_time'] }} -
                            {{ $this->availabilityDetails['end_time'] }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Consultation Fee</span>
                        <p class="mt-1 font-bold text-blue-900">
                            ₹{{ number_format($this->availabilityDetails['consultation_fee'], 2) }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Type</span>
                        <p class="mt-1 text-blue-900">{{ ucfirst($this->availabilityDetails['consultation_type']) }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Capacity</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['capacity'] }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Status</span>
                        <p class="mt-1 text-blue-900">
                            @if ($this->availabilityDetails['is_available'])
                                <span class="text-green-600 font-medium">Available</span>
                            @else
                                <span class="text-red-600 font-medium">Not Available</span>
                            @endif
                        </p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Doctor</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['doctor_name'] }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Availability ID</span>
                        <p class="mt-1 break-all font-mono text-xs text-blue-900">{{ $this->availabilityDetails['id'] }}</p>
                    </div>
                    @if (!empty($this->availabilityDetails['is_recurring']))
                        <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Recurring From</span>
                            <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['recurring_start_date'] ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white/80 p-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">Recurring Until</span>
                            <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['recurring_end_date'] ?? 'N/A' }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <div class="flex flex-col justify-end gap-3 sm:flex-row">
            @if ($this->showResult)
                <x-filament::button wire:click="clearResult" color="gray" icon="heroicon-o-x-mark">
                    Clear Result
                </x-filament::button>
            @endif

            <x-filament::button wire:click="testBooking"
                wire:confirm="This will create a real appointment booking. Are you sure you want to proceed?"
                color="primary" icon="heroicon-o-play">
                Create Appointment
            </x-filament::button>
        </div>

        @if ($this->showResult && $this->result)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Booking Result</h2>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Status:</span>
                        @if ($this->result['status'] === 'success')
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                Success
                            </span>
                        @else
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                Error
                            </span>
                        @endif
                        <span class="text-sm text-gray-500">
                            (HTTP {{ $this->result['status_code'] ?? 'N/A' }})
                        </span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Response Data</h3>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <pre class="text-sm">{{ json_encode($this->result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>

                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                            View Raw Response
                        </summary>
                        <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <pre class="text-xs text-gray-600">{{ $this->result['raw_response'] }}</pre>
                        </div>
                    </details>

                    @php
                        // Set variables for API data (protect against missing keys)
                        $hasAppointment = isset($this->result['response']['data']['appointment']);
                        $hasPayment = isset($this->result['response']['data']['payment']);
                        $apiAppointment = $hasAppointment ? $this->result['response']['data']['appointment'] : [];
                        $apiPayment = $hasPayment ? $this->result['response']['data']['payment'] : [];
                        $paymentOrderId = $apiPayment['order_id'] ?? null;
                        // Use amount_rupees if amount is not present
                        $paymentAmount = $apiPayment['amount'] ?? ($apiPayment['amount_rupees'] ?? null);
                        $razorpayKeyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));
                        $isMockPayment = (bool) ($apiPayment['mock_payment'] ?? false);
                    @endphp

                    @if ($this->result['status'] === 'success' && $hasAppointment)
                        <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 p-5">
                            <h4 class="mb-3 text-base font-semibold text-green-900">Appointment & Payment Details</h4>
                            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Appointment ID</span>
                                    <p class="mt-1 font-mono text-xs text-green-900">
                                        {{ $apiAppointment['id'] ?? 'N/A' }}</p>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Appointment Slug</span>
                                    <p class="mt-1 font-mono text-xs text-green-900">
                                        {{ $apiAppointment['slug'] ?? 'N/A' }}</p>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Appointment Date</span>
                                    <p class="mt-1 text-green-900">
                                        {{ $apiAppointment['date'] ?? 'N/A' }}</p>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Appointment Time</span>
                                    <p class="mt-1 text-green-900">
                                        {{ $apiAppointment['time'] ?? 'N/A' }}</p>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Appointment Status</span>
                                    <p class="mt-1 font-bold text-green-900">
                                        {{ isset($apiAppointment['status']) ? ucfirst($apiAppointment['status']) : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            @if ($hasPayment)
                                <div class="mt-5 border-t border-green-300 pt-4">
                                    <h5 class="mb-3 text-sm font-semibold uppercase tracking-wide text-green-900">Razorpay Payment Information</h5>
                                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                        <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Payment Status</span>
                                            <p class="mt-1 font-bold text-green-900">
                                                {{ isset($apiPayment['status']) ? ucfirst($apiPayment['status']) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Razorpay Order ID</span>
                                            <p class="mt-1 break-all font-mono text-xs text-green-900">
                                                {{ $apiPayment['order_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Razorpay Payment ID</span>
                                            <p class="mt-1 break-all font-mono text-xs text-green-900">
                                                {{ $apiPayment['payment_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Amount</span>
                                            <p class="mt-1 font-bold text-green-900">
                                                ₹{{ isset($apiPayment['amount']) ? number_format((float) $apiPayment['amount'], 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="rounded-xl border border-green-200 bg-white/60 p-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-green-700">Payment Mode</span>
                                            <p class="mt-1 font-bold text-green-900">
                                                {{ $isMockPayment ? 'Mock Mode' : 'Razorpay Live/Test Order' }}
                                            </p>
                                        </div>
                                    </div>

                                    @php
                                        $canShowPayBtn = !$isMockPayment && $paymentOrderId && $paymentAmount && $razorpayKeyId;
                                    @endphp
                                    @if ($canShowPayBtn)
                                        @php
                                            $razorpayPayment = [
                                                'order_id' => $paymentOrderId,
                                                'razorpay_key_id' => $razorpayKeyId,
                                                'amount_paise' => $apiPayment['amount_paise'] ?? (int) round((float) $paymentAmount * 100),
                                                'amount_rupees' => (float) $paymentAmount,
                                            ];
                                        @endphp

                                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <h5 class="mb-3 text-sm font-semibold uppercase tracking-wide text-blue-900">Complete Payment</h5>

                                            <button
                                                type="button"
                                                id="razorpay-pay-button"
                                                data-payment='@json($razorpayPayment)'
                                                data-appointment-id="{{ $apiAppointment['id'] }}"
                                                onclick="openRazorpay(JSON.parse(this.dataset.payment), this.dataset.appointmentId)"
                                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                Pay ₹{{ number_format($razorpayPayment['amount_rupees'], 2) }}
                                            </button>

                                            <p class="text-xs text-blue-700 mt-2">
                                                Click the button above to open Razorpay payment popup.
                                            </p>
                                        </div>
                                    @else
                                        <div class="mt-4 p-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
                                            @if ($isMockPayment)
                                                Mock payment mode is active. Razorpay checkout button is hidden for admin flow.
                                            @else
                                                Razorpay order details are not available. Payment button is hidden.
                                            @endif
                                        </div>
                                    @endif


                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5 shadow-sm">
            <h3 class="mb-3 text-base font-semibold text-blue-950">Instructions</h3>
            <ul class="grid list-disc gap-2 pl-4 text-sm leading-6 text-blue-900 md:grid-cols-2">
                <li>Select a patient from the dropdown</li>
                <li>Select a doctor - this will populate available slots</li>
                <li>Choose an availability slot for the selected doctor</li>
                <li>For recurring slots, all valid dates are auto-listed in Appointment Date</li>
                <li>Appointment time is auto-filled from the selected availability</li>
                <li>Select consultation type (In-Person or Video)</li>
                <li>Click "Create Appointment" to create booking from admin side</li>
                <li>In mock mode, Razorpay button is intentionally hidden</li>
            </ul>
        </section>
    </div>
</x-filament-panels::page>
