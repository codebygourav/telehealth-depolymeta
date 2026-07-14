<x-filament-panels::page>
    <style>
        .medicine-master-page {
            --medicine-primary: #055bd9;
            --medicine-primary-dark: #064eb8;
            --medicine-primary-soft: #eef5ff;
            --medicine-text: #172033;
            --medicine-muted: #667085;
            --medicine-line: #e5eaf1;
            --medicine-success: #0f9d6c;
            --medicine-warning: #b66b00;
            --medicine-shadow: 0 8px 26px rgba(24, 39, 75, .07);
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .medicine-side,
        .medicine-panel {
            background: #fff;
            border: 1px solid var(--medicine-line);
            border-radius: 15px;
            box-shadow: var(--medicine-shadow);
            overflow: visible;
        }

        .dark .medicine-side,
        .dark .medicine-panel {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }

        .medicine-side {
            position: sticky;
            top: 1.5rem;
            overflow: hidden;
        }

        .medicine-side-head {
            padding: 18px;
            border-bottom: 1px solid var(--medicine-line);
            background: linear-gradient(180deg, #fbfdff 0%, #fff 100%);
        }

        .dark .medicine-side-head {
            background: rgb(var(--gray-900));
            border-bottom-color: rgb(var(--gray-800));
        }

        .medicine-logo {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--medicine-primary);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .medicine-title {
            margin: 0;
            font-size: 19px;
            line-height: 1.25;
            font-weight: 800;
            color: var(--medicine-text);
        }

        .dark .medicine-title {
            color: #fff;
        }

        .medicine-subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            line-height: 1.45;
            color: var(--medicine-muted);
        }

        .medicine-side-body {
            padding: 16px;
        }

        .medicine-kv {
            display: grid;
            gap: 10px;
        }

        .medicine-kv-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f4f8;
            font-size: 12px;
            color: var(--medicine-muted);
        }

        .dark .medicine-kv-row {
            border-bottom-color: rgb(var(--gray-800));
        }

        .medicine-kv-row strong {
            color: var(--medicine-text);
            text-align: right;
            font-weight: 800;
        }

        .dark .medicine-kv-row strong {
            color: #fff;
        }

        .medicine-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 14px;
        }

        .medicine-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 9px;
            background: var(--medicine-primary-soft);
            color: var(--medicine-primary);
            font-size: 11px;
            font-weight: 800;
        }

        .medicine-badge.success {
            background: #eaf8f2;
            color: var(--medicine-success);
        }

        .medicine-badge.warning {
            background: #fff6e7;
            color: var(--medicine-warning);
        }

        .medicine-help {
            margin-top: 16px;
            border: 1px solid #cfe2ff;
            border-radius: 12px;
            background: var(--medicine-primary-soft);
            padding: 12px;
            color: #24466f;
            font-size: 12px;
            line-height: 1.5;
        }

        .dark .medicine-help {
            background: rgba(5, 91, 217, .12);
            border-color: rgba(147, 197, 253, .25);
            color: rgb(var(--gray-300));
        }

        .medicine-panel-head {
            padding: 16px 18px;
            border-bottom: 1px solid var(--medicine-line);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .dark .medicine-panel-head {
            border-bottom-color: rgb(var(--gray-800));
        }

        .medicine-panel-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--medicine-text);
        }

        .dark .medicine-panel-head h2 {
            color: #fff;
        }

        .medicine-panel-head p {
            margin: 4px 0 0;
            color: var(--medicine-muted);
            font-size: 12px;
        }

        .medicine-panel-body {
            padding: 18px;
        }

        .medicine-panel-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            flex-wrap: wrap;
        }

        .medicine-form-shell .fi-section,
        .medicine-form-shell .fi-sc-section {
            border-radius: 12px !important;
            border-color: var(--medicine-line) !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        .medicine-form-shell .fi-section-header,
        .medicine-form-shell .fi-sc-section-header {
            background: #fafcff !important;
            border-bottom: 1px solid var(--medicine-line);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .dark .medicine-form-shell .fi-section-header,
        .dark .medicine-form-shell .fi-sc-section-header {
            background: rgb(var(--gray-950)) !important;
            border-bottom-color: rgb(var(--gray-800));
        }

        .medicine-form-shell .fi-btn-color-primary {
            background: var(--medicine-primary) !important;
        }

        .medicine-form-shell .fi-btn-color-primary:hover {
            background: var(--medicine-primary-dark) !important;
        }

        @media (max-width: 1100px) {
            .medicine-master-page {
                grid-template-columns: 1fr;
            }

            .medicine-side {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .medicine-panel-head {
                display: block;
            }

            .medicine-panel-actions {
                justify-content: stretch;
                margin-top: 12px;
            }
        }
    </style>

    @php
        $isCreate = $this instanceof \Filament\Resources\Pages\CreateRecord;
        $submitMethod = $isCreate ? 'create' : 'save';
        $data = $this->data ?? [];
        $record = $this->record ?? null;
        $medicineName = trim((string) ($data['name'] ?? $record?->name ?? ''));
        $medicineName = $medicineName !== '' ? $medicineName : ($isCreate ? 'New medicine' : 'Medicine');
        $categoryName = $record?->category?->name ?? 'Not selected';
        $typeName = $record?->type?->name ?? 'Not selected';
        $aliases = $data['spoken_aliases'] ?? $record?->spoken_aliases ?? [];
        $strengths = $data['strength_options'] ?? $record?->strength_options ?? [];
        $dosages = $data['dosage_options'] ?? $record?->dosage_options ?? [];
        $speechEnabled = (bool) ($data['speech_enabled'] ?? $record?->speech_enabled ?? true);
    @endphp

    <div class="medicine-master-page medicine-form-shell">
        <aside class="medicine-side">
            <div class="medicine-side-head">
                <div class="medicine-logo">Rx</div>
                <h3 class="medicine-title">{{ $medicineName }}</h3>
                <p class="medicine-subtitle">
                    Configure the admin medicine options used by browser speech-to-text prescriptions.
                </p>
            </div>

            <div class="medicine-side-body">
                <div class="medicine-kv">
                    <div class="medicine-kv-row"><span>Mode</span><strong>{{ $isCreate ? 'Create' : 'Edit' }}</strong></div>
                    <div class="medicine-kv-row"><span>Category</span><strong>{{ $categoryName }}</strong></div>
                    <div class="medicine-kv-row"><span>Type</span><strong>{{ $typeName }}</strong></div>
                    <div class="medicine-kv-row"><span>Aliases</span><strong>{{ is_array($aliases) ? count($aliases) : 0 }}</strong></div>
                    <div class="medicine-kv-row"><span>Strengths</span><strong>{{ is_array($strengths) ? count($strengths) : 0 }}</strong></div>
                    <div class="medicine-kv-row"><span>Dosage options</span><strong>{{ is_array($dosages) ? count($dosages) : 0 }}</strong></div>
                </div>

                <div class="medicine-badge-row">
                    <span class="medicine-badge {{ $speechEnabled ? 'success' : 'warning' }}">
                        Speech {{ $speechEnabled ? 'enabled' : 'off' }}
                    </span>
                    <span class="medicine-badge">Admin source</span>
                </div>

                <div class="medicine-help">
                    Add spoken terms and type-specific option values here. The doctor app should show these choices
                    after the medicine is selected or detected by voice, while the API can still accept custom values.
                </div>
            </div>
        </aside>

        <section class="medicine-panel">
            <form wire:submit="{{ $submitMethod }}">
                <div class="medicine-panel-head">
                    <div>
                        <h2>{{ $isCreate ? 'Add medicine' : 'Edit medicine' }}</h2>
                        <p>Medicine setup, spoken aliases, and doctor-facing prescription options.</p>
                    </div>

                    <div class="medicine-panel-actions">
                        <x-filament::actions
                            :actions="$this->getFormActions()"
                            :alignment="$this->getFormActionsAlignment()"
                        />
                    </div>
                </div>

                <div class="medicine-panel-body">
                    {{ $this->form }}

                    <div class="medicine-panel-actions" style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--medicine-line);">
                        <x-filament::actions
                            :actions="$this->getFormActions()"
                            :alignment="$this->getFormActionsAlignment()"
                        />
                    </div>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
