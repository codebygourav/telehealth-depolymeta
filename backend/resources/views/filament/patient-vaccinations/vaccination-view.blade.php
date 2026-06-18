@php
    $record = $getState();
    $record->loadMissing(['patient.user', 'doctor.user', 'vaccination', 'template.program', 'documents', 'logs.performedBy']);

    $patientName = trim(($record->patient?->first_name ?? '').' '.($record->patient?->last_name ?? '')) ?: '—';
    $doctorName = trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? '')) ?: '—';
    $vaccineName = $record->vaccination?->name ?? '—';

    $status = $record->status instanceof \App\Enums\VaccinationStatus
        ? $record->status
        : \App\Enums\VaccinationStatus::tryFrom((string)$record->status) ?? \App\Enums\VaccinationStatus::PENDING;

    $effectiveStatus = $status->value;
    if (
        in_array($status->value, ['pending', 'scheduled'], true)
        && $record->scheduled_date
        && $record->scheduled_date->isPast()
        && ! $record->scheduled_date->isToday()
    ) {
        $effectiveStatus = 'overdue';
    }

    $statusColor = match($effectiveStatus) {
        'completed' => 'emerald',
        'scheduled', 'upcoming', 'due_soon', 'rescheduled' => 'blue',
        'pending', 'due_today', 'on_hold', 'skipped_by_doctor' => 'amber',
        'overdue' => 'rose',
        'missed', 'cancelled' => 'rose',
        default => 'gray',
    };
    $statusLabel = \App\Enums\VaccinationStatus::tryFrom($effectiveStatus)?->label() ?? str($effectiveStatus)->replace('_', ' ')->title();
    $showCompletedDate = $effectiveStatus === \App\Enums\VaccinationStatus::COMPLETED->value;
    $showOverdueDate = in_array($effectiveStatus, [\App\Enums\VaccinationStatus::OVERDUE->value, \App\Enums\VaccinationStatus::MISSED->value], true);
    $showMissedDate = $effectiveStatus === \App\Enums\VaccinationStatus::MISSED->value;
    $showChangedDate = filled($record->changed_date);

    $colorMap = [
        'emerald' => [
            'bgGradient' => 'from-emerald-50/40 dark:from-emerald-950/10',
            'bgIcon' => 'bg-emerald-600 dark:bg-emerald-500 shadow-emerald-500/10',
            'textHeader' => 'text-emerald-700 dark:text-emerald-400',
            'badge' => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
            'dot' => 'bg-emerald-600 dark:bg-emerald-500',
        ],
        'blue' => [
            'bgGradient' => 'from-blue-50/40 dark:from-blue-950/10',
            'bgIcon' => 'bg-blue-600 dark:bg-blue-500 shadow-blue-500/10',
            'textHeader' => 'text-blue-700 dark:text-blue-400',
            'badge' => 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
            'dot' => 'bg-blue-600 dark:bg-blue-500',
        ],
        'amber' => [
            'bgGradient' => 'from-amber-50/40 dark:from-amber-950/10',
            'bgIcon' => 'bg-amber-600 dark:bg-amber-500 shadow-amber-500/10',
            'textHeader' => 'text-amber-700 dark:text-amber-400',
            'badge' => 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
            'dot' => 'bg-amber-600 dark:bg-amber-500',
        ],
        'rose' => [
            'bgGradient' => 'from-rose-50/40 dark:from-rose-950/10',
            'bgIcon' => 'bg-rose-600 dark:bg-rose-500 shadow-rose-500/10',
            'textHeader' => 'text-rose-700 dark:text-rose-400',
            'badge' => 'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800',
            'dot' => 'bg-rose-600 dark:bg-rose-500',
        ],
        'gray' => [
            'bgGradient' => 'from-gray-50/40 dark:from-gray-950/10',
            'bgIcon' => 'bg-gray-600 dark:bg-gray-500 shadow-gray-500/10',
            'textHeader' => 'text-gray-700 dark:text-gray-400',
            'badge' => 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-700',
            'dot' => 'bg-gray-600 dark:bg-gray-500',
        ],
    ];

    $classes = $colorMap[$statusColor] ?? $colorMap['gray'];
@endphp

