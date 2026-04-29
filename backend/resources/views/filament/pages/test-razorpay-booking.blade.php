<x-filament-panels::page>
    <div class="w-full mx-auto space-y-6">
        <div class="overflow-hidden border border-indigo-100 shadow-sm rounded-2xl bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50">
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

        <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <h3 class="mb-4 text-sm font-semibold tracking-wide text-gray-500 uppercase">Booking Form</h3>
            {{ $this->form }}
        </section>

        @if ($this->autoCheckout && !empty($this->autoCheckout['payment']['order_id']))
            <section class="p-5 border border-emerald-200 shadow-sm rounded-2xl bg-emerald-50/70">
                <h3 class="mb-2 text-base font-semibold text-emerald-900">Continue Online Payment</h3>
                <p class="mb-4 text-sm text-emerald-800">
                    Appointment is created from patient registration. Click below to open Razorpay checkout.
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-credit-card"
                        x-on:click='window.openRazorpayCheckout({
                            appointment_id: @js($this->autoCheckout["appointment_id"]),
                            verify_url: "/api/v2/test-verify-payment",
                            payment: {
                                order_id: @js($this->autoCheckout["payment"]["order_id"]),
                                amount_paise: @js($this->autoCheckout["payment"]["amount_paise"]),
                                razorpay_key_id: @js($this->autoCheckout["payment"]["razorpay_key_id"])
                            }
                        })'
                    >
                        Pay Now
                    </x-filament::button>
                    <span class="text-xs text-emerald-900">
                        Order: {{ $this->autoCheckout['payment']['order_id'] }}
                    </span>
                </div>
            </section>
        @endif

        <!-- Availability Details Section -->
        @if ($this->availabilityDetails)
            <section class="p-5 border border-blue-100 shadow-sm rounded-2xl bg-blue-50/70">
                <h3 class="mb-4 text-base font-semibold text-blue-950">Selected Availability Details</h3>
                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Slot Type</span>
                        <p class="mt-1 text-blue-900">
                            {{ !empty($this->availabilityDetails['is_recurring']) ? 'Recurring' : 'One-time' }}
                        </p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Date/Pattern</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['date'] }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Time</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['start_time'] }} -
                            {{ $this->availabilityDetails['end_time'] }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Consultation Fee</span>
                        <p class="mt-1 font-bold text-blue-900">
                            ₹{{ number_format($this->availabilityDetails['consultation_fee'], 2) }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Type</span>
                        <p class="mt-1 text-blue-900">{{ ucfirst($this->availabilityDetails['consultation_type']) }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Capacity</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['capacity'] }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Status</span>
                        <p class="mt-1 text-blue-900">
                            @if ($this->availabilityDetails['is_available'])
                                <span class="font-medium text-green-600">Available</span>
                            @else
                                <span class="font-medium text-red-600">Not Available</span>
                            @endif
                        </p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Doctor</span>
                        <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['doctor_name'] }}</p>
                    </div>
                    <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                        <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Availability ID</span>
                        <p class="mt-1 font-mono text-xs text-blue-900 break-all">{{ $this->availabilityDetails['id'] }}</p>
                    </div>
                    @if (!empty($this->availabilityDetails['is_recurring']))
                        <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                            <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Recurring From</span>
                            <p class="mt-1 text-blue-900">{{ $this->availabilityDetails['recurring_start_date'] ?? 'N/A' }}</p>
                        </div>
                        <div class="p-3 border border-blue-100 rounded-xl bg-white/80">
                            <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Recurring Until</span>
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
            <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Booking Result</h2>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Status:</span>
                        @if ($this->result['status'] === 'success')
                            <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">
                                Success
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">
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
                        <h3 class="mb-2 text-sm font-semibold tracking-wide text-gray-500 uppercase">Response Data</h3>
                        <div class="p-4 overflow-x-auto border border-gray-200 rounded-xl bg-gray-50">
                            <pre class="text-sm">{{ json_encode($this->result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>

                    <details class="mt-4">
                        <summary class="text-sm font-medium text-gray-700 cursor-pointer hover:text-gray-900">
                            View Raw Response
                        </summary>
                        <div class="p-4 mt-2 overflow-x-auto border border-gray-200 rounded-xl bg-gray-50">
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
                        <div class="p-5 mt-4 border border-green-200 rounded-2xl bg-green-50">
                            <h4 class="mb-3 text-base font-semibold text-green-900">Appointment & Payment Details</h4>
                            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                    <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Appointment ID</span>
                                    <p class="mt-1 font-mono text-xs text-green-900">
                                        {{ $apiAppointment['id'] ?? 'N/A' }}</p>
                                </div>
                                <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                    <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Appointment Slug</span>
                                    <p class="mt-1 font-mono text-xs text-green-900">
                                        {{ $apiAppointment['slug'] ?? 'N/A' }}</p>
                                </div>
                                <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                    <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Appointment Date</span>
                                    <p class="mt-1 text-green-900">
                                        {{ $apiAppointment['date'] ?? 'N/A' }}</p>
                                </div>
                                <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                    <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Appointment Time</span>
                                    <p class="mt-1 text-green-900">
                                        {{ $apiAppointment['time'] ?? 'N/A' }}</p>
                                </div>
                                <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                    <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Appointment Status</span>
                                    <p class="mt-1 font-bold text-green-900">
                                        {{ isset($apiAppointment['status']) ? ucfirst($apiAppointment['status']) : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            @if ($hasPayment)
                                <div class="pt-4 mt-5 border-t border-green-300">
                                    <h5 class="mb-3 text-sm font-semibold tracking-wide text-green-900 uppercase">Razorpay Payment Information</h5>
                                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                        <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                            <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Payment Status</span>
                                            <p class="mt-1 font-bold text-green-900">
                                                {{ isset($apiPayment['status']) ? ucfirst($apiPayment['status']) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                            <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Razorpay Order ID</span>
                                            <p class="mt-1 font-mono text-xs text-green-900 break-all">
                                                {{ $apiPayment['order_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                            <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Razorpay Payment ID</span>
                                            <p class="mt-1 font-mono text-xs text-green-900 break-all">
                                                {{ $apiPayment['payment_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                            <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Amount</span>
                                            <p class="mt-1 font-bold text-green-900">
                                                ₹{{ isset($apiPayment['amount']) ? number_format((float) $apiPayment['amount'], 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="p-3 border border-green-200 rounded-xl bg-white/60">
                                            <span class="text-xs font-semibold tracking-wide text-green-700 uppercase">Payment Mode</span>
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

                                        <div class="p-4 mt-4 border border-blue-200 rounded-lg bg-blue-50">
                                            <h5 class="mb-3 text-sm font-semibold tracking-wide text-blue-900 uppercase">Complete Payment</h5>

                                            <button
                                                type="button"
                                                id="razorpay-pay-button"
                                                data-payment='@json($razorpayPayment)'
                                                data-appointment-id="{{ $apiAppointment['id'] }}"
                                                onclick="openRazorpayCheckout({ payment: JSON.parse(this.dataset.payment), appointment_id: this.dataset.appointmentId, verify_url: '/api/v2/test-verify-payment' })"
                                                class="px-4 py-2 text-white transition bg-blue-600 rounded hover:bg-blue-700">
                                                Pay ₹{{ number_format($razorpayPayment['amount_rupees'], 2) }}
                                            </button>

                                            <p class="mt-2 text-xs text-blue-700">
                                                Click the button above to open Razorpay payment popup.
                                            </p>
                                        </div>
                                    @else
                                        <div class="p-3 mt-4 text-sm border rounded-lg border-amber-200 bg-amber-50 text-amber-800">
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

        <section class="p-5 border border-blue-100 shadow-sm rounded-2xl bg-blue-50/70">
            <h3 class="mb-3 text-base font-semibold text-blue-950">Instructions</h3>
            <ul class="grid gap-2 pl-4 text-sm leading-6 text-blue-900 list-disc md:grid-cols-2">
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
