<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Payment Core Card --}}
        <div
            class="md:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 p-3 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                        <x-heroicon-s-credit-card class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            ₹{{ number_format($getState()->amount, 2) }}</h2>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mt-0.5">
                            Total Transaction Amount</p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <x-filament::badge :color="match ($getState()->status?->value ?? strtolower($getState()->status)) {
                        'paid', 'captured', 'success' => 'success',
                        'pending', 'created' => 'warning',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    }" size="lg">
                        {{ $getState()->status instanceof \App\Enums\PaymentStatus ? $getState()->status->label() : ucfirst($getState()->status) }}
                    </x-filament::badge>
                </div>
            </div>

            <div
                class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 py-5 border-t border-b border-gray-100 dark:border-gray-700">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Method</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white uppercase">
                        {{ $getState()->payment_method ?? '—' }}
                    </p>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Transaction ID</p>
                    <p class="font-mono text-sm font-semibold text-gray-900 dark:text-white break-all">
                        {{ $getState()->transaction_id ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Created</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $getState()->created_at ? \Carbon\Carbon::parse($getState()->created_at)->format('d M, Y H:i') : '—' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Gateway
                    IDs</p>
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Razorpay Order ID</span>
                    <span
                        class="font-mono text-xs font-medium text-gray-900 dark:text-white break-all">{{ $getState()->razorpay_order_id ?: '—' }}</span>
                </div>
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Razorpay Payment ID</span>
                    <span
                        class="font-mono text-xs font-medium text-gray-900 dark:text-white break-all">{{ $getState()->razorpay_payment_id ?: '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Contact Info Card --}}
        @php
            $payment = $getState();
            $email =
                $payment->email ?:
                ($payment->appointment?->patient?->email ?:
                $payment->appointment?->patient?->user?->email);
            $phone =
                $payment->contact ?:
                ($payment->appointment?->patient?->mobile_no ?:
                $payment->appointment?->patient?->user?->phone);
        @endphp
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-28 h-28 -mr-6 -mt-6 bg-primary-50 dark:bg-primary-900/10 rounded-full opacity-40 flex items-center justify-center pointer-events-none"
                aria-hidden="true">
                <x-heroicon-o-identification class="w-14 h-14 text-primary-200 dark:text-primary-700" />
            </div>

            <h3
                class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 relative z-10">
                Billing Contact</h3>

            <div class="space-y-4 relative z-10">
                <div
                    class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-heroicon-m-envelope class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Email</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $email ?: '—' }}</p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-heroicon-m-phone class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Phone</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $phone ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Relationships Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Related Appointment --}}
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div
                class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">Linked Appointment</span>
                <x-heroicon-o-link class="w-4 h-4 text-gray-400 dark:text-gray-500 flex-shrink-0" />
            </div>
            @if ($getState()->appointment)
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-5">
                        <div
                            class="flex-shrink-0 h-12 w-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                            <x-heroicon-o-calendar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white">Appt
                                #{{ substr($getState()->appointment_id ?? '', 0, 8) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $getState()->appointment->appointment_date ? \Carbon\Carbon::parse($getState()->appointment->appointment_date)->format('l, d M Y') : '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div
                            class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $getState()->appointment->status?->label() ?? '—' }}</p>
                        </div>
                        <div
                            class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white uppercase">
                                {{ $getState()->appointment->type ?? 'Standard' }}</p>
                        </div>
                    </div>
                    <x-filament::button color="primary" outlined size="sm"
                        icon="heroicon-o-arrow-top-right-on-square" tag="a"
                        href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $getState()->appointment?->slug ?? $getState()->appointment_id]) }}"
                        class="w-full">
                        Open Appointment Profile
                    </x-filament::button>
                </div>
            @else
                <div class="p-10 text-center">
                    <x-heroicon-o-calendar class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">No appointment linked</p>
                </div>
            @endif
        </div>

        {{-- Parties Involved --}}
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div
                class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">Transaction Parties</span>
                <x-heroicon-o-user-group class="w-4 h-4 text-gray-400 dark:text-gray-500 flex-shrink-0" />
            </div>
            @php
                $patient = $getState()->appointment?->patient;
                $doctor = $getState()->appointment?->doctor;
                $patientName = $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null;
                $doctorName = $doctor?->user?->name ? 'Dr. ' . $doctor->user->name : null;
            @endphp
            <div class="p-6 space-y-4">
                {{-- Patient --}}
                <div
                    class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <img src="{{ storage_url($patient?->avatar) }}" alt="{{ $patientName ?: 'Patient' }}"
                        class="h-10 w-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 flex-shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient
                            / Payer</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $patientName ?: '—' }}</p>
                    </div>
                </div>

                {{-- Doctor --}}
                <div
                    class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <img src="{{ storage_url($doctor?->avatar) }}" alt="{{ $doctorName ?: 'Doctor' }}"
                        class="h-10 w-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 flex-shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Recipient / Doctor</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $doctorName ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
