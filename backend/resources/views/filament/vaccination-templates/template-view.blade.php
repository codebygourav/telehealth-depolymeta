@php
    $record = $getState();
    $record->loadMissing(['doctor.user', 'program', 'items.vaccination']);

    // Group items by set
    $items = $record->items()
        ->orderBy('set_sort_order')
        ->orderBy('sort_order')
        ->get();

    $groupedSets = [];
    foreach ($items as $item) {
        $setName = $item->set_name ?? 'General';
        if (!isset($groupedSets[$setName])) {
            $groupedSets[$setName] = [
                'name' => $setName,
                'description' => $item->set_description,
                'sort_order' => $item->set_sort_order ?? 0,
                'doses' => [],
            ];
        }
        $groupedSets[$setName]['doses'][] = $item;
    }
    $groupedSets = array_values($groupedSets);
@endphp

<div class="space-y-6">
    <!-- Header Summary Card -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-primary-50/50 to-transparent dark:from-primary-950/20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-600 dark:bg-primary-500 flex items-center justify-center shadow-lg shadow-primary-500/10 shrink-0 mt-0.5">
                        <x-heroicon-o-clipboard-document-list class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->name }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $record->description ?: 'No description provided for this template.' }}</p>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-gray-500 dark:text-gray-400">
                            @if($record->program)
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-rectangle-group class="w-4 h-4 text-gray-400" />
                                    <span>Program: <strong class="text-gray-700 dark:text-gray-300">{{ $record->program->name }}</strong></span>
                                </div>
                            @endif
                            @if($record->doctor)
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-user class="w-4 h-4 text-gray-400" />
                                    <span>Doctor: <strong class="text-gray-700 dark:text-gray-300">Dr. {{ $record->doctor->first_name }} {{ $record->doctor->last_name }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-700">
                        {{ count($items) }} Doses
                    </span>
                    @if($record->is_active)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-500"></span>
                            Active Template
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline of Sets -->
    <div class="space-y-6">
        <h3 class="text-base font-bold text-gray-900 dark:text-white uppercase tracking-wider">Schedule Timeline</h3>

        @if(empty($groupedSets))
            <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                No vaccine doses added to this template. Edit the template to add sets and doses.
            </div>
        @else
            <div class="relative border-l border-gray-200 dark:border-gray-800 ml-4 md:ml-6 space-y-8 pb-4">
                @foreach($groupedSets as $setIndex => $set)
                    <!-- Set Block -->
                    <div class="relative pl-6 md:pl-8 group">
                        <!-- Timeline Dot -->
                        <div class="absolute -left-[13px] top-1.5 w-6 h-6 rounded-full bg-white dark:bg-gray-950 border-2 border-primary-600 dark:border-primary-500 flex items-center justify-center shadow-sm z-10">
                            <span class="text-[10px] font-black text-primary-700 dark:text-primary-400">{{ $setIndex + 1 }}</span>
                        </div>

                        <!-- Set Header Card -->
                        <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800/80 mb-4">
                            <div class="flex items-center justify-between gap-4">
                                <h4 class="text-base font-bold text-gray-900 dark:text-white leading-snug">{{ $set['name'] }}</h4>
                            </div>
                            @if($set['description'])
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{{ $set['description'] }}</p>
                            @endif
                        </div>

                        <!-- Doses Cards inside the Set -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($set['doses'] as $dose)
                                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow transition relative overflow-hidden group">
                                    <div class="absolute right-0 top-0 w-24 h-24 bg-primary-50/50 dark:bg-primary-950/10 rounded-full translate-x-8 -translate-y-8 -z-10 group-hover:scale-110 transition duration-300"></div>

                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wide">
                                                Dose {{ $dose->dose_no }}
                                            </p>
                                            <h5 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
                                                {{ $dose->vaccination?->name ?: 'Unknown Vaccine' }}
                                            </h5>
                                        </div>
                                        @if($dose->recommended_age_label)
                                            <span class="text-[10px] font-black px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md">
                                                {{ $dose->recommended_age_label }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/60 text-xs space-y-2">
                                        <!-- Dependency Info -->
                                        @if($dose->depends_on_previous_dose)
                                            <div class="flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                                                <x-heroicon-m-clock class="w-3.5 h-3.5" />
                                                <span>
                                                    Due:
                                                    @if($dose->interval_months > 0) <strong>{{ $dose->interval_months }}m</strong> @endif
                                                    @if($dose->interval_days > 0) <strong>{{ $dose->interval_days }}d</strong> @endif
                                                    after previous dose
                                                </span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                                <x-heroicon-m-calendar class="w-3.5 h-3.5" />
                                                <span>
                                                    Due:
                                                    @if($dose->due_after_months > 0) <strong>{{ $dose->due_after_months }}m</strong> @endif
                                                    @if($dose->due_after_days > 0) <strong>{{ $dose->due_after_days }}d</strong> @endif
                                                    from program start
                                                </span>
                                            </div>
                                        @endif

                                        <!-- Min/Max Age Constraints -->
                                        @if($dose->minimum_age_days || $dose->maximum_age_days)
                                            <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 text-[11px] bg-gray-50 dark:bg-gray-900/30 p-1.5 rounded-md">
                                                <x-heroicon-m-information-circle class="w-3.5 h-3.5 text-gray-400" />
                                                <span>
                                                    @if($dose->minimum_age_days) Min: {{ $dose->minimum_age_days }}d @endif
                                                    @if($dose->maximum_age_days) | Max: {{ $dose->maximum_age_days }}d @endif
                                                </span>
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
