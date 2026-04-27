<div class="space-y-4">
    {{-- Day Header with Navigation --}}
    @php
        $dayLabel = 'Select a Day';
        if (isset($days, $activeDay) && isset($days[$activeDay])) {
            $dayLabel = ucfirst($activeDay) . ' - ' . $days[$activeDay]->format('M d, Y');
        }
    @endphp

    <x-shared.section-header type="calendar" :showNavigation="true" navigationPrevious="previousDay" navigationNext="nextDay"
        :navigationLabel="$dayLabel" />

    {{-- 50-50 Layout: Calendar Slots + Appointments --}}
    <div class="grid grid-cols-2 gap-6">
        {{-- Left: Selected Day Slots (50%) --}}
        <div class="col-span-1">
            <x-calendar.selected-day-slots :selectedDateLabel="$selectedDateLabel" :selectedDateSlots="$selectedDateSlots" :showHeader="true" :gridLayout="false" />
        </div>

        {{-- Right: Appointments (50%) --}}
        <div class="col-span-1">
            <x-appointments.appointments-card-view :appointments="$appointments" title="Patient Appointments" :selectedDateLabel="$selectedDateLabel" />
        </div>
    </div>
</div>
