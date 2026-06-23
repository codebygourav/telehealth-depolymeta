@php
    /** @var \App\Models\MedicineTemplate $record */
    $compact = $compact ?? false;
    $record->loadMissing(['doctor.user', 'department', 'items.medicine.type']);
    $items = $record->items;

    $scope = $record->scope_type ?? ($record->doctor_id ? \App\Models\MedicineTemplate::SCOPE_DOCTOR : \App\Models\MedicineTemplate::SCOPE_GLOBAL);
    $scopeLabel = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => 'Doctor Specific',
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => 'Department Specific',
        default => 'Global - All Doctors',
    };
    $scopeDetail = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? '')) ?: ($record->doctor?->name ?? 'Selected doctor'),
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => $record->department?->name ?? 'Selected department',
        default => 'Available for every doctor',
    };
    $scopeClass = match ($scope) {
        \App\Models\MedicineTemplate::SCOPE_DOCTOR => 'is-blue',
        \App\Models\MedicineTemplate::SCOPE_DEPARTMENT => 'is-amber',
        default => 'is-green',
    };

    $totalDosesPerDay = $items->sum(fn($item) => (int) ($item->doses_per_day ?: 1));
    $timingCount = $items->sum(fn($item) => count($item->frequency_times ?? []));
    $longestDuration = $items->max('duration_value');

    $formatTime = function (?string $time): string {
        if (! $time) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('h:i A');
        } catch (\Throwable) {
            return $time;
        }
    };
@endphp

