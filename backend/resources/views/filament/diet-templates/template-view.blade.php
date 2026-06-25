@php
    $record = $getState();
    $record->loadMissing(['doctor.user', 'days.meals']);

    $days = $record->days()->orderBy('day_number')->get();
    $durationDays = max(1, (int) ($record->duration_days ?? 7));

    $schedule = (array) data_get($record->features, 'schedule', []);
    $recurrenceMode = (string) ($schedule['recurrence_mode'] ?? 'recurring');
    $patternType = (string) ($schedule['pattern_type'] ?? 'weekly');
    $cycleLength = max(1, (int) ($schedule['cycle_length_days'] ?? 7));
    $sameMeal = (bool) ($schedule['follow_same_meal_all_days'] ?? false);

    $tabsCount = max(1, (int) ceil($durationDays / 7));
    $visibleTabs = $tabsCount;

    $chartDays = $days->map(fn ($day): array => [
        'day_number' => (int) $day->day_number,
        'week_day' => (string) ($day->week_day ?? ''),
        'meals' => $day->meals
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($meal): array => [
                'meal_type' => (string) $meal->meal_type,
                'meal_name' => (string) $meal->meal_name,
                'instructions' => (string) ($meal->instructions ?? ''),
                'meal_image' => (string) ($meal->meal_image ?? ''),
                'helpful_links' => collect($meal->helpful_links ?? [])->map(fn ($link): array => [
                    'type' => (string) ($link['type'] ?? 'link'),
                    'title' => (string) ($link['title'] ?? ''),
                    'url' => (string) ($link['url'] ?? ''),
                ])->filter(fn (array $link): bool => filled($link['url']))->values()->all(),
                'start_time' => $meal->start_time ? \Carbon\Carbon::parse($meal->start_time)->format('h:i A') : 'No time',
                'sort_order' => (int) $meal->sort_order,
            ])
            ->all(),
    ])->values()->all();

    $weekGroups = $days->groupBy(fn ($day) => (int) floor(((int) $day->day_number - 1) / 7) + 1);
    $weekOne = $weekGroups->get(1, collect())->values();

    $daysOnCurrentTab = $recurrenceMode === 'one_time'
        ? min($durationDays, $days->count())
        : min(7, $days->count());

    if ($recurrenceMode === 'one_time') {
        $patternHeading = 'One-time Meal Pattern';
    } else {
        $patternHeading = 'Week 1 Meal Pattern';
    }
@endphp

