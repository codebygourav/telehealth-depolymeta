@php
    $record = $getState();
    $record->loadMissing(['patient.user', 'patientProfile', 'doctor.user', 'vaccination', 'template.program', 'documents']);

    $patientName = trim(($record->patient?->first_name ?? '').' '.($record->patient?->last_name ?? '')) ?: '—';
    $profileName = $record->patientProfile?->name ?? '—';
    $doctorName = trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? '')) ?: '—';
    $vaccineName = $record->vaccination??->name ?? '—';
    
    $status = $record->status instanceof \App\Enums\VaccinationStatus 
        ? $record->status 
        : \App\Enums\VaccinationStatus::tryFrom((string)$record->status) ?? \App\Enums\VaccinationStatus::PENDING;

    $statusColor = match($status->value) {
        'completed' => 'emerald',
        'scheduled' => 'blue',
        'pending' => 'amber',
        'missed', 'cancelled' => 'rose',
        default => 'gray',
    };

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
        <div class="p-6 bg-gradient-to-r {{ $classes['bgGradient'] }} to-transparent">
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
                                <span>Patient: <strong class="text-gray-700 dark:text-gray-300">{{ $patientName }} ({{ $profileName }})</strong></span>
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
                        {{ $status->label() }}
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
                            <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-rose-50/10 dark:bg-rose-950/10 p-3 rounded-lg border border-rose-100/50 dark:border-rose-900/20 min-h-[4rem]">
                                {{ $record->side_effect_observed ?: 'No adverse reactions noted.' }}
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Patient Reaction Description</h4>
                            <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800 min-h-[4rem]">
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
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Scheduled Date</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->scheduled_date ? $record->scheduled_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">First Dose Date</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->first_dose_date ? $record->first_dose_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 uppercase font-semibold">Completed Date</span>
                        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $record->completed_date ? $record->completed_date->format('d M Y') : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 pt-2">
                        <span>Offset Days saved:</span>
                        <span>{{ $record->due_after_days ?? 0 }} days ({{ $record->due_after_months ?? 0 }} months)</span>
                    </div>
                </div>
            </x-filament::section>

            <!-- Linked Template & Program -->
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
                                <h5 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Program Target</h5>
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
</div>
