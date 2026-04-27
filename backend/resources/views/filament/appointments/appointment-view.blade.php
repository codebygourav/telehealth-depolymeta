@php
    use App\Enums\AppointmentStatus;
@endphp
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $appointment = $entry->getRecord();
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        // Ensure the status is always an enum instance for further consistent usage
        if ($appointment->status instanceof AppointmentStatus) {
            $statusEnum = $appointment->status;
        } else {
            $statusEnum = AppointmentStatus::tryFrom($appointment->status);
        }

        $statusColors = [
            AppointmentStatus::PENDING->value => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            AppointmentStatus::CONFIRMED->value => 'bg-blue-100 text-blue-800 border-blue-200',
            AppointmentStatus::COMPLETED->value => 'bg-green-100 text-green-800 border-green-200',
            AppointmentStatus::CANCELLED->value => 'bg-red-100 text-red-800 border-red-200',
        ];
        $statusIcons = [
            AppointmentStatus::PENDING->value => 'heroicon-o-clock',
            AppointmentStatus::CONFIRMED->value => 'heroicon-o-check-circle',
            AppointmentStatus::COMPLETED->value => 'heroicon-o-check-badge',
            AppointmentStatus::CANCELLED->value => 'heroicon-o-x-circle',
        ];
        $statusValue = $statusEnum?->value ?? (is_string($appointment->status) ? $appointment->status : null);
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                {{-- Appointment Info --}}
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-16 h-16 rounded-xl bg-primary/10 flex items-center justify-center">
                            <x-heroicon-o-calendar-days class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Appointment Details</h1>
                            <p class="text-sm text-gray-600 mt-1">ID: {{ $appointment->id }}</p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border {{ $statusColors[$statusValue] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                            <x-dynamic-component :component="$statusIcons[$statusValue] ?? 'heroicon-o-information-circle'" class="w-4 h-4" />
                            {{ ucfirst($statusEnum?->name ?? (is_string($appointment->status) ? $appointment->status : 'Unknown')) }}
                        </span>
                        @php
                            $consultationType = strtolower($appointment->consultation_type ?? '');
                            $consultationIcon =
                                $consultationType === 'video'
                                    ? 'heroicon-o-video-camera'
                                    : ($consultationType === 'in-person'
                                        ? 'heroicon-o-building-office-2'
                                        : 'heroicon-o-phone');
                        @endphp
                        <span
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-primary/10 text-primary border border-primary/20">
                            <x-dynamic-component :component="$consultationIcon" class="w-4 h-4" />
                            {{ ucfirst(str_replace('-', ' ', $appointment->consultation_type ?? 'N/A')) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Date & Time Card --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-primary/5 to-primary/10">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-primary" />
                            Appointment Schedule
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <x-heroicon-o-calendar class="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Date</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, F d, Y') }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <x-heroicon-o-clock class="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Time</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($appointment->appointment_end_time)->format('g:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Patient & Doctor Information Card --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-primary/5 to-primary/10">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <x-heroicon-o-user class="w-5 h-5 text-primary" />
                            Patient & Doctor Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-8">
                            {{-- Patient Info --}}
                            <div class="flex-1 flex flex-col items-center">
                                <img src="{{ storage_url($patient?->user?->avatar) }}" alt="Patient Avatar"
                                    class="w-20 h-20 rounded-xl border-2 border-primary/20 object-cover mb-3">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">
                                    {{ $patient?->first_name ?? '—' }} {{ $patient?->last_name ?? '—' }}
                                </h3>
                                <span
                                    class="inline-block text-xs font-semibold text-primary-700 bg-primary-100 px-3 py-1 rounded mb-2">Patient</span>
                                <div class="grid grid-cols-1 gap-3 mt-2 w-full">
                                    @if ($patient?->user?->email)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">{{ $patient->user->email }}</span>
                                        </div>
                                    @endif
                                    @if ($patient?->user?->phone)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">{{ $patient->user->phone }}</span>
                                        </div>
                                    @endif
                                    @if ($patient?->mobile_no)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-device-phone-mobile class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">{{ $patient->mobile_no }}</span>
                                        </div>
                                    @endif
                                    @if ($patient?->dob)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-cake class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">
                                                {{ \Carbon\Carbon::parse($patient->dob)->format('M d, Y') }}
                                                ({{ \Carbon\Carbon::parse($patient->dob)->age }} years)
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="hidden md:block w-px bg-gray-200"></div>
                            {{-- Doctor Info --}}
                            <div class="flex-1 flex flex-col items-center">
                                <img src="{{ storage_url($doctor?->user?->avatar) }}" alt="Doctor Avatar"
                                    class="w-20 h-20 rounded-xl border-2 border-primary/20 object-cover mb-3">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">
                                    Dr. {{ $doctor?->first_name ?? '—' }} {{ $doctor?->last_name ?? '—' }}
                                </h3>
                                <span
                                    class="inline-block text-xs font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded mb-2">Doctor</span>
                                @if ($doctor?->education_info)
                                    <p class="text-xs text-gray-600 mb-2 text-center">
                                        {{ collect($doctor->education_info)->pluck('degree')->filter()->implode(', ') }}
                                    </p>
                                @endif
                                <div class="grid grid-cols-1 gap-3 mt-2 w-full">
                                    @if ($doctor?->user?->email)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">{{ $doctor->user->email }}</span>
                                        </div>
                                    @endif
                                    @if ($doctor?->user?->phone)
                                        <div class="flex items-center gap-2 text-sm justify-center">
                                            <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-700">{{ $doctor->user->phone }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Related Documents & Files Card --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div
                        class="p-6 border-b border-gray-200 bg-gradient-to-r from-primary/5 to-primary/10 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <x-heroicon-o-paper-clip class="w-5 h-5 text-primary" />
                            Related Documents & Files
                        </h2>
                        <span class="bg-primary/10 text-primary px-2 py-0.5 rounded-full text-xs font-bold">
                            {{ ($appointment->medicalReports->count() ?? 0) + ($appointment->payment?->moduleDocuments->count() ?? 0) }}
                            Items
                        </span>
                    </div>
                    <div class="p-6">
                        @php
                            $medicalReports = $appointment->medicalReports;
                            $receiptDoc = $appointment->payment
                                ?->moduleDocuments()
                                ->where('name', 'receipt_pdf')
                                ->first();
                        @endphp

                        @if ($medicalReports->isEmpty() && !$receiptDoc)
                            <div class="text-center py-8">
                                <x-heroicon-o-document-minus class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p class="text-gray-500 font-medium">No documents attached to this appointment.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Medical Reports --}}
                                @foreach ($medicalReports as $report)
                                    @php
                                        $reportUrl = $report->file_url;
                                        $reportName = $report->name;
                                        $reportExt = strtolower(
                                            pathinfo($report->file_name ?? $reportName, PATHINFO_EXTENSION),
                                        );

                                        $reportViewUrl = $reportUrl;
                                        $isOfficeReport = in_array($reportExt, [
                                            'ppt',
                                            'pptx',
                                            'doc',
                                            'docx',
                                            'xls',
                                            'xlsx',
                                        ]);
                                        if ($isOfficeReport && $reportUrl) {
                                            $reportViewUrl =
                                                'https://docs.google.com/viewer?url=' .
                                                urlencode($reportUrl) .
                                                '&embedded=true';
                                        }
                                    @endphp
                                    <div
                                        class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-primary-300 hover:bg-primary-50/50 transition group">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                                                <x-heroicon-o-document-chart-bar class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate max-w-[150px]">
                                                    {{ $reportName }}</p>
                                                <p class="text-[10px] text-gray-500 uppercase font-black">
                                                    {{ $report->type_label }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if ($reportUrl)
                                                <x-filament::icon-button icon="heroicon-o-eye" tag="a"
                                                    href="{{ $reportViewUrl }}" target="_blank" color="primary"
                                                    size="sm" tooltip="View in Browser" />
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

                                {{-- Payment Receipt --}}
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
                                        class="flex items-center justify-between p-4 rounded-xl border border-gray-100 bg-green-50/30 border-green-200">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                                <x-heroicon-o-document-check class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">Payment Receipt</p>
                                                <p
                                                    class="text-[10px] text-gray-500 uppercase font-black tracking-widest">
                                                    Official Invoice</p>
                                            </div>
                                        </div>
                                        @if ($receiptUrl)
                                            <div class="flex items-center gap-2">
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
                            </div>
                        @endif

                        {{-- Prescription PDF --}}
                        @php
                            $prescriptionPdfPath = 'prescriptions/Prescription-' . $appointment->id . '.pdf';
                            $hasPrescription = \Illuminate\Support\Facades\Storage::disk('public')->exists(
                                $prescriptionPdfPath,
                            );
                            $prescriptionUrl = $hasPrescription
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($prescriptionPdfPath)
                                : null;
                        @endphp

                        @if ($hasPrescription)
                            <div
                                class="mt-4 p-4 rounded-xl border border-primary-100 bg-primary-50/30 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-primary-100 text-primary flex items-center justify-center">
                                        <x-heroicon-o-document-text class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Doctor Prescription</p>
                                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">
                                            Medical Advice</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
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
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Payment Information --}}
                @if ($appointment->fee_amount)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Payment</h3>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Consultation Fee</span>
                                <span class="text-2xl font-bold text-primary">
                                    ₹{{ number_format($appointment->fee_amount, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Appointment Details --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Details</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        @if ($appointment->stamp_preference)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                    <x-heroicon-o-check-badge class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Stamp Preference</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ str_replace('_', ' ', ucfirst($appointment->stamp_preference)) }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                <x-heroicon-o-calendar class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Created</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $appointment->created_at->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $appointment->created_at->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                        @if ($appointment->updated_at && $appointment->updated_at->ne($appointment->created_at))
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                    <x-heroicon-o-pencil class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Last Updated</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $appointment->updated_at->format('M d, Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $appointment->updated_at->format('g:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                {{-- Notes Card --}}
                @if ($appointment->notes)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-primary/5 to-primary/10">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <x-heroicon-o-document-text class="w-5 h-5 text-primary" />
                                Notes
                            </h2>
                        </div>
                        <div class="p-6">
                            @if (is_array($appointment->notes))
                                <ul class="text-gray-700 leading-relaxed pl-4 list-disc">
                                    @foreach ($appointment->notes as $key => $value)
                                        <li>
                                            <span
                                                class="font-medium capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                            <span>{{ $value }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                                    {{ $appointment->notes }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>
