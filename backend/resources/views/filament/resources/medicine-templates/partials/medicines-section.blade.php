@php
    $items = $this->data['items'] ?? [];
    $count = count($items);
    $isCreatePage = str_contains(get_class($this), 'Create');
    $isCollapsed = ($count > 0 && !$isCreatePage) ? 'true' : 'false';
@endphp

<div 
    x-data="{ isCollapsed: {{ $isCollapsed }} }" 
    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden transition-all duration-300"
>
    <!-- Header/Toggle Bar -->
    <div 
        @click="isCollapsed = !isCollapsed" 
        class="flex items-center justify-between p-4 bg-slate-50/50 dark:bg-gray-950/20 cursor-pointer border-b border-gray-100 dark:border-gray-850/50 hover:bg-slate-100/30 dark:hover:bg-gray-800/10 transition-colors duration-200 select-none"
    >
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-50 dark:bg-primary-950/30 text-primary-600 dark:text-primary-400 rounded-lg shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-950 dark:text-white flex items-center gap-2">
                    {{ $getLabel() ?: 'Medicines' }}
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/30 dark:text-primary-300">
                        {{ $count }} {{ Str::plural('medicine', $count) }}
                    </span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $getDescription() ?: 'Add one or more medicines that will be created as prescriptions when the doctor applies this template.' }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <span x-show="isCollapsed && {{ $count }} > 0" class="text-xs text-muted-foreground hidden sm:inline">
                Click to expand and edit
            </span>
            <button 
                type="button" 
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200"
            >
                <svg 
                    class="w-4 h-4 transform transition-transform duration-200" 
                    :class="{ 'rotate-180': !isCollapsed }"
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Collapsed View Summary -->
    <div x-show="isCollapsed" class="p-4 bg-slate-50/20 dark:bg-gray-950/10 border-b border-gray-100 dark:border-gray-800/30">
        @if($count > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($items as $key => $item)
                    @php
                        $medName = $item['medicine_name'] ?? 'New Medicine';
                        $medType = $item['medicine_type'] ?? '';
                        $medDosage = $item['dosage'] ?? '';
                        if ($medDosage === 'custom') {
                            $medDosage = $item['dosage_custom'] ?? '';
                        }
                        $useType = $item['use_type'] ?? 'regular';
                        $meal = $item['meal_timing'] ?? '';
                        $duration = $item['duration_value'] ?? '';
                        $durationType = $item['duration_type'] ?? 'days';
                        
                        $badgeClass = $useType === 'sos' 
                            ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800' 
                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800';
                        $badgeLabel = $useType === 'sos' ? 'SOS' : 'Regular';
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-lg shadow-sm hover:border-gray-300 dark:hover:border-gray-700 transition-colors duration-200">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-5 h-5 rounded bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 shrink-0 mt-0.5">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-gray-950 dark:text-white truncate">
                                    {{ $medName }}
                                </h4>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-1 mt-0.5">
                                    @if($medType)
                                        <span>{{ $medType }}</span>
                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                    @endif
                                    @if($medDosage)
                                        <span>{{ $medDosage }}</span>
                                    @endif
                                    @if($meal)
                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                        <span class="capitalize">{{ str_replace('_', ' ', $meal) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0 ml-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold tracking-wide uppercase {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>
                            @if($duration)
                                <span class="text-[9px] font-semibold text-gray-500 dark:text-gray-400">
                                    {{ $duration }} {{ $durationType }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5 text-xs text-gray-500 dark:text-gray-400 border border-dashed border-gray-250 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900">
                No medicines added to this template yet. Click the header to expand and add medicines.
            </div>
        @endif
    </div>

    <!-- Expanded View (Original Filament components container) -->
    <div x-show="!isCollapsed" class="p-4">
        {{ $getChildComponentContainer() }}
    </div>
</div>
