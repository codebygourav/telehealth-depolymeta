<x-filament-panels::page>
    @include('filament.resources.medicine-templates.partials.template-summary', ['record' => $this->getRecord(), 'compact' => true])

    <div class="medicine-edit-panel">
        <div class="medicine-edit-head">
            <div>
                <h2>Edit Template</h2>
                <p>Update scope, medicines, dose count, generated timings, meal guidance, duration, and patient instructions.</p>
            </div>
        </div>

        <div class="medicine-form-surface">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
