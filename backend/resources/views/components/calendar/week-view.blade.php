<div class="grid grid-cols-12 gap-4">

    {{-- LEFT — WEEK VIEW --}}
    <div class="col-span-4 space-y-4 flex flex-col h-[calc(100vh-200px)]">

        {{-- Week Navigation (Sticky Header) --}}
        <x-shared.section-header type="calendar" :showNavigation="true" navigationPrevious="previousWeek"
            navigationNext="nextWeek" :navigationLabel="$currentWeekLabel ??
                now()->startOfWeek()->format('M d') . ' - ' . now()->endOfWeek()->format('M d, Y')" />

        {{-- Week Grid (Scrollable) --}}
        <div class="flex-1 overflow-y-auto space-y-3 no-scrollbar">

            {{-- Days Grid (Reverse Chronological Order) --}}
            <div class="space-y-3">
                @php
                    // Sort days in reverse chronological order (newest first)
                    $sortedDays = collect($days)->sortByDesc(function ($date, $dayName) {
                        return $date->timestamp;
                    });
                    $today = now()->format('Y-m-d');
                @endphp

                @foreach ($sortedDays as $dayName => $date)
                    @php
                        $isToday = $date->isToday();
                        $isPast = $date->isPast() && !$isToday;
                        $daySchedule = $schedule[$dayName] ?? [];
                        ksort($daySchedule);
                        $dayDate = $date->format('Y-m-d');

                        // Group by consultation type only (combine in-person General and Private)
                        $typeCounts = [];
                        foreach ($daySchedule as $time => $slots) {
                            foreach ($slots as $slot) {
                                if (isset($slot['consultation_type'])) {
                                    $consultationType = $slot['consultation_type'];
                                    if (!isset($typeCounts[$consultationType])) {
                                        $typeCounts[$consultationType] = 0;
                                    }
                                    $typeCounts[$consultationType]++;
                                }
                            }
                        }
                    @endphp

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
                        @if (!empty($daySchedule)) x-data="{ open: {{ $isToday ? 'true' : 'false' }} }" @endif>

                        {{-- Header --}}
                        <div @if (!empty($daySchedule)) @click="open = !open" @endif
                            class="p-3 flex items-center justify-between
                    {{ !empty($daySchedule) ? 'cursor-pointer dark:hover:bg-gray-700/40' : '' }}
                    {{ $isToday ? 'bg-gray-50' : 'bg-gray-50 dark:bg-gray-800/40' }}
                    {{ $isPast ? 'opacity-75' : '' }}
                    transition-colors">

                            <div class="flex items-center gap-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[11px] font-semibold tracking-wide text-gray-600 dark:text-gray-400">
                                        {{ strtoupper($dayName) }}
                                    </span>

                                    <span class="text-sm font-bold {{ $isToday ? 'text-primary ' : 'text-gray-900' }}">
                                        {{ $date->format('M d, Y') }}
                                    </span>
                                    @if ($isToday)
                                        <span class="text-[10px] font-medium mt-1 text-primary">
                                            Today
                                        </span>
                                    @endif
                                </div>

                                @if (empty($daySchedule))
                                    <span class="text-xs text-gray-500 dark:text-gray-400 italic">
                                        No appointments
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">

                                @if (!empty($daySchedule))
                                    {{-- Consultation Type Badge(s) with Counts (grouped by type only) --}}
                                    @if (!empty($typeCounts))
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @foreach ($typeCounts as $consultationType => $count)
                                                @php
                                                    $consultationLabel = ucfirst(
                                                        str_replace('-', ' ', $consultationType),
                                                    );
                                                @endphp
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-medium
                                                    {{ $consultationType === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300' }}">
                                                    <span>
                                                        {{ $consultationLabel }}
                                                    </span>
                                                    @if ($count > 0)
                                                        <span
                                                            class="inline-flex items-center justify-center min-w-[18px] h-4 px-1 rounded-xl text-[9px] font-bold {{ $consultationType === 'video' ? 'bg-blue-200 text-blue-900 dark:bg-blue-800 dark:text-blue-100' : 'bg-primary-100 text-primary-900 dark:bg-primary-800 dark:text-primary-100' }}">
                                                            {{ $count }}
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <x-filament::button class="leading-none"
                                        color="{{ $isToday ? 'primary' : 'gray' }}" size="xs"
                                        wire:click.stop="showDaySlots('{{ $dayDate }}')">
                                        View
                                    </x-filament::button>

                                    {{-- Chevron Icon --}}
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        @if (!empty($daySchedule))
                            {{-- Slots (Collapsible) --}}
                            <div x-show="open" x-collapse class="p-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($daySchedule as $time => $slots)
                                        @foreach ($slots as $slot)
                                            <div wire:click="showDaySlots('{{ $dayDate }}')"
                                                class="group w-[150px] p-3 rounded-xl border
                            border-primary-200 dark:border-primary-700
                            bg-primary-50 dark:bg-primary-900/40 cursor-pointer
                            hover:bg-primary-100 dark:hover:bg-primary-800
                            hover:border-primary-300 dark:hover:border-primary-600
                            transition">
                                                <div
                                                    class="font-semibold text-[13px] text-gray-900 dark:text-gray-100 leading-tight truncate">
                                                    {{ $slot['doctor_name'] }}
                                                </div>
                                                <div class="mt-1 text-[11px] text-gray-600 dark:text-gray-400">
                                                    {{ $slot['start_time'] }} – {{ $slot['end_time'] }}
                                                </div>
                                                @if (isset($slot['consultation_type']) && isset($slot['opd_type']))
                                                    @php
                                                        $consultationLabel = ucfirst(
                                                            str_replace('-', ' ', $slot['consultation_type']),
                                                        );
                                                        $opdLabel = ucfirst($slot['opd_type']);
                                                    @endphp
                                                    <div class="mt-2">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium
                                                            {{ $slot['consultation_type'] === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300' }}">
                                                            {{ $consultationLabel }} ({{ $opdLabel }})
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

            </div>

        </div>


    </div>

    {{-- RIGHT — SELECTED DAY SLOTS --}}
    <x-calendar.selected-day-slots 
        :selectedDateLabel="$selectedDateLabel" 
        :selectedDateSlots="$this->getFilteredDateSlots()" 
        :allSlots="$selectedDateSlots"
        :selectedTimeSlot="$selectedTimeSlot" 
    />

    {{-- Week Navigation --}}
    <div class="col-span-4">
        <x-appointments.appointments-card-view 
            :appointments="$this->getFilteredAppointments()" 
            title="Patient Appointments" 
            :selectedDateLabel="$selectedDateLabel" 
            :selectedTimeSlot="$selectedTimeSlot"
            :typeCounts="$this->getAppointmentTypeCounts()"
            :currentFilter="$appointmentFilter"
        />
    </div>
</div>