<div class="space-y-6">
    <!-- Header Block -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 bg-linear-to-r {{ $classes['bgGradient'] }} to-transparent">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl {{ $classes['bgIcon'] }} flex items-center justify-center shrink-0 mt-0.5">
                        <x-heroicon-o-shield-check class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold {{ $classes['textHeader'] }} uppercase tracking-widest">
                                Dose {{ $record->dose_no }}
                            </span>
                            @if($record->set_name)
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-gray-150 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md">
                                    {{ $record->set_name }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $vaccineName }}</h2>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <x-heroicon-m-user class="w-4 h-4 text-gray-400" />
                                <span>Patient: <strong class="text-gray-700 dark:text-gray-300">{{ $patientName }}</strong></span>
                            </div>
                            @if($record->doctor)
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-user-circle class="w-4 h-4 text-gray-400" />
                                    <span>Prescribing Doctor: <strong class="text-gray-700 dark:text-gray-300">Dr. {{ $doctorName }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold {{ $classes['badge'] }} rounded-full shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full {{ $classes['dot'] }} animate-pulse"></span>
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Details Column (2/3 width) -->
        <div class="md:col-span-2 space-y-6">
            <!-- Administration Record -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-clipboard-document-check class="w-5 h-5 text-gray-500" />
                        <span>Administration & Dosage Details</span>
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Route of Administration</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->route ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Anatomical Site</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->site ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Dose Amount</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->dose_amount ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Manufacturer & Brand</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->manufacturer ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Batch / Lot Number</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->batch_number ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Administered By</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->given_by ?: '—' }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Clinic / Location</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $record->given_at ?: '—' }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <!-- Calculation and Doctor Override Logic -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-adjustments-horizontal class="w-5 h-5 text-gray-500" />
                        <span>Calculation & Doctor Override</span>
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-4 bg-gray-50/60 dark:bg-gray-900/30">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Base Logic</h4>
                        <p class="mt-2 text-gray-700 dark:text-gray-300">
                            @php
                                $programType = $record->template?->program?->target_type?->value ?? $record->template?->program?->target_type;
                                $baseLogic = match($programType) {
                                    'baby', 'child' => 'DOB based: calculated from selected patient/family profile birth date.',
                                    'pregnancy' => 'Pregnancy based: calculated from LMP / pregnancy start date.',
                                    default => 'Assignment based: calculated from category start / template assignment date.',
                                };
                            @endphp
                            {{ $baseLogic }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-4 bg-gray-50/60 dark:bg-gray-900/30">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Doctor Actions Allowed</h4>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach(['Reschedule', 'Mark Completed', 'Skip with Reason', 'Put On Hold', 'Add Remark', 'Add Booster / Extra Dose'] as $action)
                                <span class="px-2 py-1 text-[10px] font-semibold rounded-md bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300">{{ $action }}</span>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">Each save creates an audit log below with old value, new value, user, date, and reason when provided.</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Expected Date</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $record->expected_date ? $record->expected_date->format('d M Y') : 'Doctor manual date' }}</p>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Current Due Date</h4>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $record->due_date ? $record->due_date->format('d M Y') : 'Not set' }}</p>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Doctor Changed Date</h4>
                        <p class="text-sm font-semibold text-amber-600 dark:text-amber-400 mt-1">{{ $record->changed_date ? $record->changed_date->format('d M Y') : 'No override' }}</p>
                    </div>
                </div>

                @if($record->skipped_reason || $record->on_hold_reason || ($showMissedDate && $record->doctor_notes))
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($showMissedDate && $record->doctor_notes)
                            <div class="rounded-lg border border-rose-200 dark:border-rose-900 bg-rose-50/60 dark:bg-rose-950/20 p-3 text-xs text-rose-800 dark:text-rose-300">
                                <strong>Missed Remark:</strong> {{ $record->doctor_notes }}
                            </div>
                        @endif
                        @if($record->skipped_reason)
                            <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50/60 dark:bg-amber-950/20 p-3 text-xs text-amber-800 dark:text-amber-300">
                                <strong>Skipped Reason:</strong> {{ $record->skipped_reason }}
                            </div>
                        @endif
                        @if($record->on_hold_reason)
                            <div class="rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50/60 dark:bg-blue-950/20 p-3 text-xs text-blue-800 dark:text-blue-300">
                                <strong>On Hold Reason:</strong> {{ $record->on_hold_reason }}
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::section>

            <!-- Notes and Reaction -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-chat-bubble-left-ellipsis class="w-5 h-5 text-gray-500" />
                        <span>Clinical Notes & Reactions</span>
                    </div>
                </x-slot>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Doctor Notes</h4>
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                            {{ $record->doctor_notes ?: 'No notes recorded.' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Side Effects Observed</h4>
                            <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-rose-50/10 dark:bg-rose-950/10 p-3 rounded-lg border border-rose-100/50 dark:border-rose-900/20 min-h-16">
                                {{ $record->side_effect_observed ?: 'No adverse reactions noted.' }}
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Patient Reaction Description</h4>
                            <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800 min-h-16">
                                {{ $record->patient_reaction ?: 'No patient reaction reported.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Sidebar Column (1/3 width) -->
        <div class="space-y-6">
            <!-- Schedule Status & Dates -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-calendar class="w-5 h-5 text-gray-500" />
                        <span>Schedule Timeline</span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Expected Date</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->expected_date ? $record->expected_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Assigned Date</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->assigned_date ? $record->assigned_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Due Date</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->due_date ? $record->due_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    @if($showChangedDate)
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Changed Date</span>
                            <span class="text-sm font-semibold text-amber-600">
                                {{ $record->changed_date->format('d M Y') }}
                            </span>
                        </div>
                    @endif

                    @if($showCompletedDate)
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Completed Date</span>
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $record->completed_date ? $record->completed_date->format('d M Y') : '—' }}
                            </span>
                        </div>
                    @endif

                    @if($showOverdueDate)
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Overdue Date</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $record->overdue_date ? $record->overdue_date->format('d M Y') : '—' }}
                            </span>
                        </div>
                    @endif

                    @if($showMissedDate)
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Missed Date</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $record->missed_date ? $record->missed_date->format('d M Y') : '—' }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 pt-2">
                        <span>Grace period (before/after):</span>
                        <span>-{{ $record->grace_period_before_days ?? 0 }}d / +{{ $record->grace_period_after_days ?? 0 }}d</span>
                    </div>
                </div>
            </x-filament::section>

            <!-- Linked Template & Category -->
            @if($record->template)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-link class="w-5 h-5 text-gray-500" />
                            <span>Linked Template</span>
                        </div>
                    </x-slot>

                    <div class="space-y-3">
                        <div>
                            <h5 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Template Name</h5>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ $record->template->name }}
                            </p>
                        </div>
                        @if($record->template->program)
                            <div>
                                <h5 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Category / Target</h5>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {{ $record->template->program->name }}
                                </p>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endif

            <!-- Documents & Certificates -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-paper-clip class="w-5 h-5 text-gray-500" />
                        <span>Attached Files ({{ $record->documents->count() }})</span>
                    </div>
                </x-slot>

                @if($record->documents->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($record->documents as $doc)
                            @php
                                $docUrl = $doc->document ? storage_url($doc->document) : '#';
                                $docLabel = $doc->document_type instanceof \App\Enums\VaccinationDocumentType
                                    ? $doc->document_type->label()
                                    : ucfirst((string)$doc->document_type);
                            @endphp
                            <a href="{{ $docUrl }}" target="_blank" class="flex items-center gap-2 p-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-950 dark:hover:bg-gray-900 rounded-lg border border-gray-150 dark:border-gray-800 transition">
                                <x-heroicon-o-document-text class="w-5 h-5 text-primary-500 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $docLabel }}
                                    </p>
                                    @if($doc->certificate_number)
                                        <p class="text-[9px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                                            #{{ $doc->certificate_number }}
                                        </p>
                                    @endif
                                </div>
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-gray-400" />
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-xs text-gray-400 dark:text-gray-500 italic bg-gray-50/50 dark:bg-gray-900/30 rounded-lg border border-dashed border-gray-200 dark:border-gray-800">
                        No certificates or uploads attached to this dose.
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>

    <!-- Audit History Logs -->
    <x-filament::section class="mt-6">
        <details class="group" id="dose-audit-history">
            <summary class="list-none cursor-pointer flex items-center justify-between gap-3 py-1">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-clock class="w-5 h-5 text-gray-500" />
                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Dose Audit History Logs</span>
                </div>
                <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" />
            </summary>

            <div class="mt-4">
                @if($record->logs->isNotEmpty())
                    <div class="relative border-l border-gray-200 dark:border-gray-800 ml-3 space-y-4 pb-2">
                        @foreach($record->logs as $log)
                            <div class="relative pl-6 group">
                                <div class="absolute -left-1.5 top-1.5 w-3 h-3 rounded-full bg-white dark:bg-gray-950 border-2 border-primary-600 dark:border-primary-500 flex items-center justify-center"></div>

                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">
                                        {{ $log->performedBy ? $log->performedBy->name : 'System' }}
                                    </span>
                                    performed
                                    <span class="font-semibold text-primary-600 dark:text-primary-400">
                                        {{ str_replace('updated_', 'updated ', $log->action) }}
                                    </span>
                                    on {{ $log->created_at->format('d M Y, h:i A') }}
                                </div>

                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                    @if($log->old_value !== null && $log->old_value !== '')
                                        <p>Old: <span class="font-mono bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">{{ $log->old_value }}</span></p>
                                    @endif
                                    @if($log->new_value !== null && $log->new_value !== '')
                                        <p>New: <span class="font-mono bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">{{ $log->new_value }}</span></p>
                                    @endif
                                    @if($log->reason)
                                        <p class="italic text-gray-500 dark:text-gray-500 mt-1">Reason: "{{ $log->reason }}"</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500 italic">No history logs recorded yet.</p>
                @endif
            </div>
        </details>
    </x-filament::section>
</div>
