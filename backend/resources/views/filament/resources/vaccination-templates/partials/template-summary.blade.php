@php
    /** @var \App\Models\VaccinationTemplate $record */
    $compact = $compact ?? false;
    $record->loadMissing(['doctor.user', 'program', 'items.vaccination']);

    $items = $record->items()
        ->with('vaccination')
        ->orderBy('set_sort_order')
        ->orderBy('sort_order')
        ->get();

    $targetType = $record->program?->target_type;
    $targetValue = $targetType instanceof \App\Enums\VaccinationProgramTargetType ? $targetType->value : (string) $targetType;
    $targetLabel = $targetType instanceof \App\Enums\VaccinationProgramTargetType
        ? $targetType->label()
        : str($targetValue ?: 'Target category')->replace('_', ' ')->title();

    $assignedCount = $record->patientVaccinations()->distinct('patient_id')->count('patient_id');
    $dueThisWeek = $record->patientVaccinations()
        ->whereBetween('due_date', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
        ->count();
    $overdueCount = $record->patientVaccinations()->whereIn('status', ['overdue', 'missed'])->count();

    $baseLabel = match ($targetValue) {
        'baby', 'child' => 'Assigned start date',
        'pregnancy' => 'LMP / pregnancy start date',
        default => 'Template assignment date',
    };

    $sampleStart = \Carbon\Carbon::parse('2026-01-01');
    $previousDate = $sampleStart->copy();

    $addValueUnit = function (\Carbon\Carbon $date, int $value, string $unit): \Carbon\Carbon {
        return match ($unit) {
            'weeks' => $date->copy()->addWeeks($value),
            'months' => $date->copy()->addMonths($value),
            'years' => $date->copy()->addYears($value),
            default => $date->copy()->addDays($value),
        };
    };

    $doseCards = [];
    foreach ($items as $item) {
        $timingType = $item->effectiveTimingType();
        $expectedDate = null;

        if ($timingType === 'doctor_manual_date') {
            $ruleText = 'Doctor manual date';
            $logicText = 'Doctor sets this after patient review.';
        } elseif ($timingType === 'previous_dose') {
            $value = $item->effectiveIntervalValue();
            $unit = $item->effectiveIntervalUnit();
            $expectedDate = $addValueUnit($previousDate, $value, $unit);
            $previousDate = $expectedDate->copy();
            $ruleText = "{$value} {$unit} after previous dose";
            $logicText = 'Due = previous actual completion + gap.';
        } else {
            $value = $item->effectiveOffsetValue();
            $unit = $item->effectiveOffsetUnit();
            $expectedDate = $addValueUnit($sampleStart, $value, $unit);
            $previousDate = $expectedDate->copy();
            $ruleText = "{$value} {$unit} from base date";
            $logicText = 'Due = base date + offset.';
        }

        $doseCards[] = [
            'item' => $item,
            'timing_type' => $timingType,
            'rule_text' => $ruleText,
            'logic_text' => $logicText,
            'expected_date' => $expectedDate,
        ];
    }

    $timingBadge = fn(string $type): string => match ($type) {
        'previous_dose' => 'background:#dcfce7;color:#166534;border-color:#bbf7d0;',
        'doctor_manual_date' => 'background:#fef3c7;color:#92400e;border-color:#fde68a;',
        default => 'background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe;',
    };
@endphp

<div class="vax-admin-shell">
    <div class="vax-page-head">
        <div>
            <p class="vax-kicker">Vaccination / Schedule Templates</p>
            <h1>{{ $record->name }}</h1>
            <p>{{ $record->description ?: 'Clear target-based template for registered patients and family profiles.' }}</p>
        </div>

        <div class="vax-head-actions">
            <span class="vax-pill {{ $record->is_active ? 'is-green' : 'is-gray' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span>
            <span class="vax-pill is-blue">{{ $targetLabel }}</span>
            <span class="vax-pill is-gray">{{ $items->count() }} doses</span>
        </div>
    </div>

    <div class="vax-stats-grid {{ $compact ? 'is-compact' : '' }}">
        <div class="vax-stat"><span>Assigned</span><strong>{{ $assignedCount }}</strong><small>patients</small></div>
        <div class="vax-stat"><span>Due this week</span><strong>{{ $dueThisWeek }}</strong><small>needs reminder</small></div>
        <div class="vax-stat"><span>Overdue</span><strong>{{ $overdueCount }}</strong><small>follow up</small></div>
        <div class="vax-stat"><span>Base logic</span><strong>{{ $baseLabel }}</strong><small>calculation source</small></div>
        @unless($compact)
            <div class="vax-stat"><span>Last updated</span><strong>{{ $record->updated_at?->format('d M Y') ?: '-' }}</strong><small>template change</small></div>
        @endunless
    </div>

    @if($compact)
        <div class="vax-section vax-compact-summary">
            <div class="vax-section-head">
                <div>
                    <h2>Current Dose Setup</h2>
                    <p>Quick check before editing. Full read-only details are available on the view page.</p>
                </div>
                <span class="vax-pill is-gray">{{ $items->count() }} doses</span>
            </div>

            <div class="vax-dose-grid is-compact">
                @forelse(array_slice($doseCards, 0, 4) as $card)
                    @php($item = $card['item'])
                    <div class="vax-dose-mini">
                        <strong>Dose {{ $item->dose_no }}</strong>
                        <span>{{ $item->vaccination?->name ?: 'Unknown vaccine' }}</span>
                        <small>{{ $card['rule_text'] }}</small>
                    </div>
                @empty
                    <div class="vax-empty-mini">No doses added yet.</div>
                @endforelse
            </div>
        </div>
    @else
    <div class="vax-template-card">
        <div class="vax-template-main">
            <div class="vax-card-title">
                <div>
                    <h2>{{ $record->name }}</h2>
                    <p>Target: {{ $targetLabel }} · Calculation based on {{ strtolower($baseLabel) }}</p>
                </div>
                <span class="vax-pill {{ $record->is_active ? 'is-green' : 'is-gray' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span>
            </div>

            <div class="vax-chip-row">
                <span class="vax-chip is-blue">{{ $targetLabel }}</span>
                <span class="vax-chip is-gray">{{ $items->count() }} doses</span>
                <span class="vax-chip is-amber">NotificationService</span>
                <span class="vax-chip is-green">Doctor override allowed</span>
            </div>

            <div class="vax-dose-grid">
                @forelse($doseCards as $card)
                    @php($item = $card['item'])
                    <div class="vax-dose-mini">
                        <strong>Dose {{ $item->dose_no }}</strong>
                        <span>{{ $item->vaccination?->name ?: 'Unknown vaccine' }}</span>
                        <small>{{ $card['rule_text'] }}</small>
                    </div>
                @empty
                    <div class="vax-empty-mini">No doses added yet.</div>
                @endforelse
            </div>
        </div>

        <aside class="vax-template-side">
            <dl>
                <div><dt>Assigned</dt><dd>{{ $assignedCount }} patients</dd></div>
                <div><dt>Due this week</dt><dd>{{ $dueThisWeek }}</dd></div>
                <div><dt>Overdue</dt><dd>{{ $overdueCount }}</dd></div>
                <div><dt>Last updated</dt><dd>{{ $record->updated_at?->format('d M Y') ?: '-' }}</dd></div>
            </dl>
        </aside>
    </div>

    <div class="vax-section">
        <div class="vax-section-head">
            <div>
                <h2>Dose Rules</h2>
                <p>Each dose shows timing type, configuration, and system logic.</p>
            </div>
        </div>

        <div class="vax-dose-table">
            <div class="vax-dose-row is-head">
                <span>Dose</span>
                <span>Vaccine</span>
                <span>Timing Type</span>
                <span>Configuration</span>
                <span>System Logic</span>
            </div>
            @forelse($doseCards as $card)
                @php($item = $card['item'])
                <div class="vax-dose-row">
                    <span><strong>Dose {{ $item->dose_no }}</strong></span>
                    <span>{{ $item->vaccination?->name ?: '-' }}</span>
                    <span><em style="{{ $timingBadge($card['timing_type']) }}">{{ str($card['timing_type'])->replace('_', ' ')->title() }}</em></span>
                    <span>{{ $card['rule_text'] }}<br><small>Grace: -{{ $item->grace_period_before_days ?? 0 }}d / +{{ $item->grace_period_after_days ?? 0 }}d</small></span>
                    <span>{{ $card['logic_text'] }}</span>
                </div>
            @empty
                <div class="vax-empty-state">No dose rules added yet.</div>
            @endforelse
        </div>
    </div>

    <div class="vax-two-grid">
        <div class="vax-section">
            <h2>Notification Rules</h2>
            <div class="vax-note-box">Use existing <strong>NotificationService</strong> for assigned, due soon, due today, overdue, missed, completed, rescheduled, and doctor remark alerts.</div>
            <div class="vax-chip-row">
                <span class="vax-chip is-blue">{{ $record->reminder_1_days_before ?? 7 }} days before</span>
                <span class="vax-chip is-blue">{{ $record->reminder_2_days_before ?? 3 }} days before</span>
                <span class="vax-chip is-blue">{{ $record->reminder_3_days_before ?? 1 }} day before</span>
                <span class="vax-chip is-amber">Due today</span>
                <span class="vax-chip is-rose">Overdue every {{ $record->overdue_alert_days_after ?? 1 }} day(s)</span>
            </div>
        </div>

        <div class="vax-section">
            <h2>Doctor Permissions</h2>
            <div class="vax-chip-row">
                @foreach(['Can assign', 'Can reschedule', 'Can complete', 'Reason required', 'Can add booster', 'Can skip with reason'] as $permission)
                    <span class="vax-chip {{ str_contains($permission, 'Reason') || str_contains($permission, 'skip') ? 'is-rose' : 'is-green' }}">{{ $permission }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="vax-section">
        <div class="vax-section-head">
            <div>
                <h2>Template Calculation Preview</h2>
                <p>Sample base date: 01 Jan 2026. Doctors see patient-specific dates before assignment.</p>
            </div>
        </div>
        <div class="vax-preview-list">
            @foreach($doseCards as $card)
                @php($item = $card['item'])
                <div class="vax-preview-item">
                    <strong>Dose {{ $item->dose_no }} - {{ $item->vaccination?->name ?: 'Unknown vaccine' }}</strong>
                    <span>{{ $card['expected_date'] ? 'Expected: '.$card['expected_date']->format('d M Y') : 'Expected: Doctor manual date' }} · {{ $card['rule_text'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
