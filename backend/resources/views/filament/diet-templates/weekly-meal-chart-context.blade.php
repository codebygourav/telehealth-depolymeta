@php
    $weekDays = [
        'MONDAY' => 'Monday',
        'TUESDAY' => 'Tuesday',
        'WEDNESDAY' => 'Wednesday',
        'THURSDAY' => 'Thursday',
        'FRIDAY' => 'Friday',
        'SATURDAY' => 'Saturday',
        'SUNDAY' => 'Sunday',
    ];

    $mealTypes = [
        'MORNING' => 'Morning',
        'BREAKFAST' => 'Breakfast',
        'MID_MEAL' => 'Mid Meal',
        'LUNCH' => 'Lunch',
        'EVENING_SNACK' => 'Evening Snack',
        'DINNER' => 'Dinner',
        'NIGHT' => 'Night',
    ];

    $mealPresets = \App\Filament\Resources\DietTemplates\DietTemplateResource::mealPresets();
@endphp

<div
    class="diet-weekly-context diet-custom-chart"
    x-data="{
        dietChartPayload: $wire.entangle('data.diet_chart_payload').live,
        days: [],
        durationDays: $wire.entangle('data.duration_days').live,
        recurrenceMode: $wire.entangle('data.features.schedule.recurrence_mode').live,
        patternType: $wire.entangle('data.features.schedule.pattern_type').live,
        cycleLengthDays: $wire.entangle('data.features.schedule.cycle_length_days').live,
        followSameMealAllDays: $wire.entangle('data.features.schedule.follow_same_meal_all_days').live,
        selectedWeek: 1,
        selectedDayIndex: 0,
        openMealIndex: 0,
        weekDays: @js($weekDays),
        mealTypes: @js($mealTypes),
        mealPresets: @js($mealPresets),

        init() {
            this.days = this.parsePayload();

            if (!Array.isArray(this.days) || this.days.length === 0) {
                this.days = [this.newDay(1)];
            }

            this.normalizeDays();
            this.updatePayload();
            this.$watch('durationDays', () => this.enforceDurationLimit());
        },

        touch() {
            this.days = [...this.days];
            this.cycleLengthDays = Math.max(1, Number(this.durationDays || 7));
            this.updatePayload();
        },

        parsePayload() {
            if (Array.isArray(this.dietChartPayload)) {
                return this.dietChartPayload;
            }

            if (!this.dietChartPayload || typeof this.dietChartPayload !== 'string') {
                return [];
            }

            try {
                const parsed = JSON.parse(this.dietChartPayload);

                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        },

        updatePayload() {
            this.dietChartPayload = JSON.stringify(this.days);
        },

        maxAllowedDays() {
            return Math.max(1, Number(this.durationDays || 7));
        },

        weekTabsCreated() {
            return Math.max(1, Math.ceil(Math.max(this.days.length, 1) / 7));
        },

        daysShownInCurrentTab() {
            const start = (this.selectedWeek - 1) * 7;
            return Math.min(7, Math.max(0, this.days.length - start));
        },

        patternHeading() {
            return this.recurrenceMode === 'one_time' ? 'One-time Meal Pattern' : `Week ${this.selectedWeek} Meal Pattern`;
        },

        weekTabs() {
            const tabs = [];
            for (let i = 1; i <= this.weekTabsCreated(); i++) {
                tabs.push(i);
            }
            return tabs;
        },

        visibleDays() {
            const start = (this.selectedWeek - 1) * 7;
            return this.days.slice(start, start + 7).map((day, offset) => ({
                day,
                index: start + offset,
            }));
        },

        selectedDay() {
            return this.days[this.selectedDayIndex] || null;
        },

        dayLabel(day, index) {
            const fallback = `Day ${Number(day?.day_number || index + 1)}`;
            return this.weekDays[day?.week_day] || fallback;
        },

        mealTypeLabel(type) {
            return this.mealTypes[type] || 'Meal';
        },

        mealCount(day) {
            return Array.isArray(day?.meals) ? day.meals.length : 0;
        },

        newDay(dayNumber) {
            const keys = Object.keys(this.weekDays);

            return {
                day_number: dayNumber,
                week_day: keys[(dayNumber - 1) % keys.length],
                meals: [],
            };
        },

        newMeal() {
            const meal = {
                meal_type: 'MORNING',
                meal_preset: '',
                meal_name: '',
                instructions: '',
                meal_image: '',
                helpful_links: [],
                calories: null,
                protein_grams: null,
                carbs_grams: null,
                fat_grams: null,
                start_time: '',
                sort_order: 0,
            };

            this.applyDefaultPreset(meal);

            return meal;
        },

        presetOptions(mealType) {
            return this.mealPresets[mealType] || {};
        },

        matchingPresetKey(mealType, mealName) {
            const name = String(mealName || '').trim().toLowerCase();

            if (!name) {
                return '';
            }

            for (const [key, preset] of Object.entries(this.presetOptions(mealType))) {
                if (String(preset.meal_name || '').trim().toLowerCase() === name) {
                    return key;
                }
            }

            return '';
        },

        firstPresetKey(mealType) {
            const keys = Object.keys(this.presetOptions(mealType));

            return keys.length ? keys[0] : '';
        },

        applyPreset(meal, presetKey) {
            const preset = this.presetOptions(meal.meal_type)[presetKey];

            meal.meal_preset = presetKey || '';

            if (!preset) {
                this.touch();
                return;
            }

            meal.meal_name = preset.meal_name || '';
            meal.instructions = preset.instructions || '';
            meal.helpful_links = Array.isArray(meal.helpful_links) ? meal.helpful_links : [];
            meal.calories = preset.calories ?? null;
            meal.protein_grams = preset.protein_grams ?? null;
            meal.carbs_grams = preset.carbs_grams ?? null;
            meal.fat_grams = preset.fat_grams ?? null;
            meal.start_time = preset.start_time || '';
            this.touch();
        },

        applyDefaultPreset(meal) {
            this.applyPreset(meal, this.firstPresetKey(meal.meal_type));
        },

        changeMealType(meal) {
            meal.meal_preset = '';
            this.applyDefaultPreset(meal);
        },

        normalizeDays() {
            this.days = (Array.isArray(this.days) ? this.days : []).map((day, index) => ({
                day_number: Number(day?.day_number || index + 1),
                week_day: day?.week_day || Object.keys(this.weekDays)[index % 7],
                meals: (Array.isArray(day?.meals) ? day.meals : []).map((meal, mealIndex) => {
                    const mealType = meal?.meal_type || 'MORNING';
                    const mealName = meal?.meal_name || '';

                    return {
                        meal_type: mealType,
                        meal_preset: meal?.meal_preset || this.matchingPresetKey(mealType, mealName),
                        meal_name: mealName,
                        instructions: meal?.instructions || '',
                        meal_image: meal?.meal_image || '',
                        helpful_links: (Array.isArray(meal?.helpful_links) ? meal.helpful_links : [])
                            .filter((link) => Boolean(link?.url))
                            .map((link) => ({
                                type: link?.type || 'recipe',
                                title: link?.title || '',
                                url: link?.url || '',
                            })),
                        calories: meal?.calories ?? null,
                        protein_grams: meal?.protein_grams ?? null,
                        carbs_grams: meal?.carbs_grams ?? null,
                        fat_grams: meal?.fat_grams ?? null,
                        start_time: meal?.start_time || '',
                        sort_order: Number(meal?.sort_order ?? mealIndex),
                    };
                }),
            }));
        },

        enforceDurationLimit() {
            const max = this.maxAllowedDays();

            if (this.days.length > max) {
                this.days = this.days.slice(0, max);
                this.selectedDayIndex = Math.min(this.selectedDayIndex, this.days.length - 1);
                this.selectedWeek = Math.max(1, Math.ceil((this.selectedDayIndex + 1) / 7));
            }

            this.touch();
        },

        selectWeek(week) {
            this.selectedWeek = week;
            this.selectedDayIndex = Math.min(this.days.length - 1, (week - 1) * 7);
            this.openMealIndex = 0;
        },

        selectDay(index) {
            this.selectedDayIndex = index;
            this.selectedWeek = Math.max(1, Math.ceil((index + 1) / 7));
            this.openMealIndex = 0;
        },

        addWeek() {
            const remaining = this.maxAllowedDays() - this.days.length;
            const addCount = Math.min(7, Math.max(0, remaining));

            for (let i = 0; i < addCount; i++) {
                this.days.push(this.newDay(this.days.length + 1));
            }

            this.selectedWeek = this.weekTabsCreated();
            this.selectedDayIndex = Math.max(0, this.days.length - addCount);
            this.touch();
        },

        addDay() {
            if (this.days.length >= this.maxAllowedDays()) {
                return;
            }

            this.days.push(this.newDay(this.days.length + 1));
            this.selectedDayIndex = this.days.length - 1;
            this.selectedWeek = this.weekTabsCreated();
            this.openMealIndex = 0;
            this.touch();
        },

        removeDay(index) {
            if (this.days.length <= 1) {
                this.days = [this.newDay(1)];
                this.selectedDayIndex = 0;
                this.touch();
                return;
            }

            this.days.splice(index, 1);
            this.normalizeDays();
            this.selectedDayIndex = Math.min(this.selectedDayIndex, this.days.length - 1);
            this.selectedWeek = Math.max(1, Math.ceil((this.selectedDayIndex + 1) / 7));
            this.touch();
        },

        addMeal() {
            const day = this.selectedDay();

            if (!day) {
                return;
            }

            day.meals = Array.isArray(day.meals) ? day.meals : [];
            const meal = this.newMeal();
            meal.sort_order = day.meals.length;
            day.meals.push(meal);
            this.openMealIndex = day.meals.length - 1;
            this.touch();
        },

        removeMeal(index) {
            const day = this.selectedDay();

            if (!day || !Array.isArray(day.meals)) {
                return;
            }

            day.meals.splice(index, 1);
            day.meals.forEach((meal, mealIndex) => meal.sort_order = mealIndex);
            this.openMealIndex = Math.min(this.openMealIndex, Math.max(0, day.meals.length - 1));
            this.touch();
        },

        addHelpfulLink(meal) {
            meal.helpful_links = Array.isArray(meal.helpful_links) ? meal.helpful_links : [];
            meal.helpful_links.push({ type: 'recipe', title: '', url: '' });
            this.touch();
        },

        removeHelpfulLink(meal, index) {
            if (!Array.isArray(meal.helpful_links)) {
                return;
            }

            meal.helpful_links.splice(index, 1);
            this.touch();
        },

        onMealImageSelected(event, meal) {
            const file = event.target?.files?.[0];
            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                meal.meal_image = String(loadEvent?.target?.result || '');
                this.touch();
            };
            reader.readAsDataURL(file);
        },

        clearMealImage(meal) {
            meal.meal_image = '';
            this.touch();
        },

        copyPreviousWeek() {
            const start = (this.selectedWeek - 1) * 7;

            if (start < 7) {
                return;
            }

            const previous = this.days.slice(start - 7, start);
            previous.forEach((day, offset) => {
                const target = start + offset;

                if (target < this.days.length) {
                    this.days[target].meals = JSON.parse(JSON.stringify(day.meals || []));
                }
            });

            this.touch();
        },
    }"
