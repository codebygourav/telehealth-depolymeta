<x-filament-panels::page>
    @include('filament.resources.medicine-templates.partials.template-summary', ['record' => $this->getRecord()])

    <div class="medicine-admin-note">
        <h2>How Doctors Use This</h2>
        <p>
            Doctors can select this template from the appointment prescription tab. The system creates normal prescription
            records, applies the generated timings, refreshes the PDF, and keeps the patient prescription view unchanged.
        </p>
    </div>
</x-filament-panels::page>
