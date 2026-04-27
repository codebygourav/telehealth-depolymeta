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
                        class="relative h-17 rounded-lg border transition-all duration-200
                        @if ($day['is_today']) bg-primary-50 dark:bg-primary-900/20 border-primary ring-2 ring-primary
                        @elseif ($isActive)
                            bg-gray-100 dark:bg-red-900/20 border-gray-500 ring-2 ring-gray-500
                        @else
                            bg-gray-50 border-gray-200
                            hover:bg-gray-100 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 @endif
                        @if (!$day['is_current_month']) opacity-40 @endif">

                        {{-- Day Number --}}
                        <div
                            class="absolute top-1 left-2 text-sm font-semibold
                        @if ($day['is_today']) text-primary-900 dark:text-primary-400
                        @elseif ($isActive) text-gray-600
                        @else
                            text-gray-700 dark:text-gray-300 @endif">
                            {{ $day['date']->format('j') }}
                        </div>

                        {{-- Event Indicator --}}
                        @if (!empty($day['events']))
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                                <span class="w-1.5 h-1.5 rounded-xl bg-primary"></span>
                            </div>
                        @endif
                    </button>
                @endforeach

            </div>
        </div>
    </div>

    {{-- RIGHT: Sidebar (60%) --}}
    <x-calendar.selected-day-slots :selectedDateLabel="$selectedDateLabel" :selectedDateSlots="$selectedDateSlots" />

    <div class="col-span-4">
        <x-appointments.appointments-card-view :appointments="$appointments" title="Patient Appointments" :selectedDateLabel="$selectedDateLabel" />
    </div>
</div>
