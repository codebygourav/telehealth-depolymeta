<x-filament-panels::page>
    @include('filament.resources.vaccination-templates.partials.template-summary', ['record' => $this->getRecord(), 'compact' => true])

    <div class="vax-edit-panel">
        <div class="vax-edit-head">
            <div>
                <h2>Edit Template Configuration</h2>
                <p>Update only the target, dose timing rules, reminders, and calculation preview needed for this schedule.</p>
            </div>
        </div>

        <div class="vax-form-surface">
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
