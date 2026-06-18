@php
    $record = $getState();
    $record->loadMissing(['patientVaccination.patient', 'patientVaccination.vaccination', 'patientVaccination.doctor']);

    $patientName = trim(($record->patientVaccination?->patient?->first_name ?? '').' '.($record->patientVaccination?->patient?->last_name ?? '')) ?: '—';
    $vaccineName = $record->patientVaccination?->vaccination?->name ?? '—';
    $doctorName = trim(($record->patientVaccination?->doctor?->first_name ?? '').' '.($record->patientVaccination?->doctor?->last_name ?? '')) ?: '—';
    $documentPath = $record->document;
    $url = $documentPath ? storage_url($documentPath) : null;
    $ext = $documentPath ? strtolower(pathinfo($documentPath, PATHINFO_EXTENSION)) : '';
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $isPdf = $ext === 'pdf';
    $typeLabel = $record->document_type instanceof \App\Enums\VaccinationDocumentType
        ? $record->document_type->label()
        : ucfirst((string) $record->document_type);
@endphp

<div class="space-y-6">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <x-heroicon-m-document-text class="w-6 h-6 text-primary-500" />
                <span>Vaccination Document Details</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Document Type</p>
                <p class="text-sm font-bold text-gray-900 mt-1">{{ $typeLabel }}</p>
            </div>
            <div class="p-4 bg-primary-50 rounded-xl border border-primary-100 shadow-sm">
                <p class="text-[10px] font-bold text-primary-600 uppercase tracking-widest">Patient</p>
                <p class="text-sm font-bold text-primary-800 mt-1">{{ $patientName }}</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border border-purple-100 shadow-sm">
                <p class="text-[10px] font-bold text-purple-600 uppercase tracking-widest">Vaccine</p>
                <p class="text-sm font-bold text-purple-800 mt-1">{{ $vaccineName }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Doctor</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $doctorName }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Certificate Number</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $record->certificate_number ?: '—' }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">File Preview</x-slot>

        @if ($url)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ basename($documentPath) }}</p>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">{{ $ext ?: 'file' }}</p>
                    </div>
                    <div class="flex gap-1">
                        <x-filament::icon-button icon="heroicon-m-eye" color="gray" tag="a" target="_blank" href="{{ $url }}" size="sm" />
                        <x-filament::icon-button icon="heroicon-m-arrow-down-tray" color="gray" tag="a" href="{{ $url }}" download size="sm" />
                    </div>
                </div>

                <div class="p-4">
                    @if ($isImage)
                        <img src="{{ $url }}" alt="Vaccination document" class="w-full max-h-128 object-contain rounded-lg border border-gray-100 bg-gray-50">
                    @elseif ($isPdf)
                        <iframe src="{{ $url }}" class="w-full h-128 rounded-lg border border-gray-200" title="PDF preview"></iframe>
                    @else
                        <div class="p-8 text-center text-sm text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                            Preview not available for this file type. Use Open or Download.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="p-8 text-center text-sm text-gray-400 italic bg-white rounded-xl border border-dashed border-gray-200">
                No document file attached.
            </div>
        @endif
    </x-filament::section>
</div>