>
    <div>
        <div class="diet-admin-card-title">Weekly Meal Chart</div>
        <div class="diet-week-chart-subtitle">Only one week opens at a time. Select a day on the left and add or edit meals on the right.</div>
    </div>

    <div class="diet-week-tabs" role="list" aria-label="Week tabs preview">
        <template x-for="week in weekTabs()" :key="week">
            <button
                type="button"
                class="diet-week-tab"
                :class="{ 'is-active': selectedWeek === week }"
                x-text="`Week ${week}`"
                x-on:click="selectWeek(week)"
            ></button>
        </template>
        <button x-show="days.length < maxAllowedDays()" type="button" class="diet-week-tab is-add-week" x-on:click="addWeek()">+ Add Week</button>
    </div>

    <div class="diet-week-stats">
        <div class="diet-week-stat">
            <div class="diet-week-stat-value" x-text="durationDays"></div>
            <div class="diet-week-stat-label">Duration days</div>
        </div>
        <div class="diet-week-stat">
            <div class="diet-week-stat-value" x-text="weekTabsCreated()"></div>
            <div class="diet-week-stat-label">Weeks tab created</div>
        </div>
        <div class="diet-week-stat">
            <div class="diet-week-stat-value" x-text="daysShownInCurrentTab()"></div>
            <div class="diet-week-stat-label">Days shown in current tab</div>
        </div>
        <div class="diet-week-stat">
            <div class="diet-week-stat-value">No scroll</div>
            <div class="diet-week-stat-label">Only selected week visible</div>
        </div>
    </div>

    <div class="diet-chart-heading-row">
        <div>
            <div class="diet-week-pattern-title" x-text="patternHeading()"></div>
            <div class="diet-week-pattern-subtitle">This section updates from Meal Schedule Rule. Add days and meals below.</div>
        </div>
        <div class="diet-chart-actions">
            <button type="button" class="diet-secondary-btn" x-on:click="copyPreviousWeek()" x-show="selectedWeek > 1">Copy from previous week</button>
            <button type="button" class="diet-primary-btn" x-on:click="addMeal()">+ Add meal to selected day</button>
        </div>
    </div>

    <div class="diet-form-chart-shell">
        <aside class="diet-form-day-tabs" aria-label="Days in selected week">
            <div class="diet-form-day-tabs-title">Days</div>
            <template x-for="entry in visibleDays()" :key="entry.index">
                <button
                    type="button"
                    class="diet-form-day-tab"
                    :class="{ 'is-active': selectedDayIndex === entry.index }"
                    x-on:click="selectDay(entry.index)"
                >
                    <span x-text="dayLabel(entry.day, entry.index)"></span>
                    <span x-text="`${mealCount(entry.day)} meals`"></span>
                </button>
            </template>
            <button type="button" class="diet-form-add-day-btn" x-show="days.length < maxAllowedDays()" x-on:click="addDay()">+ Add day</button>
        </aside>

        <section class="diet-form-day-panel">
            <div class="diet-form-day-panel-title">
                <span x-text="selectedDay() ? `${dayLabel(selectedDay(), selectedDayIndex)} Meals - Week ${selectedWeek}` : `Week ${selectedWeek} Meals`"></span>
                <span x-show="selectedDay()" x-text="`${mealCount(selectedDay())} meals`"></span>
            </div>

            <template x-if="selectedDay()">
                <div class="diet-custom-day-editor">
                    <div class="diet-custom-day-grid">
                        <label>
                            <span>Day Number</span>
                            <input type="number" min="1" x-model.number="selectedDay().day_number" x-on:input="touch()">
                        </label>
                        <label>
                            <span>Week Day</span>
                            <select x-model="selectedDay().week_day" x-on:change="touch()">
                                <template x-for="(label, value) in weekDays" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </label>
                        <button type="button" class="diet-danger-btn" x-on:click="removeDay(selectedDayIndex)">Remove day</button>
                    </div>

                    <div class="diet-custom-meals-header">
                        <div>Meals</div>
                        <button type="button" class="diet-primary-btn" x-on:click="addMeal()">Add meal to this day</button>
                    </div>

                    <template x-if="!selectedDay().meals || selectedDay().meals.length === 0">
                        <div class="diet-week-empty">No meals configured for this day.</div>
                    </template>

                    <template x-for="(meal, mealIndex) in selectedDay().meals" :key="mealIndex">
                        <div class="diet-week-meal-row" :class="{ 'is-open': openMealIndex === mealIndex }">
                            <button type="button" class="diet-week-meal-summary" x-on:click="openMealIndex = openMealIndex === mealIndex ? -1 : mealIndex">
                                <strong x-text="`${mealTypeLabel(meal.meal_type)}: ${meal.meal_name || 'New meal'}`"></strong>
                                <span x-text="meal.start_time || 'No time'"></span>
                            </button>

                            <div x-show="openMealIndex === mealIndex">
                                <div class="diet-week-meal-detail-grid">
                                    <label>
                                        <span class="diet-week-meal-label">Meal Type</span>
                                        <select class="diet-week-meal-field" x-model="meal.meal_type" x-on:change="changeMealType(meal)">
                                            <template x-for="(label, value) in mealTypes" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </label>
                                    <label>
                                        <span class="diet-week-meal-label">Meal Name / Food Items</span>
                                        <select class="diet-week-meal-field" x-model="meal.meal_preset" x-on:change="applyPreset(meal, meal.meal_preset)">
                                            <option value="">Custom meal</option>
                                            <template x-for="(preset, key) in presetOptions(meal.meal_type)" :key="key">
                                                <option :value="key" x-text="preset.meal_name"></option>
                                            </template>
                                        </select>
                                    </label>
                                    <label>
                                        <span class="diet-week-meal-label">Start Time</span>
                                        <input class="diet-week-meal-field" type="time" x-model="meal.start_time" x-on:input="touch()">
                                    </label>
                                </div>

                                <div class="diet-week-meal-instructions">
                                    <label>
                                        <span class="diet-week-meal-label">Editable Meal Name / Food Items</span>
                                        <input class="diet-week-meal-field" type="text" x-model="meal.meal_name" x-on:input="meal.meal_preset = ''; touch()" placeholder="Warm lemon water with soaked almonds">
                                    </label>
                                </div>

                                <div class="diet-week-meal-instructions">
                                    <label>
                                        <span class="diet-week-meal-label">Instructions</span>
                                        <textarea class="diet-week-meal-note" x-model="meal.instructions" x-on:input="touch()" placeholder="Meal preparation or portion guidance"></textarea>
                                    </label>
                                </div>

                                <div class="diet-week-meal-instructions">
                                    <label>
                                        <span class="diet-week-meal-label">Meal Image</span>
                                        <div class="diet-meal-image-upload">
                                            <input type="file" accept="image/*" x-on:change="onMealImageSelected($event, meal)">
                                            <button type="button" class="diet-secondary-btn" x-show="meal.meal_image" x-on:click="clearMealImage(meal)">Remove Image</button>
                                        </div>
                                        <template x-if="meal.meal_image">
                                            <img :src="meal.meal_image" class="diet-meal-image-preview" alt="Meal image preview">
                                        </template>
                                    </label>
                                </div>

                                <div class="diet-week-meal-instructions">
                                    <div class="diet-week-meal-links-header">
                                        <span class="diet-week-meal-label">Helpful Links</span>
                                        <button type="button" class="diet-secondary-btn" x-on:click="addHelpfulLink(meal)">Add Link</button>
                                    </div>
                                    <template x-if="!meal.helpful_links || meal.helpful_links.length === 0">
                                        <div class="diet-week-empty">No helpful links added.</div>
                                    </template>
                                    <template x-for="(link, linkIndex) in (meal.helpful_links || [])" :key="`link-${mealIndex}-${linkIndex}`">
                                        <div class="diet-meal-link-row">
                                            <select class="diet-week-meal-field" x-model="link.type" x-on:change="touch()">
                                                <option value="recipe">Recipe Link</option>
                                                <option value="youtube">YouTube Video</option>
                                                <option value="article">Article</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <input class="diet-week-meal-field" type="text" x-model="link.title" x-on:input="touch()" placeholder="Optional title">
                                            <input class="diet-week-meal-field" type="url" x-model="link.url" x-on:input="touch()" placeholder="https://...">
                                            <button type="button" class="diet-danger-btn" x-on:click="removeHelpfulLink(meal, linkIndex)">Remove</button>
                                        </div>
                                    </template>
                                </div>

                                <div class="diet-custom-nutrition-grid">
                                    <label><span>Calories</span><input type="number" min="0" x-model.number="meal.calories" x-on:input="touch()"></label>
                                    <label><span>Protein (g)</span><input type="number" min="0" x-model.number="meal.protein_grams" x-on:input="touch()"></label>
                                    <label><span>Carbs (g)</span><input type="number" min="0" x-model.number="meal.carbs_grams" x-on:input="touch()"></label>
                                    <label><span>Fat (g)</span><input type="number" min="0" x-model.number="meal.fat_grams" x-on:input="touch()"></label>
                                    <button type="button" class="diet-danger-btn" x-on:click="removeMeal(mealIndex)">Remove meal</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </section>
    </div>
</div>