<div class="diet-admin-shell space-y-4">
    <div class="diet-admin-card">
        <div class="diet-admin-card-title">Template Details</div>
        <div class="diet-admin-grid-2">
            <div>
                <div class="diet-admin-label">Doctor</div>
                <div class="diet-admin-readonly-value">
                    @if($record->doctor)
                        Dr. {{ trim(($record->doctor->first_name ?? '').' '.($record->doctor->last_name ?? '')) }}
                    @else
                        Not assigned
                    @endif
                </div>
            </div>
            <div>
                <div class="diet-admin-label">Template Name</div>
                <div class="diet-admin-readonly-value">{{ $record->name }}</div>
            </div>
            <div>
                <div class="diet-admin-label">Duration Days</div>
                <div class="diet-admin-readonly-value">{{ $durationDays }}</div>
            </div>
            <div>
                <div class="diet-admin-label">Status</div>
                <div class="diet-admin-readonly-value">{{ $record->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
        </div>
        @if($record->description)
            <div class="diet-admin-description">{{ $record->description }}</div>
        @endif
    </div>

    <div class="diet-admin-card">
        <div class="diet-admin-card-title">Meal Schedule Rule</div>
        <div class="diet-schedule-preview">
            <div class="diet-schedule-grid">
                <div class="diet-schedule-card {{ $recurrenceMode === 'recurring' && $patternType === 'weekly' ? 'is-active' : '' }}">
                    <div class="diet-schedule-title">Weekly tabs repeat until duration ends</div>
                    <div class="diet-schedule-text">Best for 1 month plans. Create week-wise pattern and system repeats automatically.</div>
                </div>
                <div class="diet-schedule-card {{ $recurrenceMode === 'one_time' ? 'is-active' : '' }}">
                    <div class="diet-schedule-title">Exact days only, no repeat</div>
                    <div class="diet-schedule-text">Configured duration: {{ $durationDays }} day(s).</div>
                </div>
                <div class="diet-schedule-card {{ $sameMeal ? 'is-active' : '' }}">
                    <div class="diet-schedule-title">Same meal every day</div>
                    <div class="diet-schedule-text">Reuse Day 1 meal set for all days in patient duration.</div>
                </div>
            </div>
            <div class="diet-schedule-note {{ $recurrenceMode === 'one_time' ? 'is-one-time' : '' }}">
                @if($recurrenceMode === 'one_time')
                    One-time mode: no repeat; patient gets only configured days.
                @else
                    Duration decides total patient days. Weekly or cycle meal pattern repeats until duration ends.
                @endif
            </div>
        </div>
    </div>

    <div
        class="diet-admin-card diet-weekly-context"
        x-data="{
            days: @js($chartDays),
            selectedWeek: 1,
            selectedDayIndex: 0,
            weekTabs: Array.from({ length: {{ $visibleTabs }} }, (_, index) => index + 1),
            visibleDays() {
                const start = (this.selectedWeek - 1) * 7;
                return this.days.slice(start, start + 7).map((day, offset) => ({
                    day,
                    index: start + offset,
                }));
            },
            selectedDay() {
                return this.days[this.selectedDayIndex] || this.visibleDays()[0]?.day || null;
            },
            selectedMeals() {
                return this.selectedDay()?.meals || [];
            },
            dayLabel(day) {
                if (!day) return 'Day';
                const value = day.week_day || `Day ${day.day_number}`;
                return String(value).toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
            },
            mealTypeLabel(type) {
                return String(type || 'Meal').toLowerCase().replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
            },
            linkLabel(link) {
                const type = String(link?.type || 'Link').toLowerCase();
                const title = String(link?.title || '').trim();
                const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);

                if (title.length) {
                    return `${typeLabel}: ${title}`;
                }

                return `${typeLabel} link`;
            },
            selectWeek(week) {
                this.selectedWeek = week;
                this.selectedDayIndex = Math.min(this.days.length - 1, (week - 1) * 7);
            },
            selectDay(index) {
                this.selectedDayIndex = index;
            },
        }"
    >
        <div>
            <div class="diet-admin-card-title">Weekly Meal Chart</div>
            <div class="diet-week-chart-subtitle">Only one week opens at a time. Days stay as tabs on the left and selected day meals show on the right.</div>
        </div>

        <div class="diet-week-tabs" role="list" aria-label="Week tabs preview">
            <template x-for="week in weekTabs" :key="week">
                <button
                    type="button"
                    class="diet-week-tab"
                    :class="{ 'is-active': selectedWeek === week }"
                    x-text="`Week ${week}`"
                    x-on:click="selectWeek(week)"
                ></button>
            </template>
        </div>

        <div class="diet-week-stats">
            <div class="diet-week-stat">
                <div class="diet-week-stat-value">{{ $durationDays }}</div>
                <div class="diet-week-stat-label">Duration days</div>
            </div>
            <div class="diet-week-stat">
                <div class="diet-week-stat-value">{{ $visibleTabs }}</div>
                <div class="diet-week-stat-label">Weeks tab created</div>
            </div>
            <div class="diet-week-stat">
                <div class="diet-week-stat-value">{{ $daysOnCurrentTab }}</div>
                <div class="diet-week-stat-label">Days shown in current tab</div>
            </div>
            <div class="diet-week-stat">
                <div class="diet-week-stat-value">No scroll</div>
                <div class="diet-week-stat-label">Only selected week visible</div>
            </div>
        </div>

        <div class="diet-week-pattern-title" x-text="selectedWeek === 1 ? @js($patternHeading) : `Week ${selectedWeek} Meal Pattern`"></div>
        <div class="diet-week-pattern-subtitle">Select a week, then select a day to view its meals.</div>

        @if($days->isNotEmpty())
            <div class="diet-week-pattern-shell">
                <div class="diet-week-day-list">
                    <template x-for="entry in visibleDays()" :key="entry.index">
                        <button
                            type="button"
                            class="diet-week-day-item"
                            :class="{ 'is-active': selectedDayIndex === entry.index }"
                            x-on:click="selectDay(entry.index)"
                        >
                            <span x-text="dayLabel(entry.day)"></span>
                            <span x-text="`${entry.day.meals.length} meals`"></span>
                        </button>
                    </template>
                </div>

                <div class="diet-week-day-meals">
                    <div class="diet-week-day-title-row">
                        <div class="diet-week-day-title">
                            <span x-text="`${dayLabel(selectedDay())} Meals - Week ${selectedWeek}`"></span>
                        </div>
                        <div class="diet-week-meals-count" x-text="`${selectedMeals().length} meals`"></div>
                    </div>

                    <template x-if="selectedMeals().length === 0">
                        <div class="diet-week-empty">No meals configured for this day.</div>
                    </template>

                    <template x-for="(meal, mealIndex) in selectedMeals()" :key="`${selectedDayIndex}-${mealIndex}`">
                        <div class="diet-week-meal-row" :class="{ 'is-open': mealIndex === 0 }">
                            <div class="diet-week-meal-summary">
                                <strong x-text="`${mealTypeLabel(meal.meal_type)}: ${meal.meal_name}`"></strong>
                                <span x-text="meal.start_time"></span>
                            </div>

                            <div>
                                <div class="diet-week-meal-detail-grid">
                                    <div>
                                        <div class="diet-week-meal-label">Meal Type</div>
                                        <div class="diet-week-meal-display" x-text="mealTypeLabel(meal.meal_type)"></div>
                                    </div>
                                    <div>
                                        <div class="diet-week-meal-label">Meal Name / Food Items</div>
                                        <div class="diet-week-meal-display" x-text="meal.meal_name"></div>
                                    </div>
                                    <div>
                                        <div class="diet-week-meal-label">Start Time</div>
                                        <div class="diet-week-meal-display" x-text="meal.start_time"></div>
                                    </div>
                                </div>

                                <div class="diet-week-meal-instructions">
                                    <div class="diet-week-meal-label">Instructions</div>
                                    <div class="diet-week-meal-display-note" x-text="meal.instructions || 'No instructions added.'"></div>
                                </div>

                                <template x-if="meal.meal_image || (Array.isArray(meal.helpful_links) && meal.helpful_links.length)">
                                    <div class="mt-3 space-y-2">
                                        <div class="diet-week-meal-label">Recipe Media</div>

                                        <template x-if="meal.meal_image">
                                            <a :href="meal.meal_image" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-primary-600 hover:bg-slate-50">
                                                View meal image
                                            </a>
                                        </template>

                                        <template x-if="Array.isArray(meal.helpful_links) && meal.helpful_links.length">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="(link, linkIndex) in meal.helpful_links" :key="`${selectedDayIndex}-${mealIndex}-link-${linkIndex}`">
                                                    <a
                                                        :href="link.url"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-white"
                                                        x-text="linkLabel(link)"
                                                    ></a>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @else
            <div class="diet-week-empty">No days configured in this template.</div>
        @endif
    </div>
</div>
