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

    <div class="space-y-6">
        <!-- Form Section -->
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <!-- Availability Details Section -->
        @if ($this->availabilityDetails)
            <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-3">Selected Availability Details (from Database):</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700 font-medium">Date:</span>
                        <p class="text-blue-900">{{ $this->availabilityDetails['date'] }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Time:</span>
                        <p class="text-blue-900">{{ $this->availabilityDetails['start_time'] }} -
                            {{ $this->availabilityDetails['end_time'] }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Consultation Fee:</span>
                        <p class="text-blue-900 font-bold">
                            ₹{{ number_format($this->availabilityDetails['consultation_fee'], 2) }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Type:</span>
                        <p class="text-blue-900">{{ ucfirst($this->availabilityDetails['consultation_type']) }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Capacity:</span>
                        <p class="text-blue-900">{{ $this->availabilityDetails['capacity'] }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Status:</span>
                        <p class="text-blue-900">
                            @if ($this->availabilityDetails['is_available'])
                                <span class="text-green-600 font-medium">Available</span>
                            @else
                                <span class="text-red-600 font-medium">Not Available</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Doctor:</span>
                        <p class="text-blue-900">{{ $this->availabilityDetails['doctor_name'] }}</p>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Availability ID:</span>
                        <p class="text-blue-900 text-xs">{{ $this->availabilityDetails['id'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Button -->
        <div class="flex justify-end gap-4">
            @if ($this->showResult)
                <x-filament::button wire:click="clearResult" color="gray" icon="heroicon-o-x-mark">
                    Clear Result
                </x-filament::button>
            @endif

            <x-filament::button wire:click="testBooking"
                wire:confirm="This will create a real appointment booking. Are you sure you want to proceed?"
                color="primary" icon="heroicon-o-play">
                Test Booking
            </x-filament::button>
        </div>

        <!-- Result Section -->
        @if ($this->showResult && $this->result)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Booking Result</h2>

                <div class="space-y-4">
                    <!-- Status Badge -->
                    <div class="flex items-center gap-2">
                        <span class="font-medium">Status:</span>
                        @if ($this->result['status'] === 'success')
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-xl text-sm font-medium">
                                Success
                            </span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-xl text-sm font-medium">
                                Error
                            </span>
                        @endif
                        <span class="text-gray-500 text-sm">
                            (HTTP {{ $this->result['status_code'] ?? 'N/A' }})
                        </span>
                    </div>

                    <!-- Response Data -->
                    <div>
                        <h3 class="font-medium mb-2">Response Data:</h3>
                        <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-sm">{{ json_encode($this->result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>

                    <!-- Raw Response -->
                    <details class="mt-4">
                        <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">
                            View Raw Response
                        </summary>
                        <div class="mt-2 bg-gray-50 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-xs text-gray-600">{{ $this->result['raw_response'] }}</pre>
                        </div>
                    </details>

                    <!-- Success Actions -->
                    @php
                        // Set variables for API data (protect against missing keys)
                        $hasAppointment = isset($this->result['response']['data']['appointment']);
                        $hasPayment = isset($this->result['response']['data']['payment']);
                        $apiAppointment = $hasAppointment ? $this->result['response']['data']['appointment'] : [];
                        $apiPayment = $hasPayment ? $this->result['response']['data']['payment'] : [];
                        $paymentOrderId = $apiPayment['order_id'] ?? null;
                        // Use amount_rupees if amount is not present
                        $paymentAmount = $apiPayment['amount'] ?? ($apiPayment['amount_rupees'] ?? null);
                        // Razorpay publishable key via config
                        // Razorpay publishable key - Priority to Environment/Config as requested by user
                        $razorpayKeyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));
                    @endphp

                    @if ($this->result['status'] === 'success' && $hasAppointment)
                        <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                            <h4 class="font-medium text-green-900 mb-3">Appointment & Payment Details:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-green-700 font-medium">Appointment ID:</span>
                                    <p class="text-green-900 font-mono text-xs">
                                        {{ $apiAppointment['id'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-green-700 font-medium">Appointment Slug:</span>
                                    <p class="text-green-900 font-mono text-xs">
                                        {{ $apiAppointment['slug'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-green-700 font-medium">Appointment Date:</span>
                                    <p class="text-green-900">
                                        {{ $apiAppointment['date'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-green-700 font-medium">Appointment Time:</span>
                                    <p class="text-green-900">
                                        {{ $apiAppointment['time'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-green-700 font-medium">Appointment Status:</span>
                                    <p class="text-green-900 font-bold">
                                        {{ isset($apiAppointment['status']) ? ucfirst($apiAppointment['status']) : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            @if ($hasPayment)
                                <div class="mt-4 pt-4 border-t border-green-300">
                                    <h5 class="font-medium text-green-900 mb-2">Razorpay Payment Information:</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-green-700 font-medium">Payment Status:</span>
                                            <p class="text-green-900 font-bold">
                                                {{ isset($apiPayment['status']) ? ucfirst($apiPayment['status']) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Razorpay Order ID:</span>
                                            <p class="text-green-900 font-mono text-xs break-all">
                                                {{ $apiPayment['order_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Razorpay Payment ID:</span>
                                            <p class="text-green-900 font-mono text-xs break-all">
                                                {{ $apiPayment['payment_id'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Amount:</span>
                                            <p class="text-green-900 font-bold">
                                                ₹{{ isset($apiPayment['amount']) ? number_format((float) $apiPayment['amount'], 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- The Pay Button logic (show only if payment is not already made and API has enough info) --}}
                                    @php
                                        $currentStatus = strtolower($apiPayment['status'] ?? '');
                                        // On this test page, we'll show the button even if it's already paid, to allow testing the modal
                                        $canShowPayBtn = $paymentOrderId && $paymentAmount && $razorpayKeyId;
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
                                            <h5 class="font-medium text-blue-900 mb-3">Complete Payment:</h5>

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
                                    @endif


                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Instructions -->
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Instructions:</h3>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Select a patient from the dropdown</li>
                <li>Select a doctor - this will populate available slots</li>
                <li>Choose an availability slot for the selected doctor</li>
                <li>Set the appointment date and time</li>
                <li>Select consultation type (In-Person or Video)</li>
                <li>Click "Test Booking" to test the Razorpay booking functionality</li>
                <li>The result will show the API response and payment details</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
