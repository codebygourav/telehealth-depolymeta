@php
    $data = $this->data ?? [];
    $schedule = (array) data_get($data, 'features.schedule', []);

    $recurrenceMode = (string) ($schedule['recurrence_mode'] ?? 'recurring');
    $patternType = (string) ($schedule['pattern_type'] ?? 'weekly');
    $durationDays = max(1, (int) data_get($data, 'duration_days', 7));
    $sameMeal = (bool) ($schedule['follow_same_meal_all_days'] ?? false);

    $isRecurringWeekly = $recurrenceMode === 'recurring' && $patternType === 'weekly' && ! $sameMeal;
    $isExactWeeks = $recurrenceMode === 'one_time';
    $isOneTime = $recurrenceMode === 'one_time';
@endphp

<div
    class="diet-schedule-preview"
    x-data="{
        recurrenceMode: $wire.entangle('data.features.schedule.recurrence_mode').live,
        patternType: $wire.entangle('data.features.schedule.pattern_type').live,
        cycleLengthDays: $wire.entangle('data.features.schedule.cycle_length_days').live,
        durationDays: $wire.entangle('data.duration_days').live,
        followSameMealAllDays: $wire.entangle('data.features.schedule.follow_same_meal_all_days').live,
        pickWeekly() {
            this.recurrenceMode = 'recurring';
            this.patternType = 'weekly';
            this.followSameMealAllDays = false;
            this.cycleLengthDays = Math.max(1, Number(this.durationDays || 7));
        },
        pickExactWeeks() {
            this.recurrenceMode = 'one_time';
            this.patternType = 'weekly';
            this.followSameMealAllDays = false;
            this.cycleLengthDays = Math.max(1, Number(this.durationDays || 7));
        },
        pickSameMeal() {
            this.recurrenceMode = 'recurring';
            this.patternType = 'weekly';
            this.followSameMealAllDays = true;
            this.cycleLengthDays = Math.max(1, Number(this.durationDays || 7));
        }
    }"
>
    <div class="diet-schedule-grid">
        <div
            class="diet-schedule-card is-clickable {{ $isRecurringWeekly ? 'is-active' : '' }}"
            :class="{ 'is-active': recurrenceMode === 'recurring' && patternType === 'weekly' && !followSameMealAllDays }"
            role="button"
            tabindex="0"
            x-on:click="pickWeekly()"
            x-on:keydown.enter.prevent="pickWeekly()"
        >
            <div class="diet-schedule-title">Weekly tabs repeat until duration ends</div>
            <div class="diet-schedule-text">Best for longer plans. Current duration: <span x-text="durationDays || {{ $durationDays }}"></span> day(s). Create week tabs as needed and the chart follows the selected duration.</div>
        </div>

        <div
            class="diet-schedule-card is-clickable {{ $isExactWeeks ? 'is-active' : '' }}"
            :class="{ 'is-active': recurrenceMode === 'one_time' }"
            role="button"
            tabindex="0"
            x-on:click="pickExactWeeks()"
            x-on:keydown.enter.prevent="pickExactWeeks()"
        >
            <div class="diet-schedule-title">Exact weeks only, no repeat</div>
            <div class="diet-schedule-text">Best when the patient should receive only the configured days. Current duration: <span x-text="durationDays || {{ $durationDays }}"></span> day(s).</div>
        </div>

        <div
            class="diet-schedule-card is-clickable {{ $sameMeal ? 'is-active' : '' }}"
            :class="{ 'is-active': followSameMealAllDays }"
            role="button"
            tabindex="0"
            x-on:click="pickSameMeal()"
            x-on:keydown.enter.prevent="pickSameMeal()"
        >
            <div class="diet-schedule-title">Same meal every day</div>
            <div class="diet-schedule-text">Best for simple diets. Add Day 1 meals only, then reuse that meal set for all <span x-text="durationDays || {{ $durationDays }}"></span> day(s).</div>
        </div>
    </div>

    <div class="diet-schedule-note {{ $isOneTime ? 'is-one-time' : '' }}" :class="{ 'is-one-time': recurrenceMode === 'one_time' }">
        <span x-show="recurrenceMode === 'one_time'">One-time mode selected. Template days will not repeat; patient gets only configured days.</span>
        <span x-show="recurrenceMode !== 'one_time'">Simple logic: Duration decides total patient days. Weekly meal pattern repeats automatically until duration ends.</span>
    </div>
</div>
