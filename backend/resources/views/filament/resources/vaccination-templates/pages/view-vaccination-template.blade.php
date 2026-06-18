<x-filament-panels::page>
    @include('filament.resources.vaccination-templates.partials.template-summary', ['record' => $this->getRecord()])

    <div class="vax-section">
        <h2>What Doctor Will See</h2>
        <div class="vax-note-box">
            Doctor frontend shows only active templates. The doctor previews calculated values, assigns the template to the registered patient or family profile, then manages complete, reschedule, hold, skip, remark, certificate, and booster actions.
        </div>
    </div>
</x-filament-panels::page>
