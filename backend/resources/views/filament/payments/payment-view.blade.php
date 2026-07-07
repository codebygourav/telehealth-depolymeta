@php
    use App\Enums\AppointmentStatus;
    use App\Enums\PaymentStatus;

    $payment = $entry->getRecord();
    $appointment = $payment->appointment;
    $patient = $appointment?->patient;
    $doctor = $appointment?->doctor;

    $value = fn($item) => filled($item) ? $item : 'N/A';
    $formatLabel = fn($item) => filled($item)
        ? str($item)
            ->replace(['_', '-'], ' ')
            ->title()
        : 'N/A';

    $paymentStatus =
        $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::tryFrom((string) $payment->status);
    $paymentStatusValue = $paymentStatus?->value ?? (string) $payment->status;
    $paymentStatusLabel = $paymentStatus?->label() ?? $formatLabel($paymentStatusValue);
    $paymentStatusClasses = [
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'failed' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'refunded' => 'bg-gray-100 text-gray-800 ring-gray-200',
    ];

    $appointmentStatus =
        $appointment?->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::tryFrom((string) $appointment?->status);
    $appointmentStatusValue = $appointmentStatus?->value ?? (string) $appointment?->status;
    $appointmentStatusLabel = $appointmentStatus?->label() ?? $formatLabel($appointmentStatusValue);
    $appointmentStatusClasses = [
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'confirmed' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'completed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'rescheduled' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
        'cancelled' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'failed' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'no_show' => 'bg-gray-100 text-gray-800 ring-gray-200',
    ];

    $patientName = trim(($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? '')) ?: 'N/A';
    $doctorName =
        trim(($doctor?->first_name ?? '') . ' ' . ($doctor?->last_name ?? '')) ?: $doctor?->user?->name ?? 'N/A';
    $patientEmail = $payment->email ?: ($patient?->email ?: $patient?->user?->email);
    $patientPhone = $payment->contact ?: ($patient?->mobile_no ?: $patient?->user?->phone);

    $appointmentDate = $appointment?->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date) : null;
    $appointmentTime = $appointment?->appointment_time
        ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A')
        : null;
    $appointmentEndTime = $appointment?->appointment_end_time
        ? \Carbon\Carbon::parse($appointment->appointment_end_time)->format('g:i A')
        : null;
    $timeRange = collect([$appointmentTime, $appointmentEndTime])->filter()->implode(' - ');

    $paymentIdentifier = $payment->razorpay_payment_id ?: $payment->transaction_id ?: $payment->id;
    $receiptDoc = $payment->moduleDocuments()->where('name', 'receipt_pdf')->first();
    $receiptFile = is_array($receiptDoc?->files ?? null) ? ($receiptDoc->files[0] ?? null) : null;
    $receiptUrl = $receiptFile ? storage_url($receiptFile) : null;

    $paymentDetails = [
        ['label' => 'Amount', 'value' => '₹' . number_format((float) $payment->amount, 2)],
        ['label' => 'Payment Status', 'value' => $paymentStatusLabel],
        ['label' => 'Payment Method', 'value' => $formatLabel($payment->payment_method)],
        ['label' => 'Captured', 'value' => $payment->captured === null ? null : ($payment->captured ? 'Yes' : 'No')],
        ['label' => 'Payment Created On', 'value' => $payment->created_at?->format('d M Y, g:i A')],
        ['label' => 'Gateway Created On', 'value' => $payment->razorpay_created_at?->format('d M Y, g:i A')],
    ];

    $patientDetails = [
        ['label' => 'Patient ID', 'value' => $patient?->existing_patient_id ?: 'New patient'],
        ['label' => 'Phone Number', 'value' => $patientPhone],
        ['label' => 'Email', 'value' => $patientEmail],
        [
            'label' => 'Age / Gender',
            'value' => trim(
                ($patient?->age ? $patient->age . ' years' : 'N/A') .
                    ' / ' .
                    $formatLabel($patient?->gender instanceof \BackedEnum ? $patient->gender->value : $patient?->gender),
            ),
        ],
    ];

    $appointmentDetails = [
        ['label' => 'Appointment ID', 'value' => $appointment?->id],
        [
            'label' => 'OPD Visit Date',
            'value' => trim(($appointmentDate?->format('d M Y') ?? 'N/A') . ($timeRange ? ' · ' . $timeRange : '')),
        ],
        ['label' => 'Appointment Mode', 'value' => $formatLabel($appointment?->consultation_type)],
        ['label' => 'Appointment Status', 'value' => $appointment ? $appointmentStatusLabel : null],
        ['label' => 'Booking Created On', 'value' => $appointment?->created_at?->format('d M Y, g:i A')],
        ['label' => 'Fee Amount', 'value' => $appointment?->fee_amount ? '₹' . number_format((float) $appointment->fee_amount, 2) : null],
    ];

    $doctorDetails = [
        ['label' => 'Doctor', 'value' => $doctorName !== 'N/A' ? $doctorName : null],
        ['label' => 'Email', 'value' => $doctor?->user?->email],
        ['label' => 'Phone Number', 'value' => $doctor?->user?->phone],
        ['label' => 'Experience', 'value' => $doctor?->years_experience ? $doctor->years_experience . ' years' : null],
    ];

    $gatewayDetails = [
        ['label' => 'Payment ID / Voucher ID', 'value' => $paymentIdentifier],
        ['label' => 'Razorpay Order ID', 'value' => $payment->razorpay_order_id],
        ['label' => 'Razorpay Payment ID', 'value' => $payment->razorpay_payment_id],
        ['label' => 'Transaction ID', 'value' => $payment->transaction_id],
        ['label' => 'Invoice ID', 'value' => $payment->invoice_id],
        ['label' => 'Bank', 'value' => $payment->bank],
        ['label' => 'UPI ID', 'value' => $payment->vpa],
        ['label' => 'Wallet', 'value' => $payment->wallet],
        ['label' => 'Refund Status', 'value' => $formatLabel($payment->refund_status)],
        ['label' => 'Amount Refunded', 'value' => $payment->amount_refunded ? '₹' . number_format((float) $payment->amount_refunded, 2) : null],
        ['label' => 'Fee', 'value' => $payment->fee ? '₹' . number_format((float) $payment->fee, 2) : null],
        ['label' => 'Tax', 'value' => $payment->tax ? '₹' . number_format((float) $payment->tax, 2) : null],
    ];

    $failureDetails = collect([
        ['label' => 'Error Code', 'value' => $payment->error_code],
        ['label' => 'Description', 'value' => $payment->error_description],
        ['label' => 'Source', 'value' => $payment->error_source],
        ['label' => 'Step', 'value' => $payment->error_step],
        ['label' => 'Reason', 'value' => $payment->error_reason],
    ])->filter(fn($detail) => filled($detail['value']))->values();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="p-4 sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Payment Overview</p>
                        <h1 class="mt-1 break-words text-2xl font-bold text-gray-950">
                            ₹{{ number_format((float) $payment->amount, 2) }}
                        </h1>
                        <p class="mt-1 break-all text-sm font-medium text-gray-600">
                            {{ $value($paymentIdentifier) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold ring-1 {{ $paymentStatusClasses[$paymentStatusValue] ?? 'bg-gray-100 text-gray-800 ring-gray-200' }}">
                            {{ $paymentStatusLabel }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800 ring-1 ring-gray-200">
                            {{ $formatLabel($payment->payment_method) }}
                        </span>
                        @if ($appointment)
                            <span
                                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold ring-1 {{ $appointmentStatusClasses[$appointmentStatusValue] ?? 'bg-gray-100 text-gray-800 ring-gray-200' }}">
                                {{ $appointmentStatusLabel }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-gray-950">Payment Details</h2>
                    <p class="mt-1 text-sm text-gray-500">Transaction amount, status, method, and capture details.</p>
                </div>
                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        @foreach ($paymentDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950 sm:text-right">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-gray-950">Patient Details</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $patientName }}</p>
                </div>
                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        @foreach ($patientDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950 sm:text-right">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-4 py-4 sm:px-6">
                    <div>
                        <h2 class="text-base font-bold text-gray-950">Appointment Details</h2>
                        <p class="mt-1 text-sm text-gray-500">OPD visit and booking information linked to this payment.</p>
                    </div>
                    @if ($appointment)
                        <x-filament::button color="primary" outlined size="sm" icon="heroicon-o-arrow-top-right-on-square"
                            tag="a"
                            href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $appointment->slug ?: $appointment->id]) }}">
                            Open
                        </x-filament::button>
                    @endif
                </div>
                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        @foreach ($appointmentDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950 sm:text-right">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-gray-950">Doctor Details</h2>
                    <p class="mt-1 text-sm text-gray-500">Consulting doctor for the linked appointment.</p>
                </div>
                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        @foreach ($doctorDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950 sm:text-right">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-base font-bold text-gray-950">Gateway Details</h2>
                    <p class="mt-1 text-sm text-gray-500">Razorpay IDs, refund fields, and gateway charges.</p>
                </div>
                @if ($receiptUrl)
                    <x-filament::button color="primary" outlined size="sm" icon="heroicon-o-arrow-down-tray"
                        tag="a" href="{{ $receiptUrl }}" target="_blank">
                        Receipt
                    </x-filament::button>
                @endif
            </div>
            <div class="grid gap-x-8 px-4 py-2 sm:px-6 lg:grid-cols-2">
                @foreach ($gatewayDetails as $detail)
                    <div class="grid gap-1 border-b border-gray-100 py-3 sm:grid-cols-[170px_1fr]">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $detail['label'] }}</dt>
                        <dd class="break-all text-sm font-semibold text-gray-950 lg:text-right">
                            {{ $value($detail['value']) }}</dd>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($failureDetails->isNotEmpty())
            <section class="rounded-lg border border-rose-200 bg-white shadow-sm">
                <div class="border-b border-rose-100 px-4 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-rose-950">Failure Details</h2>
                    <p class="mt-1 text-sm text-rose-700">Gateway error information recorded for this payment.</p>
                </div>
                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-rose-100">
                        @foreach ($failureDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-rose-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-rose-950 sm:text-right">
                                    {{ $detail['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endif
    </div>
</x-dynamic-component>
