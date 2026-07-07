@php
    use App\Enums\AppointmentStatus;
    use Illuminate\Support\Facades\Storage;

    $appointment = $entry->getRecord();
    $patient = $appointment->patient;
    $doctor = $appointment->doctor;
    $payment = $appointment->payment;
    $videoConsultation = $appointment->videoConsultation;

    $statusEnum =
        $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::tryFrom((string) $appointment->status);
    $statusValue = $statusEnum?->value ?? (is_string($appointment->status) ? $appointment->status : 'unknown');

    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'confirmed' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'completed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'rescheduled' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
        'cancelled' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'failed' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'no_show' => 'bg-gray-100 text-gray-800 ring-gray-200',
    ];

    $value = fn($item) => filled($item) ? $item : 'N/A';
    $formatLabel = fn($item) => filled($item)
        ? str($item)
            ->replace(['_', '-'], ' ')
            ->title()
        : 'N/A';
    $patientAttributes = $patient?->getAttributes() ?? [];
    $patientField = fn(string $key) => $patientAttributes[$key] ?? null;
    $patientName = trim(($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? '')) ?: 'N/A';
    $doctorName =
        trim(($doctor?->first_name ?? '') . ' ' . ($doctor?->last_name ?? '')) ?: $doctor?->user?->name ?? 'N/A';
    $patientEmail = $patient?->email ?: $patient?->user?->email;
    $patientPhone = $patient?->mobile_no ?: $patient?->user?->phone;
    $patientUnitId = $patientField('unit_id');
    $doctorEmail = $doctor?->user?->email;
    $doctorPhone = $doctor?->user?->phone;
    $consultationType = strtolower($appointment->consultation_type ?? '');
    $consultationLabel = $formatLabel($appointment->consultation_type);
    $opdType = $consultationType === 'in-person' ? ($appointment->availability?->opd_type ?: null) : null;
    $opdTypeLabel = $opdType ? $formatLabel($opdType) : 'N/A';
    $consultationSummary = $opdType ? $consultationLabel . ' / ' . $opdTypeLabel . ' OPD' : $consultationLabel;

    $gender = strtolower(
        (string) ($patient?->gender instanceof \BackedEnum ? $patient->gender->value : $patient?->gender),
    );
    $maritalStatus = strtolower((string) $patient?->marital_status);

    // Determine guardian based on gender and marital status
    $guardianLabel = 'Father Name';
    $guardianName = $patientField('father_name');

    if ($maritalStatus === 'married' && $gender === 'female') {
        $guardianLabel = 'Husband Name';
        $guardianName = $patientField('husband_name');
    } elseif ($maritalStatus === 'married' && $gender === 'male') {
        $guardianLabel = 'Father Name';
        $guardianName = $patientField('father_name');
    }

    $addressParts = collect([
        $patient?->address,
        $patient?->area,
        $patient?->landmark,
        $patient?->city,
        $patient?->state,
        $patient?->pincode,
    ])
        ->filter()
        ->implode(', ');

    $appointmentDate = $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date) : null;
    $appointmentTime = $appointment->appointment_time
        ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A')
        : null;
    $appointmentEndTime = $appointment->appointment_end_time
        ? \Carbon\Carbon::parse($appointment->appointment_end_time)->format('g:i A')
        : null;
    $timeRange = collect([$appointmentTime, $appointmentEndTime])
        ->filter()
        ->implode(' - ');

    $paymentStatus = $payment?->status instanceof \BackedEnum ? $payment->status->value : $payment?->status;
    $isAdminBooking = ($appointment->booking_source ?? null) === 'admin';
    $adminPaymentType = $appointment->admin_payment_type;
    $isAdminWithoutPayment = $isAdminBooking && $adminPaymentType === 'without_payment';
    $adminPaymentLabel = $isAdminBooking
        ? ($isAdminWithoutPayment ? 'Admin - No Payment' : 'Admin - With Payment')
        : 'Patient Booking';
    $paymentLabel =
        $payment?->status instanceof \BackedEnum && method_exists($payment->status, 'label')
            ? $payment->status->label()
            : $formatLabel($paymentStatus);
    $paymentPaidLabel = $isAdminWithoutPayment
        ? 'Admin No Payment'
        : (strtolower((string) $paymentStatus) === 'paid' ? 'Paid' : 'Not Paid');
    $paymentId = $payment?->razorpay_payment_id ?: $payment?->transaction_id ?: $payment?->id;

    $prescriptionPdfPath = 'prescriptions/Prescription-' . $appointment->id . '.pdf';
    $hasPrescription = Storage::disk('public')->exists($prescriptionPdfPath);
    $prescriptionUrl = $hasPrescription ? Storage::disk('public')->url($prescriptionPdfPath) : null;
    $medicalReports = $appointment->medicalReports;
    $receiptDoc = $payment?->moduleDocuments()->where('name', 'receipt_pdf')->first();
    $documentCount = $medicalReports->count() + ($receiptDoc ? 1 : 0) + ($hasPrescription ? 1 : 0);

    $patientDetails = [
        ['label' => 'Phone Number', 'value' => $patientPhone],
        ['label' => 'Email', 'value' => $patientEmail],
        ['label' => 'Existing Patient ID', 'value' => $patientField('existing_patient_id')],
        [
            'label' => 'Age / Gender',
            'value' => trim(
                ($patient?->age ? $patient->age . ' years' : 'N/A') .
                    ' / ' .
                    $formatLabel(
                        $patient?->gender instanceof \BackedEnum ? $patient->gender->value : $patient?->gender,
                    ),
            ),
        ],
        ['label' => 'Marital Status', 'value' => $formatLabel($patient?->marital_status)],
        ['label' => $guardianLabel, 'value' => $guardianName],
        ['label' => 'Address', 'value' => $addressParts],
    ];

    $doctorDetails = [
        ['label' => 'Email', 'value' => $doctorEmail],
        ['label' => 'Phone Number', 'value' => $doctorPhone],
        ['label' => 'Experience', 'value' => $doctor?->years_experience ? $doctor->years_experience . ' years' : null],
    ];

    $appointmentDetails = [
        ['label' => 'Consultation', 'value' => $consultationSummary],
        [
            'label' => 'Timing',
            'value' => trim(($appointmentDate?->format('d M Y') ?? 'N/A') . ($timeRange ? ' · ' . $timeRange : '')),
        ],
        ['label' => 'Booking Source', 'value' => $adminPaymentLabel],
        ['label' => 'Payment Status', 'value' => $paymentPaidLabel],
        ['label' => 'Payment ID / Voucher ID', 'value' => $paymentId],
        [
            'label' => 'Price',
            'value' => '₹' . number_format((float) ($payment?->amount ?? ($appointment->fee_amount ?? 0)), 2),
        ],
        ['label' => 'Method', 'value' => $formatLabel($payment?->payment_method)],
        [
            'label' => 'Payment Waived By',
            'value' => $isAdminWithoutPayment
                ? trim(($appointment->paymentWaiver?->name ?? 'Admin') . ($appointment->payment_waived_at ? ' · ' . $appointment->payment_waived_at->format('d M Y, g:i A') : ''))
                : null,
        ],
        ['label' => 'Generated On', 'value' => $appointment->created_at?->format('d M Y, g:i A')],
    ];

    $printGender = $formatLabel($patient?->gender instanceof \BackedEnum ? $patient->gender->value : $patient?->gender);
    $printDob = $patient?->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M Y') : null;
    $printRows = collect([
        ['label' => 'Patient Name', 'value' => $patientName],
        ['label' => $guardianLabel, 'value' => $guardianName],
        ['label' => 'Gender', 'value' => $printGender === 'N/A' ? null : $printGender],
        ['label' => 'Date of Birth', 'value' => $printDob],
        ['label' => 'Marital Status', 'value' => $formatLabel($patient?->marital_status) === 'N/A' ? null : $formatLabel($patient?->marital_status)],
        ['label' => 'Address', 'value' => $addressParts],
        ['label' => 'Phone', 'value' => $patientPhone],
        ['label' => 'Email', 'value' => $patientEmail],
    ])->filter(fn ($row) => filled($row['value']))->values();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <style>
        @media print {
            @page {
                margin: 10mm;
            }

            body * {
                visibility: hidden;
            }

            .appointment-print-area,
            .appointment-print-area * {
                visibility: visible;
            }

            .appointment-print-area {
                display: block !important;
                position: absolute;
                inset: 0;
                width: 100%;
                background: #fff;
                color: #111827;
                padding: 18px;
            }

            .appointment-no-print {
                display: none !important;
            }

            .appointment-print-footer {
                display: block !important;
            }

            .appointment-screen-content {
                display: none !important;
            }

            .appointment-screen-header {
                display: none !important;
            }

            .appointment-print-card {
                display: block !important;
            }
        }

        .appointment-print-footer {
            display: none;
        }

        .appointment-print-card {
            display: none;
        }
    </style>

    <div class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="p-4 sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Appointment Overview</p>
                            <h1 class="mt-1 break-words text-2xl font-bold text-gray-950">{{ $patientName }}</h1>
                            <p class="mt-1 text-sm font-medium text-gray-600">
                                {{ $appointmentDate?->format('d M Y') ?? 'N/A' }}{{ $timeRange ? ' · ' . $timeRange : '' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold ring-1 {{ $statusClasses[$statusValue] ?? 'bg-gray-100 text-gray-800 ring-gray-200' }}">
                            {{ $statusEnum?->label() ?? $formatLabel($statusValue) }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800 ring-1 ring-gray-200">
                            {{ $consultationLabel }}
                        </span>
                        @if ($opdType)
                            <span
                                class="inline-flex items-center rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">
                                {{ $opdTypeLabel }} OPD
                            </span>
                        @endif
                        @if ($isAdminBooking)
                            <span
                                class="inline-flex items-center rounded-md {{ $isAdminWithoutPayment ? 'bg-sky-50 text-sky-800 ring-sky-200' : 'bg-violet-50 text-violet-800 ring-violet-200' }} px-3 py-2 text-sm font-semibold ring-1">
                                {{ $adminPaymentLabel }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="appointment-print-area overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="appointment-screen-header border-b border-gray-200 px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Patient Details</p>
                            <h2 class="mt-1 break-words text-xl font-bold text-gray-950">{{ $patientName }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $patient?->existing_patient_id ?: 'New patient' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button"
                                onclick="document.getElementById('patient-print-generated-at').textContent = new Date().toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }); window.print();"
                                class="appointment-no-print inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                                <x-heroicon-o-printer class="h-4 w-4" />
                                Print Patient Details
                            </button>
                        </div>
                    </div>
                </div>

                <div class="appointment-screen-content px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100 asdrfghj">
                        @foreach ($patientDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="appointment-print-card px-0 py-0">
                    <div class="bg-white text-gray-950">
                        <div class="mb-5 flex items-start justify-between gap-6">
                            <h2 class="text-2xl font-extrabold uppercase tracking-wide">Patient Details</h2>

                            @if (filled($patientUnitId))
                                <div class="text-right text-base font-bold">
                                    <span class="text-xs uppercase tracking-wide text-gray-500">Unit ID</span>
                                    <div>{{ $patientUnitId }}</div>
                                </div>
                            @endif
                        </div>

                        <dl class="space-y-3 text-base 24324">
                            @foreach ($printRows as $row)
                                <div class="{{ $row['label'] === 'Address' ? '' : 'grid grid-cols-[140px_1fr] gap-4' }}">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                        {{ $row['label'] }}
                                    </dt>
                                    <dd class="{{ $row['label'] === 'Address' ? 'mt-1 uppercase leading-snug' : 'break-words font-semibold text-gray-950' }}">
                                        {{ $row['value'] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                <div class="appointment-print-footer mt-6 border-t border-gray-200 px-0 py-4 text-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment ID</p>
                            <p class="mt-1 break-all font-bold text-gray-950">{{ $value($paymentId) }}</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Generated On</p>
                            <p id="patient-print-generated-at" class="mt-1 font-bold text-gray-950">
                                {{ now()->format('d M Y, g:i A') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Doctor Details</p>
                            <h2 class="mt-1 break-words text-xl font-bold text-gray-950">{{ $doctorName }}</h2>
                            <p class="mt-1 text-sm text-gray-500">Consulting doctor</p>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        @foreach ($doctorDetails as $detail)
                            <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr]">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $detail['label'] }}</dt>
                                <dd class="break-words text-sm font-semibold text-gray-950">
                                    {{ $value($detail['value']) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if ($videoConsultation?->host_url || $videoConsultation?->participate_url || $videoConsultation?->room_url)
                    <div class="border-t border-gray-100 px-4 py-4 sm:px-6">
                        <div class="flex flex-wrap gap-2">
                            @if ($videoConsultation?->host_url)
                                <a href="{{ $videoConsultation->host_url }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 ring-1 ring-violet-100 transition hover:bg-violet-100">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    Open host link
                                </a>
                            @endif
                            @if ($videoConsultation?->participate_url || $videoConsultation?->room_url)
                                <a href="{{ $videoConsultation?->participate_url ?: $videoConsultation?->room_url }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 ring-1 ring-sky-100 transition hover:bg-sky-100">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    Open participant link
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                        <h2 class="text-base font-bold text-gray-950">Appointment Details</h2>
                        <p class="mt-1 text-sm text-gray-500">Consultation and payment receipt summary.</p>
                    </div>
                    <div class="px-4 py-2 sm:px-6">
                        <dl class="divide-y divide-gray-100">
                            @foreach ($appointmentDetails as $detail)
                                <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr]">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ $detail['label'] }}</dt>
                                    <dd class="break-words text-sm font-semibold text-gray-950 sm:text-right">
                                        {{ $value($detail['value']) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 sm:px-6">
                        <div>
                            <h2 class="text-base font-bold text-gray-950">Related Documents</h2>
                            <p class="mt-1 text-sm text-gray-500">Medical reports, receipt, and prescription files.</p>
                        </div>
                        <span
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700">{{ $documentCount }}
                            Items</span>
                    </div>

                    <div class="p-4 sm:p-6">
                        @if ($medicalReports->isEmpty() && !$receiptDoc && !$hasPrescription)
                            <div class="rounded-lg border border-dashed border-gray-300 py-10 text-center">
                                <x-heroicon-o-document-minus class="mx-auto h-10 w-10 text-gray-300" />
                                <p class="mt-3 text-sm font-medium text-gray-500">No documents attached to this
                                    appointment.</p>
                            </div>
                        @else
                            <div class="grid gap-3 lg:grid-cols-2">
                                @foreach ($medicalReports as $report)
                                    @php
                                        $reportUrl = $report->file_url;
                                        $reportName = $report->name;
                                        $reportExt = strtolower(
                                            pathinfo($report->file_name ?? $reportName, PATHINFO_EXTENSION),
                                        );
                                        $reportViewUrl = $reportUrl;

                                        if (
                                            in_array($reportExt, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx']) &&
                                            $reportUrl
                                        ) {
                                            $reportViewUrl =
                                                'https://docs.google.com/viewer?url=' .
                                                urlencode($reportUrl) .
                                                '&embedded=true';
                                        }
                                    @endphp
                                    <div
                                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 p-4">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                                <x-heroicon-o-document-chart-bar class="h-6 w-6" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-gray-950">{{ $reportName }}
                                                </p>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    {{ $report->type_label }}</p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            @if ($reportUrl)
                                                <x-filament::icon-button icon="heroicon-o-eye" tag="a"
                                                    href="{{ $reportViewUrl }}" target="_blank" color="primary"
                                                    size="sm" tooltip="View" />
                                                <x-filament::icon-button icon="heroicon-o-arrow-down-tray"
                                                    tag="a" href="{{ $reportUrl }}"
                                                    download="{{ $report->file_name ?? $reportName }}" color="gray"
                                                    size="sm" tooltip="Download" />
                                            @endif
                                            <x-filament::icon-button icon="heroicon-o-arrow-top-right-on-square"
                                                tag="a"
                                                href="{{ \App\Filament\Resources\MedicalReports\MedicalReportResource::getUrl('view', ['record' => $report->id]) }}"
                                                color="gray" size="sm" tooltip="Report Details" />
                                        </div>
                                    </div>
                                @endforeach

                                @if ($receiptDoc)
                                    @php
                                        $receiptFile = $receiptDoc->files[0] ?? null;
                                        $receiptUrl = $receiptFile ? storage_url($receiptFile) : null;
                                        $receiptExt = $receiptFile
                                            ? strtolower(pathinfo($receiptFile, PATHINFO_EXTENSION))
                                            : '';
                                        $receiptViewUrl = $receiptUrl;

                                        if (in_array($receiptExt, ['ppt', 'pptx', 'doc', 'docx']) && $receiptUrl) {
                                            $receiptViewUrl =
                                                'https://docs.google.com/viewer?url=' .
                                                urlencode($receiptUrl) .
                                                '&embedded=true';
                                        }
                                    @endphp
                                    <div
                                        class="flex items-center justify-between gap-3 rounded-lg border border-emerald-100 bg-emerald-50/40 p-4">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                <x-heroicon-o-document-check class="h-6 w-6" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-gray-950">Payment Receipt</p>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Official invoice</p>
                                            </div>
                                        </div>
                                        @if ($receiptUrl)
                                            <div class="flex shrink-0 items-center gap-1">
                                                <x-filament::icon-button icon="heroicon-o-eye" tag="a"
                                                    href="{{ $receiptViewUrl }}" target="_blank" color="success"
                                                    size="sm" tooltip="View Receipt" />
                                                <x-filament::icon-button icon="heroicon-o-arrow-down-tray"
                                                    tag="a" href="{{ $receiptUrl }}"
                                                    download="Receipt-{{ $appointment->id }}.pdf" color="gray"
                                                    size="sm" tooltip="Download Receipt" />
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($hasPrescription)
                                    <div
                                        class="flex items-center justify-between gap-3 rounded-lg border border-primary-100 bg-primary-50/40 p-4">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                                                <x-heroicon-o-document-text class="h-6 w-6" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-gray-950">Doctor Prescription
                                                </p>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Medical advice</p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <x-filament::icon-button icon="heroicon-o-eye" tag="a"
                                                href="{{ $prescriptionUrl }}" target="_blank" color="primary"
                                                size="sm" tooltip="View Prescription" />
                                            <x-filament::icon-button icon="heroicon-o-arrow-down-tray" tag="a"
                                                href="{{ $prescriptionUrl }}"
                                                download="Prescription-{{ $appointment->id }}.pdf" color="gray"
                                                size="sm" tooltip="Download" />
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            @if ($appointment->notes || $appointment->instructions_by_doctor || $appointment->next_visit_date)
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-2">
                    <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                        <h2 class="text-base font-bold text-gray-950">Clinical Notes</h2>
                    </div>
                    <div class="space-y-4 p-4 text-sm text-gray-700 sm:p-6">
                        @if ($appointment->notes)
                            <div>
                                <p class="font-semibold text-gray-950">Visit Reason</p>
                                @if (is_array($appointment->notes))
                                    <ul class="mt-2 list-disc space-y-1 pl-4">
                                        @foreach ($appointment->notes as $key => $note)
                                            <li><span
                                                    class="font-medium">{{ str($key)->replace('_', ' ')->title() }}:</span>
                                                {{ is_array($note) ? implode(', ', $note) : $note }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-2 whitespace-pre-wrap">{{ $appointment->notes }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($appointment->instructions_by_doctor)
                            <div>
                                <p class="font-semibold text-gray-950">Doctor Instructions</p>
                                <p class="mt-2 whitespace-pre-wrap">
                                    {{ is_array($appointment->instructions_by_doctor) ? collect($appointment->instructions_by_doctor)->filter()->implode(', ') : $appointment->instructions_by_doctor }}
                                </p>
                            </div>
                        @endif

                        @if ($appointment->next_visit_date)
                            <div>
                                <p class="font-semibold text-gray-950">Next Visit</p>
                                <p class="mt-2">
                                    {{ \Carbon\Carbon::parse($appointment->next_visit_date)->format('d M Y') }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-dynamic-component>
