@php
    $record = $getRecord();
    $record->loadMissing(['patientVaccination.patient', 'patientVaccination.vaccination']);

    $patientName = trim(($record->patientVaccination?->patient?->first_name ?? '').' '.($record->patientVaccination?->patient?->last_name ?? '')) ?: 'Patient';
    $vaccineName = $record->patientVaccination?->vaccination?->name ?? 'Vaccine';
    $documentPath = $record->document;
    $url = $documentPath ? storage_url($documentPath) : null;
    $ext = $documentPath ? strtolower(pathinfo($documentPath, PATHINFO_EXTENSION)) : '';
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $isPdf = $ext === 'pdf';
    $typeLabel = $record->document_type instanceof \App\Enums\VaccinationDocumentType
        ? $record->document_type->label()
        : ucfirst((string) $record->document_type);
@endphp

<div class="h-full w-full bg-white rounded-xl group overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition">
    <div class="p-4 border-b border-gray-100 bg-white">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-600 flex items-center justify-center shadow">
                @if ($isPdf)
                    <x-heroicon-o-document-text class="w-6 h-6 text-white" />
                @elseif ($isImage)
                    <x-heroicon-o-photo class="w-6 h-6 text-white" />
                @else
                    <x-heroicon-o-paper-clip class="w-6 h-6 text-white" />
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-gray-900 text-base leading-tight truncate">{{ $typeLabel }}</h3>
                <p class="text-xs text-gray-600 font-medium mt-1 truncate">{{ $vaccineName }}</p>
            </div>
        </div>
    </div>

    <div class="p-3 border-b border-gray-100 bg-gray-50 text-xs space-y-1">
        <p class="text-gray-500"><span class="font-bold text-gray-700">Patient:</span> {{ $patientName }}</p>
        @if ($record->certificate_number)
            <p class="text-gray-500"><span class="font-bold text-gray-700">Certificate #:</span> {{ $record->certificate_number }}</p>
        @endif
    </div>

    @if ($url && $isImage)
        <div class="p-3 bg-white">
            <img src="{{ $url }}" alt="Document preview" class="w-full h-36 object-cover rounded-lg border border-gray-100">
        </div>
    @elseif ($url && $isPdf)
        <div class="p-3 bg-white">
            <div class="h-36 rounded-lg border border-red-100 bg-red-50 flex items-center justify-center">
                <x-heroicon-o-document-text class="w-10 h-10 text-red-500" />
                <span class="ml-2 text-sm font-semibold text-red-700">PDF Document</span>
            </div>
        </div>
    @endif

    <div class="p-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $record->created_at?->format('d M Y') }}</span>
        <span class="text-xs font-bold text-emerald-700">View Details</span>
    </div>
</div>
