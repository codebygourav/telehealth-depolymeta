@php
    $record = $getState();
    $record->loadMissing(['patientProfile.patient', 'vaccinationProgram', 'vaccinationTemplate', 'doctor.user', 'patientVaccinations.vaccination']);

    $patientName = trim(($record->patientProfile?->patient?->first_name ?? '').' '.($record->patientProfile?->patient?->last_name ?? '')) ?: '—';
    $profileName = $record->patientProfile?->name ?? '—';
    $doctorName = trim(($record->doctor?->first_name ?? '').' '.($record->doctor?->last_name ?? '')) ?: '—';
    
    $status = $record->status instanceof \App\Enums\PatientVaccinationProgramStatus 
        ? $record->status 
        : \App\Enums\PatientVaccinationProgramStatus::tryFrom((string)$record->status) ?? \App\Enums\PatientVaccinationProgramStatus::ACTIVE;

    $statusColor = match($status->value) {
        'active' => 'emerald',
        'completed' => 'blue',
        'cancelled' => 'rose',
        default => 'gray',
    };

    // Group patient vaccinations by set name
    $doses = $record->patientVaccinations()
        ->with('vaccination')
        ->orderBy('set_sort_order')
        ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
        ->get();

    $groupedSets = [];
    foreach ($doses as $dose) {
        $setName = $dose->set_name ?? 'General';
        if (!isset($groupedSets[$setName])) {
            $groupedSets[$setName] = [
                'name' => $setName,
                'sort_order' => $dose->set_sort_order ?? 0,
                'doses' => [],
            ];
        }
        $groupedSets[$setName]['doses'][] = $dose;
    }
    $groupedSets = array_values($groupedSets);
@endphp

<div class="space-y-6">
    <!-- Summary Header Card -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-{{ $statusColor }}-50/40 to-transparent dark:from-{{ $statusColor }}-950/10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-{{ $statusColor }}-600 dark:bg-{{ $statusColor }}-500 flex items-center justify-center shadow-lg shadow-{{ $statusColor }}-500/10 shrink-0 mt-0.5">
                        <x-heroicon-o-clipboard-document-check class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <span class="text-xs font-bold text-{{ $statusColor }}-700 dark:text-{{ $statusColor }}-400 uppercase tracking-widest">
                            Assigned Vaccination Schedule
                        </span>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $record->vaccinationTemplate?->name ?? 'Custom Schedule' }}
                        </h2>
                        
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <x-heroicon-m-user class="w-4 h-4 text-gray-400" />
                                <span>Patient: <strong class="text-gray-700 dark:text-gray-300">{{ $patientName }} ({{ $profileName }})</strong></span>
                            </div>
                            @if($record->doctor)
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-user-circle class="w-4 h-4 text-gray-400" />
                                    <span>Assigned By: <strong class="text-gray-700 dark:text-gray-300">Dr. {{ $doctorName }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-150 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-250 dark:border-gray-700">
                        Start Date: <strong>{{ $record->start_date?->format('d M Y') }}</strong>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-{{ $statusColor }}-50 dark:bg-{{ $statusColor }}-950/30 text-{{ $statusColor }}-700 dark:text-{{ $statusColor }}-400 border border-{{ $statusColor }}-200 dark:border-{{ $statusColor }}-800 rounded-full shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-{{ $statusColor }}-600 dark:bg-{{ $statusColor }}-500 animate-pulse"></span>
                        {{ $status->label() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline of Doses -->
    <div class="space-y-6">
        <h3 class="text-base font-bold text-gray-900 dark:text-white uppercase tracking-wider">Vaccination Timeline & Administration Status</h3>

        @if(empty($groupedSets))
            <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                No vaccine doses have been generated for this schedule.
            </div>
        @else
            <div class="relative border-l border-gray-200 dark:border-gray-800 ml-4 md:ml-6 space-y-8 pb-4">
                @foreach($groupedSets as $setIndex => $set)
                    <!-- Set Header Section -->
                    <div class="relative pl-6 md:pl-8">
                        <div class="absolute -left-[13px] top-1.5 w-6 h-6 rounded-full bg-white dark:bg-gray-950 border-2 border-primary-600 dark:border-primary-500 flex items-center justify-center shadow-sm z-10">
                            <span class="text-[10px] font-black text-primary-700 dark:text-primary-400">{{ $setIndex + 1 }}</span>
                        </div>

                        <!-- Set Title Card -->
                        <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800/80 mb-4">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white leading-snug">{{ $set['name'] }}</h4>
                        </div>

                        <!-- Doses Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($set['doses'] as $dose)
                                @php
                                    $dStatus = $dose->status instanceof \App\Enums\VaccinationStatus 
                                        ? $dose->status 
                                        : \App\Enums\VaccinationStatus::tryFrom((string)$dose->status) ?? \App\Enums\VaccinationStatus::PENDING;

                                    $dColor = match($dStatus->value) {
                                        'completed' => 'emerald',
                                        'scheduled' => 'blue',
                                        'pending' => 'amber',
                                        'missed', 'cancelled' => 'rose',
                                        default => 'gray',
                                    };
                                @endphp
                                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow transition relative overflow-hidden group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                                                Dose {{ $dose->dose_no }}
                                            </p>
                                            <h5 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
                                                {{ $dose->vaccination?->name ?: 'Unknown Vaccine' }}
                                            </h5>
                                        </div>
                                        
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold bg-{{ $dColor }}-50 dark:bg-{{ $dColor }}-950/20 text-{{ $dColor }}-700 dark:text-{{ $dColor }}-400 border border-{{ $dColor }}-100 dark:border-{{ $dColor }}-900/50 rounded-full">
                                            {{ $dStatus->label() }}
                                        </span>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-150 dark:border-gray-800/60 text-xs space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Scheduled Date:</span>
                                            <span class="font-semibold text-gray-800 dark:text-gray-300">
                                                {{ $dose->scheduled_date ? $dose->scheduled_date->format('d M Y') : '—' }}
                                            </span>
                                        </div>

                                        @if($dose->completed_date)
                                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                                <span>Administered Date:</span>
                                                <span class="font-bold">
                                                    {{ $dose->completed_date->format('d M Y') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($dose->batch_number)
                                            <div class="flex justify-between text-[11px] text-gray-400">
                                                <span>Batch Info:</span>
                                                <span>#{{ $dose->batch_number }} ({{ $dose->manufacturer }})</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
