@php
    $record = $getState();
    $record->loadMissing(['doctor.user', 'days.meals']);

    $days = $record->days()->orderBy('day_number')->get();
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
                            @if($record->doctor)
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-user class="w-4 h-4 text-gray-400" />
                                    <span>Doctor: <strong class="text-gray-700 dark:text-gray-300">Dr. {{ $record->doctor->first_name }} {{ $record->doctor->last_name }}</strong></span>
                                </div>
                            @endif
                            <div class="flex items-center gap-1">
                                <x-heroicon-m-calendar class="w-4 h-4 text-gray-400" />
                                <span>Duration: <strong class="text-gray-700 dark:text-gray-300">{{ $record->duration_days }} Days</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-700">
                        {{ $days->count() }} Configured Days
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

    <!-- Restrictions and Notes -->
    @if($record->restrictions || $record->notes)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($record->restrictions)
                <div class="bg-rose-50/10 dark:bg-rose-950/5 border border-rose-100 dark:border-rose-900/20 rounded-xl p-5">
                    <h4 class="text-sm font-bold text-rose-800 dark:text-rose-400 flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-500" />
                        <span>Dietary Restrictions & Allergens</span>
                    </h4>
                    <p class="text-xs text-rose-700 dark:text-rose-300 mt-2 leading-relaxed">
                        {{ $record->restrictions }}
                    </p>
                </div>
            @endif

            @if($record->notes)
                <div class="bg-gray-50 dark:bg-gray-900/40 border border-gray-150 dark:border-gray-800/80 rounded-xl p-5">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-gray-500" />
                        <span>Clinical Notes & Guidelines</span>
                    </h4>
                    <p class="text-xs text-gray-650 dark:text-gray-400 mt-2 leading-relaxed">
                        {{ $record->notes }}
                    </p>
                </div>
            @endif
        </div>
    @endif

    <!-- Meal Plan Grid by Days -->
    <div class="space-y-6">
        <h3 class="text-base font-bold text-gray-900 dark:text-white uppercase tracking-wider">Daily Meal Plan Grid</h3>

        @if($days->isEmpty())
            <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                No days or meals configured for this template. Edit the template to add them.
            </div>
        @else
            <div class="relative border-l border-gray-200 dark:border-gray-800 ml-4 md:ml-6 space-y-8 pb-4">
                @foreach($days as $dayIndex => $day)
                    <!-- Day Block -->
                    <div class="relative pl-6 md:pl-8 group">
                        <!-- Timeline Dot -->
                        <div class="absolute -left-[13px] top-1.5 w-6 h-6 rounded-full bg-white dark:bg-gray-950 border-2 border-primary-600 dark:border-primary-500 flex items-center justify-center shadow-sm z-10">
                            <span class="text-[10px] font-black text-primary-700 dark:text-primary-400">{{ $day->day_number }}</span>
                        </div>

                        <!-- Day Header Card -->
                        <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-150 dark:border-gray-800/80 mb-4">
                            <div class="flex items-center justify-between gap-4">
                                <h4 class="text-base font-bold text-gray-900 dark:text-white leading-snug">
                                    Day {{ $day->day_number }} @if($day->week_day) <span class="text-xs font-normal text-gray-550 dark:text-gray-400 ml-1">({{ ucfirst(strtolower($day->week_day)) }})</span> @endif
                                </h4>
                            </div>
                        </div>

                        <!-- Meals Cards inside the Day -->
                        @php
                            $meals = $day->meals()->orderBy('sort_order')->get();
                        @endphp
                        @if($meals->isEmpty())
                            <div class="p-4 text-xs italic text-gray-450 dark:text-gray-500 bg-gray-50/30 dark:bg-gray-900/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                                No meals configured for this day.
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($meals as $meal)
                                    <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow transition relative overflow-hidden group">
                                        <div class="absolute right-0 top-0 w-24 h-24 bg-primary-50/50 dark:bg-primary-950/10 rounded-full translate-x-8 -translate-y-8 -z-10 group-hover:scale-110 transition duration-300"></div>
                                        
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    @php
                                                        $mealTypeLabel = match(strtoupper($meal->meal_type)) {
                                                            'MORNING' => 'Morning',
                                                            'BREAKFAST' => 'Breakfast',
                                                            'MID_MEAL' => 'Mid Meal',
                                                            'LUNCH' => 'Lunch',
                                                            'EVENING_SNACK' => 'Evening Snack',
                                                            'DINNER' => 'Dinner',
                                                            'NIGHT' => 'Night',
                                                            default => ucfirst(strtolower($meal->meal_type)),
                                                        };
                                                        
                                                        $typeColor = match(strtoupper($meal->meal_type)) {
                                                            'BREAKFAST', 'MORNING' => 'bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50',
                                                            'LUNCH', 'MID_MEAL' => 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-amber-900/50',
                                                            'DINNER', 'NIGHT', 'EVENING_SNACK' => 'bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50',
                                                            default => 'bg-gray-50 dark:bg-gray-950/20 text-gray-700 dark:text-gray-400 border border-gray-100 dark:border-gray-900/50',
                                                        };
                                                    @endphp
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $typeColor }}">
                                                        {{ $mealTypeLabel }}
                                                    </span>
                                                    @if($meal->start_time)
                                                        <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium">
                                                            {{ \Carbon\Carbon::parse($meal->start_time)->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <h5 class="text-sm font-bold text-gray-900 dark:text-white mt-1.5">
                                                    {{ $meal->meal_name }}
                                                </h5>
                                            </div>
                                        </div>

                                        @if($meal->instructions)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed italic">
                                                {{ $meal->instructions }}
                                            </p>
                                        @endif

                                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/60 grid grid-cols-4 gap-1 text-[10px] text-center">
                                            <div class="bg-gray-50 dark:bg-gray-850 p-1.5 rounded">
                                                <div class="text-gray-400 dark:text-gray-500">Calories</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-300 mt-0.5">{{ $meal->calories ?? 0 }}</div>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-850 p-1.5 rounded">
                                                <div class="text-gray-400 dark:text-gray-500">Protein</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-300 mt-0.5">{{ $meal->protein_grams ?? 0 }}g</div>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-850 p-1.5 rounded">
                                                <div class="text-gray-400 dark:text-gray-500">Carbs</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-300 mt-0.5">{{ $meal->carbs_grams ?? 0 }}g</div>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-850 p-1.5 rounded">
                                                <div class="text-gray-400 dark:text-gray-500">Fat</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-300 mt-0.5">{{ $meal->fat_grams ?? 0 }}g</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
