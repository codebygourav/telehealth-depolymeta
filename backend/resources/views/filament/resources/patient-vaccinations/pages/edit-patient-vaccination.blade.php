<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $record->loadMissing(['patient', 'doctor', 'vaccination']);

        $patientName = trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')) ?: 'Unknown Patient';
        $doctorName = trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? '')) ?: 'Unknown Doctor';
        $vaccineName = $record->vaccination?->name ?: 'Unknown Vaccine';

        $statusValue = $record->status instanceof \App\Enums\VaccinationStatus
            ? $record->status->value
            : (string) $record->status;

        $statusLabel = \App\Enums\VaccinationStatus::tryFrom($statusValue)?->label() ?: str($statusValue)->replace('_', ' ')->title()->toString();
    @endphp

    <div class="vax-edit-panel">
        <div class="vax-edit-head">
            <div>
                <h2>Edit Patient Vaccination Dose</h2>
                <p>Update status, timeline, and doctor notes. Fields now show conditionally by selected status.</p>
            </div>
            <div class="vax-head-actions">
                <span class="vax-pill is-blue">{{ $statusLabel }}</span>
                <span class="vax-pill is-gray">Dose {{ $record->dose_no ?? '-' }}</span>
            </div>
        </div>

        <div class="vax-note-box mb-4">
            <strong>{{ $vaccineName }}</strong> for <strong>{{ $patientName }}</strong>
            <span class="text-gray-500"> · Prescribed by {{ $doctorName }}</span>
        </div>

        <div class="vax-form-surface">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