<style>
    .medicine-admin-shell{display:grid;gap:1rem}
    .medicine-page-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;border:1px solid #e5e7eb;background:linear-gradient(135deg,#f8fafc,#eff6ff);border-radius:16px;padding:1.25rem}
    .medicine-kicker{margin:0 0 .35rem;color:#2563eb;font-size:.75rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
    .medicine-page-head h1{margin:0;color:#111827;font-size:1.55rem;font-weight:800;line-height:1.2}
    .medicine-page-head p{margin:.45rem 0 0;color:#64748b;max-width:760px}
    .medicine-head-actions,.medicine-chip-row{display:flex;flex-wrap:wrap;gap:.5rem}
    .medicine-pill,.medicine-chip{display:inline-flex;align-items:center;border-radius:999px;padding:.35rem .7rem;font-size:.75rem;font-weight:700;white-space:nowrap}
    .medicine-pill.is-green,.medicine-chip.is-green{background:#dcfce7;color:#166534}
    .medicine-pill.is-blue,.medicine-chip.is-blue{background:#dbeafe;color:#1d4ed8}
    .medicine-pill.is-amber,.medicine-chip.is-amber{background:#fef3c7;color:#92400e}
    .medicine-pill.is-gray,.medicine-chip.is-gray{background:#f1f5f9;color:#475569}
    .medicine-pill.is-rose,.medicine-chip.is-rose{background:#ffe4e6;color:#be123c}
    .medicine-stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
    .medicine-stats-grid.is-compact{grid-template-columns:repeat(3,minmax(0,1fr))}
    .medicine-stat{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:1rem}
    .medicine-stat span{display:block;color:#64748b;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
    .medicine-stat strong{display:block;margin-top:.35rem;color:#111827;font-size:1.15rem;font-weight:800}
    .medicine-stat small{display:block;margin-top:.15rem;color:#64748b}
    .medicine-section,.medicine-admin-note,.medicine-edit-panel{border:1px solid #e5e7eb;background:#fff;border-radius:16px;padding:1.1rem}
    .medicine-section-head,.medicine-edit-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem}
    .medicine-section h2,.medicine-admin-note h2,.medicine-edit-head h2{margin:0;color:#111827;font-size:1.05rem;font-weight:800}
    .medicine-section p,.medicine-admin-note p,.medicine-edit-head p{margin:.25rem 0 0;color:#64748b;font-size:.9rem}
    .medicine-template-card{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:1rem;border:1px solid #e5e7eb;background:#fff;border-radius:16px;padding:1rem}
    .medicine-card-title{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}
    .medicine-card-title h2{margin:0;color:#111827;font-size:1.15rem;font-weight:800}
    .medicine-card-title p{margin:.25rem 0 0;color:#64748b}
    .medicine-template-side{border-left:1px solid #e5e7eb;padding-left:1rem}
    .medicine-template-side dl{display:grid;gap:.75rem;margin:0}
    .medicine-template-side div{display:flex;justify-content:space-between;gap:.75rem}
    .medicine-template-side dt{color:#64748b;font-size:.8rem}
    .medicine-template-side dd{margin:0;color:#111827;font-weight:800;text-align:right}
    .medicine-medicine-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}
    .medicine-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:1rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .medicine-card-head{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}
    .medicine-card h3{margin:0;color:#111827;font-size:1rem;font-weight:800}
    .medicine-card .type{margin:.15rem 0 0;color:#64748b;font-size:.78rem}
    .medicine-meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:.9rem}
    .medicine-meta{border-radius:10px;background:#f8fafc;padding:.65rem}
    .medicine-meta span{display:block;color:#64748b;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
    .medicine-meta strong{display:block;margin-top:.2rem;color:#111827;font-size:.86rem}
    .medicine-timing-row{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.55rem}
    .medicine-time{border-radius:999px;background:#eef2ff;color:#3730a3;padding:.25rem .55rem;font-size:.74rem;font-weight:800}
    .medicine-instructions{margin-top:.8rem;border-radius:10px;background:#eff6ff;color:#1e3a8a;padding:.75rem;font-size:.86rem}
    .medicine-form-surface{border-radius:14px;background:#f8fafc;padding:1rem}
    .medicine-empty{border:1px dashed #cbd5e1;border-radius:14px;padding:2rem;text-align:center;color:#64748b}
    @media (max-width: 1024px){.medicine-template-card{grid-template-columns:1fr}.medicine-template-side{border-left:0;border-top:1px solid #e5e7eb;padding-left:0;padding-top:1rem}.medicine-medicine-grid{grid-template-columns:1fr}.medicine-stats-grid,.medicine-stats-grid.is-compact{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width: 640px){.medicine-page-head,.medicine-card-title,.medicine-section-head{flex-direction:column}.medicine-stats-grid,.medicine-stats-grid.is-compact{grid-template-columns:1fr}.medicine-meta-grid{grid-template-columns:1fr}}
</style>

<div class="medicine-admin-shell">
    <div class="medicine-page-head">
        <div>
            <p class="medicine-kicker">Medicine / Prescription Template</p>
            <h1>{{ $record->name }}</h1>
            <p>{{ $record->description ?: 'Reusable prescription template with medicines, auto-generated daily timings, duration, and patient instructions.' }}</p>
        </div>

        <div class="medicine-head-actions">
            <span class="medicine-pill {{ $record->is_active ? 'is-green' : 'is-gray' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span>
            <span class="medicine-pill {{ $scopeClass }}">{{ $scopeLabel }}</span>
            <span class="medicine-pill is-gray">{{ $items->count() }} medicines</span>
        </div>
    </div>

    <div class="medicine-stats-grid {{ $compact ? 'is-compact' : '' }}">
        <div class="medicine-stat"><span>Scope</span><strong>{{ $scopeLabel }}</strong><small>{{ $scopeDetail }}</small></div>
        <div class="medicine-stat"><span>Medicines</span><strong>{{ $items->count() }}</strong><small>template items</small></div>
        <div class="medicine-stat"><span>Total Doses</span><strong>{{ $totalDosesPerDay }}</strong><small>per day across all medicines</small></div>
        @unless($compact)
            <div class="medicine-stat"><span>Auto Timings</span><strong>{{ $timingCount }}</strong><small>saved timing slots</small></div>
        @endunless
        <div class="medicine-stat"><span>Longest Duration</span><strong>{{ $longestDuration ?: '-' }}</strong><small>{{ $longestDuration ? 'configured value' : 'no end date' }}</small></div>
    </div>

    @unless($compact)
        <div class="medicine-template-card">
            <div>
                <div class="medicine-card-title">
                    <div>
                        <h2>Template Setup</h2>
                        <p>{{ $scopeDetail }} · Last updated {{ $record->updated_at?->format('d M Y, h:i A') ?: '-' }}</p>
                    </div>
                    <span class="medicine-pill {{ $scopeClass }}">{{ $scopeLabel }}</span>
                </div>

                <div class="medicine-chip-row" style="margin-top:.85rem">
                    <span class="medicine-chip is-blue">Auto Timing Preview</span>
                    <span class="medicine-chip is-green">PDF refresh on assign</span>
                    <span class="medicine-chip is-gray">Patient view unchanged</span>
                    <span class="medicine-chip is-amber">Doctor can still add custom medicine</span>
                </div>
            </div>

            <aside class="medicine-template-side">
                <dl>
                    <div><dt>Status</dt><dd>{{ $record->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div><dt>Scope</dt><dd>{{ $scopeLabel }}</dd></div>
                    <div><dt>Target</dt><dd>{{ $scopeDetail }}</dd></div>
                    <div><dt>Created</dt><dd>{{ $record->created_at?->format('d M Y') ?: '-' }}</dd></div>
                </dl>
            </aside>
        </div>
    @endunless

    <div class="medicine-section">
        <div class="medicine-section-head">
            <div>
                <h2>Template Medicines</h2>
                <p>Readable prescription preview including calculated daily timings.</p>
            </div>
            <span class="medicine-pill is-gray">{{ $items->count() }} item(s)</span>
        </div>

        <div class="medicine-medicine-grid">
            @forelse($items as $index => $item)
                <div class="medicine-card">
                    <div class="medicine-card-head">
                        <div>
                            <h3>{{ $loop->iteration }}. {{ $item->medicine_name }}</h3>
                            <p class="type">{{ $item->medicine_type ?: $item->medicine?->type?->name ?: 'Type not specified' }}</p>
                        </div>
                        <span class="medicine-pill is-blue">{{ $item->doses_per_day ?: 1 }}x / day</span>
                    </div>

                    <div class="medicine-meta-grid">
                        <div class="medicine-meta"><span>Dosage</span><strong>{{ $item->dosage ?: '-' }}</strong></div>
                        <div class="medicine-meta"><span>Meal</span><strong>{{ str($item->meal_timing ?: '-')->replace('_', ' ')->title() }}</strong></div>
                        <div class="medicine-meta"><span>First Dose</span><strong>{{ $formatTime($item->first_dose_time) }}</strong></div>
                        <div class="medicine-meta"><span>Gap</span><strong>{{ $item->dose_interval_hours ?: '-' }} hour(s)</strong></div>
                        <div class="medicine-meta"><span>Duration</span><strong>{{ $item->duration_value ? $item->duration_value . ' ' . $item->duration_type : 'No end date' }}</strong></div>
                        <div class="medicine-meta"><span>Frequency</span><strong>{{ $item->frequency ?: '-' }}</strong></div>
                    </div>

                    <div class="medicine-timing-row">
                        @forelse(($item->frequency_times ?? []) as $time)
                            <span class="medicine-time">{{ $formatTime($time) }}</span>
                        @empty
                            <span class="medicine-time">No timing set</span>
                        @endforelse
                    </div>

                    @if($item->instructions)
                        <div class="medicine-instructions">{{ $item->instructions }}</div>
                    @endif
                </div>
            @empty
                <div class="medicine-empty">No medicines added to this template.</div>
            @endforelse
        </div>
    </div>
</div>
