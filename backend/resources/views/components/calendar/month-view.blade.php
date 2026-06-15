<div class="grid grid-cols-12 gap-4">
    <div class="col-span-4 space-y-4">
        {{-- Month Navigation --}}

        <x-shared.section-header type="calendar" :showNavigation="true" navigationPrevious="previousMonth"
            navigationNext="nextMonth" :navigationLabel="$currentMonthLabel ?? now()->format('F Y')" />
        {{-- Calendar Container --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border shadow">

            {{-- Day Headers --}}
            <div class="grid grid-cols-7 gap-2 mb-2">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div
                        class="text-center text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider py-2">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Days Grid --}}
            <div class="grid grid-cols-7 gap-2">
                @foreach ($monthDays as $day)
                    @php
                        $dayDate = $day['date']->format('Y-m-d');
                        $isActive =
                            $dayDate ===
                            ($selectedDateLabel ? \Carbon\Carbon::parse($selectedDateLabel)->format('Y-m-d') : '');
                    @endphp

                    <button type="button" wire:click="showDaySlots('{{ $dayDate }}')"
                        class="relative h-17 rounded-lg border transition-all duration-200 cursor-pointer
                        @if ($isActive)
                            bg-primary border-primary text-white shadow-sm scale-[1.02]
                        @elseif ($day['is_today'])
                            bg-primary-50/30 dark:bg-primary-950/20 border-2 border-primary text-primary
                        @else
                            bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                            hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-gray-300 dark:hover:border-gray-600
                        @endif
                        @if (!$day['is_current_month']) opacity-40 @endif">

                        {{-- Day Number --}}
                        <div
                            class="absolute top-1 left-2 text-sm font-semibold
                            @if ($isActive) text-white
                            @elseif ($day['is_today']) text-primary
                            @else text-gray-700 dark:text-gray-300 @endif">
                            {{ $day['date']->format('j') }}
                        </div>

                        {{-- Event Indicator --}}
                        @if (!empty($day['events']))
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                                <span class="w-1.5 h-1.5 rounded-xl @if ($isActive) bg-white @else bg-primary @endif"></span>
                            </div>
                        @endif
                    </button>
                @endforeach

            </div>
        </div>
    </div>

    {{-- RIGHT: Sidebar (60%) --}}
    <x-calendar.selected-day-slots 
        :selectedDateLabel="$selectedDateLabel" 
        :selectedDateSlots="$this->getFilteredDateSlots()" 
        :allSlots="$selectedDateSlots"
        :selectedTimeSlot="$selectedTimeSlot"
    />

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
