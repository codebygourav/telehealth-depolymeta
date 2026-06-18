@php
    $record = $getState();
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
        : str($targetValue ?: 'category')->replace('_', ' ')->title();

    $assignedCount = $record->patientVaccinations()->distinct('patient_id')->count('patient_id');
    $dueThisWeek = $record->patientVaccinations()
        ->whereBetween('due_date', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
        ->count();
    $overdueCount = $record->patientVaccinations()
        ->whereIn('status', ['overdue', 'missed'])
        ->count();

    $doctorName = trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? ''))
        ?: ($record->doctor?->name ?? 'Any assigned doctor');

    $baseLabel = match ($targetValue) {
        'baby', 'child' => 'Assigned start date',
        'pregnancy' => 'LMP / pregnancy start date',
        default => 'Assignment / category start date',
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
        $ruleText = '';
        $logicText = '';

        if ($timingType === 'doctor_manual_date') {
            $ruleText = 'Doctor manual date';
            $logicText = 'Doctor sets this date after checking patient condition.';
        } elseif ($timingType === 'previous_dose') {
            $value = $item->effectiveIntervalValue();
            $unit = $item->effectiveIntervalUnit();
            $expectedDate = $addValueUnit($previousDate, $value, $unit);
            $previousDate = $expectedDate->copy();
            $ruleText = "{$value} {$unit} after previous dose";
            $logicText = 'Due date = previous actual completed date + gap.';
        } else {
            $value = $item->effectiveOffsetValue();
            $unit = $item->effectiveOffsetUnit();
            $expectedDate = $addValueUnit($sampleStart, $value, $unit);
            $previousDate = $expectedDate->copy();
            $ruleText = "{$value} {$unit} from base date";
            $logicText = 'Due date = base date + offset.';
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
        'previous_dose' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800',
        'doctor_manual_date' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800',
        default => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-800',
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Vaccination / Schedule Templates</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $record->name }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">
                {{ $record->description ?: 'Clear category-based template. Doctors assign this to a registered patient or family profile, then the system calculates patient-specific due dates.' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $record->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                {{ $record->is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950/30 dark:text-primary-300">
                {{ $targetLabel }}
            </span>
            <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                {{ $items->count() }} doses
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500">Category</p>
            <p class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $record->program?->name ?: 'Uncategorized' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500">Assigned Patients</p>
            <p class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $assignedCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500">Due This Week</p>
            <p class="mt-2 text-xl font-bold text-amber-600">{{ $dueThisWeek }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500">Overdue / Missed</p>
            <p class="mt-2 text-xl font-bold text-rose-600">{{ $overdueCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium text-gray-500">Last Updated</p>
            <p class="mt-2 text-sm font-bold text-gray-950 dark:text-white">{{ $record->updated_at?->format('d M Y') ?: '-' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">Dose Rules</x-slot>
                <x-slot name="description">Only the fields needed for each timing type are shown. Units are clear: days, weeks, months, or years.</x-slot>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @forelse($doseCards as $card)
                        @php($item = $card['item'])
                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-gray-500">Dose {{ $item->dose_no }}</p>
                                    <h3 class="mt-1 text-sm font-bold text-gray-950 dark:text-white">{{ $item->vaccination?->name ?: 'Unknown vaccine' }}</h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $item->set_name ?: 'General schedule' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $timingBadge($card['timing_type']) }}">
                                    {{ str($card['timing_type'])->replace('_', ' ')->title() }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="font-semibold text-gray-500">Configuration</p>
                                    <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $card['rule_text'] }}</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="font-semibold text-gray-500">System Logic</p>
                                    <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $card['logic_text'] }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-semibold">
                                @if($item->recommended_age_label)
                                    <span class="rounded-md bg-blue-50 px-2 py-1 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $item->recommended_age_label }}</span>
                                @endif
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Grace -{{ $item->grace_period_before_days ?? 0 }}d / +{{ $item->grace_period_after_days ?? 0 }}d</span>
                                @if($item->minimum_age_days || $item->maximum_age_days)
                                    <span class="rounded-md bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Age {{ $item->minimum_age_days ?? 0 }}d - {{ $item->maximum_age_days ?? 'no max' }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-800">
                            No dose rules added yet.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Notification Rules</x-slot>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
                        Use existing <strong>NotificationService</strong> for assigned, due soon, due today, overdue, missed, completed, rescheduled, and doctor remark alerts.
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-bold">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">{{ $record->reminder_1_days_before ?? 7 }} days before</span>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">{{ $record->reminder_2_days_before ?? 3 }} days before</span>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">{{ $record->reminder_3_days_before ?? 1 }} day before</span>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">Due today</span>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Overdue every {{ $record->overdue_alert_days_after ?? 1 }} day(s)</span>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Doctor Permissions</x-slot>
                    <div class="flex flex-wrap gap-2 text-[10px] font-bold">
                        @foreach(['Can assign', 'Can reschedule', 'Can complete', 'Can add remark', 'Can add booster', 'Can put on hold', 'Can skip with reason'] as $permission)
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">{{ $permission }}</span>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">Template Calculation Preview</x-slot>
                <x-slot name="description">Sample base date: 01 Jan 2026. This is what the doctor sees before assigning to a patient.</x-slot>

                <div class="relative border-l border-primary-600/30 pl-5">
                    @foreach($doseCards as $card)
                        @php($item = $card['item'])
                        <div class="relative mb-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm last:mb-0 dark:border-gray-800 dark:bg-gray-900">
                            <span class="absolute -left-[26px] top-5 h-3 w-3 rounded-full border-2 border-primary-600 bg-white dark:bg-gray-950"></span>
                            <p class="text-sm font-bold text-gray-950 dark:text-white">Dose {{ $item->dose_no }} - {{ $item->vaccination?->name ?: 'Unknown vaccine' }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $card['expected_date'] ? 'Expected: '.$card['expected_date']->format('d M Y') : 'Expected: Doctor manual date' }}
                                · {{ $card['rule_text'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-950 dark:text-white">Assignment Summary</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Assigned</span><strong>{{ $assignedCount }} patients</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Due this week</span><strong>{{ $dueThisWeek }}</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Overdue</span><strong>{{ $overdueCount }}</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Last updated</span><strong>{{ $record->updated_at?->format('d M Y') ?: '-' }}</strong></div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-950 dark:text-white">Base Date Logic</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $baseLabel }}</p>
                <p class="mt-3 text-xs text-gray-500">Baby/child uses DOB. Pregnancy uses LMP. Adult, elderly, travel, and staff use assignment date.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-950 dark:text-white">Doctor View</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Doctor sees active templates only, previews calculated values, assigns directly to the appointment patient account, then manages completion, reschedule, hold, skip, remarks, and booster doses.</p>
                <p class="mt-3 text-xs font-semibold text-gray-500">Default doctor: Dr. {{ $doctorName }}</p>
            </div>
        </aside>
    </div>
</div>
