@php
    $id = $getId();
    $statePath = $getStatePath();
    $startDateField = $getStartDateField();
    $endDateField = $getEndDateField();

    // Use nullsafe operator only if PHP 8+, otherwise fallback to plain check
    $minDateRaw = $getMinDate();
    $minDate = $minDateRaw ? $minDateRaw->format('Y-m-d') : null;

    $maxDateRaw = $getMaxDate();
    $maxDate = $maxDateRaw ? $maxDateRaw->format('Y-m-d') : null;

    // Get values from separate fields if provided, otherwise from state
    if ($startDateField && $endDateField) {
        try {
            $startDate = $get($startDateField);
            $endDate = $get($endDateField);
        } catch (\Exception $e) {
            $startDate = null;
            $endDate = null;
        }
    } else {
        $state = $getState() ?? ['start_date' => null, 'end_date' => null];
        $startDate = isset($state['start_date']) ? $state['start_date'] : null;
        $endDate = isset($state['end_date']) ? $state['end_date'] : null;
    }

    // Ensure dates are in YYYY-MM-DD format
    $displayStartDate = '';
    $displayEndDate = '';
    if ($startDate) {
        try {
            $displayStartDate = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
        } catch (\Exception $e) {
            // If parsing fails, try to extract date part if it's a datetime string
        if (is_string($startDate)) {
            $displayStartDate = substr($startDate, 0, 10);
        }
    }
}
if ($endDate) {
    try {
        $displayEndDate = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
    } catch (\Exception $e) {
        // If parsing fails, try to extract date part if it's a datetime string
            if (is_string($endDate)) {
                $displayEndDate = substr($endDate, 0, 10);
            }
        }
    }
    $displayText =
        $displayStartDate && $displayEndDate ? $displayStartDate . ' To ' . $displayEndDate : 'Select date range...';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="dateRangePicker({
        statePath: @js($statePath),
        startDateField: @js($startDateField),
        endDateField: @js($endDateField),
        minDate: @js($minDate),
        maxDate: @js($maxDate),
        startDate: @js($displayStartDate),
        endDate: @js($displayEndDate),
    })" class="relative" x-on:click.outside="closePicker()"
        @swipe-next-month.window="nextMonth($event.detail.type)"
        @swipe-prev-month.window="previousMonth($event.detail.type)">
        {{-- Input Field --}}
        <div class="relative">
            <div
                class="block w-full px-3 py-2 transition duration-75 bg-white border border-gray-300 rounded-lg shadow-sm fi-input-wrp ring-1 ring-inset ring-gray-950/10 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 dark:border-gray-700 dark:bg-white/5 dark:ring-white/20 dark:focus-within:border-primary-400 dark:focus-within:ring-primary-400">
                <input type="text" readonly x-model="displayText" @click="togglePicker()"
                    class="block w-full border-none bg-transparent px-0 py-0 text-base text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 disabled:bg-transparent disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 sm:text-sm sm:leading-6"
                    placeholder="Select date range..." />
                <div class="flex items-center gap-2" style="position: absolute; bottom: 0; right: 10px; top: 0;">
                    <button type="button" @click.stop="clearDates()" x-show="startDate && endDate"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        {{ \Filament\Support\generate_icon_html('heroicon-o-x-mark', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-4 h-4'])) }}
                    </button>
                    <button type="button" @click.stop="togglePicker()"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        {{ \Filament\Support\generate_icon_html('heroicon-o-calendar', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-5 h-5'])) }}
                    </button>
                </div>
            </div>

            {{-- Picker Dropdown --}}
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-0 z-50 mt-2 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 md:right-auto"
                style="display: none; min-width: 360px; max-width: 500px; width: 500px;" x-cloak>
                <div class="flex flex-col md:flex-row md:items-start">
                    <div class="flex flex-col flex-1 min-w-0 gap-4 p-2 sm:p-4" style="min-width: 0;">
                        <div class="flex flex-col w-full md:flex-row md:items-start md:justify-center md:gap-8">
                            {{-- Start Date Calendar --}}
                            <div class="flex-1 min-w-[260px] max-w-[320px] mb-4 md:mb-0 md:mr-4" x-data="{
                                touchStartX: 0,
                                touchStartY: 0,
                                isSwiping: false
                            }"
                                @touchstart="touchStartX = $event.touches[0].clientX; touchStartY = $event.touches[0].clientY; isSwiping = false"
                                @touchmove="
                                    if (Math.abs($event.touches[0].clientX - touchStartX) > Math.abs($event.touches[0].clientY - touchStartY)) {
                                        isSwiping = true;
                                    }
                                "
                                @touchend="
                                    if (isSwiping) {
                                        const diff = touchStartX - $event.changedTouches[0].clientX;
                                        if (Math.abs(diff) > 50) {
                                            if (diff > 0) {
                                                $dispatch('swipe-next-month', { type: 'start' });
                                            } else {
                                                $dispatch('swipe-prev-month', { type: 'start' });
                                            }
                                        }
                                        isSwiping = false;
                                    }
                                ">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="previousYear('start')"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation flex items-center">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-left', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-3 h-3 sm:w-4 sm:h-4'])) }}

                                        </button>
                                        <button type="button" @click="previousMonth('start')"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-left', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-4 h-4 sm:w-5 sm:h-5'])) }}
                                        </button>
                                    </div>
                                    <span class="px-2 text-xs font-semibold text-gray-900 sm:text-sm dark:text-gray-100"
                                        x-text="startMonthLabel"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="nextMonth('start')"
                                            :disabled="!canNextStartMonth"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                            :class="!canNextStartMonth ? 'opacity-50 cursor-not-allowed' : ''">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-right', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-4 h-4 sm:w-5 sm:h-5'])) }}
                                        </button>
                                        <button type="button" @click="nextYear('start')" :disabled="!canNextStartYear"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation flex items-center disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                            :class="!canNextStartYear ? 'opacity-50 cursor-not-allowed' : ''">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-right', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-3 h-3 sm:w-4 sm:h-4'])) }}
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']">
                                        <div class="py-1 text-xs font-medium text-center text-gray-500 dark:text-gray-400"
                                            x-text="day"></div>
                                    </template>
                                </div>
                                <div class="grid grid-cols-7 gap-1 transition-all duration-300 touch-pan-y"
                                    id="start-calendar" style="touch-action: pan-y pinch-zoom;"></div>
                            </div>

                            {{-- End Date Calendar --}}
                            <div class="flex-1 min-w-[260px] max-w-[320px]" x-data="{
                                touchStartX: 0,
                                touchStartY: 0,
                                isSwiping: false
                            }"
                                @touchstart="touchStartX = $event.touches[0].clientX; touchStartY = $event.touches[0].clientY; isSwiping = false"
                                @touchmove="
                                    if (Math.abs($event.touches[0].clientX - touchStartX) > Math.abs($event.touches[0].clientY - touchStartY)) {
                                        isSwiping = true;
                                    }
                                "
                                @touchend="
                                    if (isSwiping) {
                                        const diff = touchStartX - $event.changedTouches[0].clientX;
                                        if (Math.abs(diff) > 50) {
                                            if (diff > 0) {
                                                $dispatch('swipe-next-month', { type: 'end' });
                                            } else {
                                                $dispatch('swipe-prev-month', { type: 'end' });
                                            }
                                        }
                                        isSwiping = false;
                                    }
                                ">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="previousYear('end')" :disabled="!canPrevEndYear"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation flex items-center disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                            :class="!canPrevEndYear ? 'opacity-50 cursor-not-allowed' : ''">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-left', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-3 h-3 sm:w-4 sm:h-4'])) }}

                                        </button>
                                        <button type="button" @click="previousMonth('end')"
                                            :disabled="!canPrevEndMonth"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                            :class="!canPrevEndMonth ? 'opacity-50 cursor-not-allowed' : ''">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-left', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-4 h-4 sm:w-5 sm:h-5'])) }}
                                        </button>
                                    </div>
                                    <span class="px-2 text-xs font-semibold text-gray-900 sm:text-sm dark:text-gray-100"
                                        x-text="endMonthLabel"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="nextMonth('end')"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-right', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-4 h-4 sm:w-5 sm:h-5'])) }}
                                        </button>
                                        <button type="button" @click="nextYear('end')"
                                            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors active:scale-95 touch-manipulation flex items-center">
                                            {{ \Filament\Support\generate_icon_html('heroicon-o-chevron-right', attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'w-3 h-3 sm:w-4 sm:h-4'])) }}

                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']">
                                        <div class="py-1 text-xs font-medium text-center text-gray-500 dark:text-gray-400"
                                            x-text="day"></div>
                                    </template>
                                </div>
                                <div class="grid grid-cols-7 gap-1 transition-all duration-300 touch-pan-y"
                                    id="end-calendar" style="touch-action: pan-y pinch-zoom;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function dateRangePicker(config) {
                return {
                    isOpen: false,
                    startDate: config.startDate || '',
                    endDate: config.endDate || '',
                    displayText: config.startDate && config.endDate ? config.startDate + ' To ' + config.endDate :
                        'Select date range...',
                    presetActive: null,
                    startMonth: config.startDate ? new Date(config.startDate + 'T00:00:00').getMonth() : new Date().getMonth(),
                    startYear: config.startDate ? new Date(config.startDate + 'T00:00:00').getFullYear() : new Date()
                        .getFullYear(),
                    endMonth: config.endDate ? new Date(config.endDate + 'T00:00:00').getMonth() : (new Date().getMonth() + 1),
                    endYear: config.endDate ? new Date(config.endDate + 'T00:00:00').getFullYear() : new Date().getFullYear(),
                    updateStateTimeout: null,

                    get startMonthLabel() {
                        var date = new Date(this.startYear, this.startMonth, 1);
                        return date.getFullYear() + ' ' + date.toLocaleDateString('en-US', {
                            month: 'long'
                        });
                    },

                    get endMonthLabel() {
                        var date = new Date(this.endYear, this.endMonth, 1);
                        return date.getFullYear() + ' ' + date.toLocaleDateString('en-US', {
                            month: 'long'
                        });
                    },

                    get isEndBeforeStart() {
                        // Check if end calendar is before start calendar
                        var startDate = new Date(this.startYear, this.startMonth, 1);
                        var endDate = new Date(this.endYear, this.endMonth, 1);
                        return endDate < startDate;
                    },

                    get canNextStartMonth() {
                        // Can't go forward on start calendar if end is before start
                        if (this.isEndBeforeStart) return false;
                        // Can't go forward if start would be after end
                        var nextStartDate = new Date(this.startYear, this.startMonth + 1, 1);
                        var endDate = new Date(this.endYear, this.endMonth, 1);
                        return nextStartDate <= endDate;
                    },

                    get canNextStartYear() {
                        // Can't go forward on start calendar if end is before start
                        if (this.isEndBeforeStart) return false;
                        // Can't go forward if start would be after end
                        var nextStartDate = new Date(this.startYear + 1, this.startMonth, 1);
                        var endDate = new Date(this.endYear, this.endMonth, 1);
                        return nextStartDate <= endDate;
                    },

                    get canPrevEndMonth() {
                        // Can't go backward on end calendar if it's before start
                        if (this.isEndBeforeStart) return false;
                        // Can't go backward if end would be before start
                        var prevEndDate = new Date(this.endYear, this.endMonth - 1, 1);
                        var startDate = new Date(this.startYear, this.startMonth, 1);
                        return prevEndDate >= startDate;
                    },

                    get canPrevEndYear() {
                        // Can't go backward on end calendar if it's before start
                        if (this.isEndBeforeStart) return false;
                        // Can't go backward if end would be before start
                        var prevEndDate = new Date(this.endYear - 1, this.endMonth, 1);
                        var startDate = new Date(this.startYear, this.startMonth, 1);
                        return prevEndDate >= startDate;
                    },

                    init() {
                        // Normalize dates to YYYY-MM-DD format
                        if (this.startDate) {
                            this.startDate = this.normalizeDate(this.startDate);
                        }
                        if (this.endDate) {
                            this.endDate = this.normalizeDate(this.endDate);
                        }

                        // Update display text
                        if (this.startDate && this.endDate) {
                            this.displayText = this.startDate + ' To ' + this.endDate;
                        }

                        this.renderCalendars();
                        this.$watch('startMonth', () => {
                            if (this.isOpen) this.renderCalendars();
                        });
                        this.$watch('startYear', () => {
                            if (this.isOpen) this.renderCalendars();
                        });
                        this.$watch('endMonth', () => {
                            if (this.isOpen) this.renderCalendars();
                        });
                        this.$watch('endYear', () => {
                            if (this.isOpen) this.renderCalendars();
                        });
                        this.$watch('startDate', () => {
                            if (this.startDate) {
                                this.startDate = this.normalizeDate(this.startDate);
                            }
                            if (this.isOpen) this.renderCalendars();
                            // Update DateRangePicker state when startDate changes
                            if (this.startDate && this.endDate) {
                                this.updateDateRangeState();
                            }
                        });
                        this.$watch('endDate', () => {
                            if (this.endDate) {
                                this.endDate = this.normalizeDate(this.endDate);
                            }
                            if (this.isOpen) this.renderCalendars();
                            // Update DateRangePicker state when endDate changes
                            if (this.startDate && this.endDate) {
                                this.updateDateRangeState();
                            }
                        });

                        // Ensure values are set on form submit - use capture phase to run early
                        const form = this.$el.closest('form');
                        if (form) {
                            // Use capture phase to ensure this runs before other handlers
                            form.addEventListener('submit', (e) => {
                                // Force sync before form submission - don't prevent default
                                // Always try to update state, even if dates seem missing
                                // This ensures any dates that are set get synced
                                this.updateState(true); // Force update

                                // CRITICAL: Always update the DateRangePicker's state on form submit
                                // This ensures date_range has the actual values, not null
                                this.updateDateRangeState();
                            }, {
                                once: false,
                                capture: true
                            });
                        }

                        // Re-render calendars after Livewire updates to prevent them from disappearing
                        if (window.Livewire) {
                            const self = this;

                            // Listen for Livewire updates and re-render calendars
                            document.addEventListener('livewire:update', () => {
                                if (self.isOpen) {
                                    // Use multiple attempts to ensure calendars are rendered
                                    setTimeout(() => {
                                        if (self.isOpen) {
                                            self.renderCalendars();
                                        }
                                    }, 50);
                                    setTimeout(() => {
                                        if (self.isOpen) {
                                            self.renderCalendars();
                                        }
                                    }, 200);
                                }
                            });

                            document.addEventListener('livewire:load', () => {
                                if (self.isOpen) {
                                    setTimeout(() => {
                                        if (self.isOpen) {
                                            self.renderCalendars();
                                        }
                                    }, 100);
                                }
                            });

                            // Also listen for wire:model updates on our inputs
                            this.$nextTick(() => {
                                const startInput = document.querySelector(
                                    `input[wire\\:model*="${config.startDateField}"]`);
                                const endInput = document.querySelector(
                                    `input[wire\\:model*="${config.endDateField}"]`);

                                if (startInput) {
                                    startInput.addEventListener('change', () => {
                                        if (self.isOpen) {
                                            setTimeout(() => {
                                                if (self.isOpen) {
                                                    self.renderCalendars();
                                                }
                                            }, 100);
                                        }
                                    });
                                }

                                if (endInput) {
                                    endInput.addEventListener('change', () => {
                                        if (self.isOpen) {
                                            setTimeout(() => {
                                                if (self.isOpen) {
                                                    self.renderCalendars();
                                                }
                                            }, 100);
                                        }
                                    });
                                }
                            });
                        }
                    },

                    togglePicker() {
                        this.isOpen = !this.isOpen;
                        if (this.isOpen) {
                            this.renderCalendars();
                        }
                    },

                    closePicker() {
                        this.isOpen = false;
                    },

                    clearDates() {
                        this.startDate = '';
                        this.endDate = '';
                        this.displayText = 'Select date range...';
                        this.presetActive = null;
                        this.updateState();
                    },

                    selectPreset(preset) {
                        this.presetActive = preset;
                        var today = new Date();
                        today.setHours(0, 0, 0, 0);

                        var start, end;

                        switch (preset) {
                            case 'today':
                                start = end = new Date(today);
                                break;
                            case 'yesterday':
                                start = end = new Date(today);
                                start.setDate(start.getDate() - 1);
                                break;
                            case 'thisWeek':
                                start = new Date(today);
                                start.setDate(start.getDate() - start.getDay());
                                end = new Date(start);
                                end.setDate(end.getDate() + 6);
                                break;
                            case 'lastWeek':
                                start = new Date(today);
                                start.setDate(start.getDate() - start.getDay() - 7);
                                end = new Date(start);
                                end.setDate(end.getDate() + 6);
                                break;
                            case 'last30Days':
                                start = new Date(today);
                                start.setDate(start.getDate() - 30);
                                end = new Date(today);
                                break;
                            case 'thisMonth':
                                start = new Date(today.getFullYear(), today.getMonth(), 1);
                                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                                break;
                            case 'lastMonth':
                                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                                end = new Date(today.getFullYear(), today.getMonth(), 0);
                                break;
                            case 'thisQuarter':
                                var quarter = Math.floor(today.getMonth() / 3);
                                start = new Date(today.getFullYear(), quarter * 3, 1);
                                end = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
                                break;
                            case 'lastQuarter':
                                var lastQuarter = Math.floor(today.getMonth() / 3) - 1;
                                start = new Date(today.getFullYear(), lastQuarter * 3, 1);
                                end = new Date(today.getFullYear(), (lastQuarter + 1) * 3, 0);
                                break;
                            case 'thisYear':
                                start = new Date(today.getFullYear(), 0, 1);
                                end = new Date(today.getFullYear(), 11, 31);
                                break;
                            case 'lastYear':
                                start = new Date(today.getFullYear() - 1, 0, 1);
                                end = new Date(today.getFullYear() - 1, 11, 31);
                                break;
                        }

                        if (start && end) {
                            this.startDate = this.formatDate(start);
                            this.endDate = this.formatDate(end);
                            this.startMonth = start.getMonth();
                            this.startYear = start.getFullYear();
                            this.endMonth = end.getMonth();
                            this.endYear = end.getFullYear();
                            this.displayText = this.startDate + ' To ' + this.endDate;
                            this.updateState();
                        }
                    },

                    normalizeDate(dateInput) {
                        // Normalize date string to YYYY-MM-DD format
                        if (!dateInput) return '';
                        if (typeof dateInput === 'string') {
                            // If it's already in YYYY-MM-DD format, return as is
                            if (/^\d{4}-\d{2}-\d{2}$/.test(dateInput)) {
                                return dateInput;
                            }
                            // If it's a datetime string, extract just the date part
                            if (dateInput.length >= 10) {
                                return dateInput.substring(0, 10);
                            }
                            // Try to parse it
                            try {
                                var date = new Date(dateInput);
                                if (!isNaN(date.getTime())) {
                                    return this.formatDate(date);
                                }
                            } catch (e) {
                                // Ignore parsing errors
                            }
                        }
                        return dateInput;
                    },

                    formatDate(date) {
                        return date.getFullYear() + '-' +
                            String(date.getMonth() + 1).padStart(2, '0') + '-' +
                            String(date.getDate()).padStart(2, '0');
                    },

                    selectDate(date, type) {
                        // Normalize the selected date
                        date = this.normalizeDate(date);
                        if (!date) return; // Don't proceed if date is invalid

                        // Ensure picker stays open
                        this.isOpen = true;

                        var normalizedStartDate = this.startDate ? this.normalizeDate(this.startDate) : '';
                        var normalizedEndDate = this.endDate ? this.normalizeDate(this.endDate) : '';

                        // If both dates are already selected, reset and start new selection
                        if (normalizedStartDate && normalizedEndDate) {
                            // If clicking on start calendar, set as new start date
                            // If clicking on end calendar, set as new start date (will select end next)
                            this.startDate = date;
                            this.endDate = '';
                            this.displayText = date + ' To ...';
                            this.renderCalendars();
                            this.debouncedUpdateState();
                            return;
                        }

                        // Normal selection logic
                        if (type === 'start') {
                            // Clicking on start calendar
                            if (normalizedEndDate && date > normalizedEndDate) {
                                // New start date is after end date, reset end date
                                this.startDate = date;
                                this.endDate = '';
                                this.displayText = date + ' To ...';
                            } else {
                                // Set or update start date
                                this.startDate = date;
                                if (normalizedEndDate) {
                                    this.displayText = this.startDate + ' To ' + normalizedEndDate;
                                } else {
                                    this.displayText = this.startDate + ' To ...';
                                }
                            }
                        } else {
                            // Clicking on end calendar
                            if (!normalizedStartDate) {
                                // No start date yet, set as start date
                                this.startDate = date;
                                this.endDate = '';
                                this.displayText = date + ' To ...';
                            } else if (date >= normalizedStartDate) {
                                // Valid end date (after or equal to start) - this works for same month too
                                this.endDate = date;
                                this.displayText = normalizedStartDate + ' To ' + this.endDate;
                            } else {
                                // End date before start date, set as new start date
                                this.startDate = date;
                                this.endDate = '';
                                this.displayText = date + ' To ...';
                            }
                        }

                        // Re-render calendars immediately to show the selection
                        this.renderCalendars();

                        // Update state immediately (don't debounce) to ensure values are saved
                        // This ensures the DateRangePicker's own state is updated
                        this.updateState();

                        // Also explicitly update the DateRangePicker's state to ensure it's saved
                        this.updateDateRangeState();
                    },

                    updateDateRangeState() {
                        // Always update the DateRangePicker's own state (date_range field)
                        // This is critical for the form submission to work
                        if (this.startDate && this.endDate) {
                            const normalizedStartDate = this.normalizeDate(this.startDate);
                            const normalizedEndDate = this.normalizeDate(this.endDate);

                            if (normalizedStartDate && normalizedEndDate) {
                                const componentInput = document.querySelector(
                                    `input[wire\\:model="${config.statePath}"], input[wire\\:model*="${config.statePath}"]`
                                );

                                if (componentInput) {
                                    const stateValue = JSON.stringify({
                                        start_date: normalizedStartDate,
                                        end_date: normalizedEndDate
                                    });

                                    // Always update, even if value seems the same
                                    componentInput.value = stateValue;

                                    // Trigger events to ensure Livewire picks it up
                                    componentInput.dispatchEvent(new Event('input', {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                    componentInput.dispatchEvent(new Event('change', {
                                        bubbles: true,
                                        cancelable: true
                                    }));

                                    // Also try wire:model update if available
                                    if (window.Livewire) {
                                        try {
                                            const wireModel = componentInput.getAttribute('wire:model') ||
                                                componentInput.getAttribute('wire:model.defer');
                                            if (wireModel) {
                                                const livewireComponent = componentInput.closest('[wire\\:id]');
                                                if (livewireComponent) {
                                                    const componentId = livewireComponent.getAttribute('wire:id');
                                                    const component = window.Livewire.find(componentId);
                                                    if (component) {
                                                        // Update via Livewire's set method
                                                        component.set(wireModel, {
                                                            start_date: normalizedStartDate,
                                                            end_date: normalizedEndDate
                                                        });
                                                    }
                                                }
                                            }
                                        } catch (e) {
                                            // Silently fail - DOM update should be enough
                                        }
                                    }
                                }
                            }
                        }
                    },

                    debouncedUpdateState() {
                        // Clear any existing timeout
                        if (this.updateStateTimeout) {
                            clearTimeout(this.updateStateTimeout);
                        }

                        // Set a new timeout to update state after a delay
                        // This prevents immediate Livewire re-renders that clear the calendars
                        this.updateStateTimeout = setTimeout(() => {
                            this.updateState();
                            // Re-render calendars after state update to ensure they stay visible
                            this.$nextTick(() => {
                                if (this.isOpen) {
                                    this.renderCalendars();
                                }
                            });
                        }, 300);
                    },

                    updateState(forceUpdate = false) {
                        // Normalize dates before updating state
                        var normalizedStartDate = this.startDate ? this.normalizeDate(this.startDate) : '';
                        var normalizedEndDate = this.endDate ? this.normalizeDate(this.endDate) : '';

                        // Only skip if dates are not set AND we're not forcing an update
                        if ((!normalizedStartDate || !normalizedEndDate) && !forceUpdate) {
                            return; // Don't update if dates are not set
                        }

                        if (config.startDateField && config.endDateField) {
                            // Try multiple methods to find and update the date picker inputs
                            // Method 1: Find by wire:model attribute (most common in Filament)
                            let startInput = document.querySelector(
                                `input[wire\\:model*="${config.startDateField}"], input[wire\\:model="${config.startDateField}"]`
                            );
                            let endInput = document.querySelector(
                                `input[wire\\:model*="${config.endDateField}"], input[wire\\:model="${config.endDateField}"]`
                            );

                            // Method 2: Find by name attribute
                            if (!startInput) {
                                startInput = document.querySelector(
                                    `input[name="${config.startDateField}"], input[name*="${config.startDateField}"]`);
                            }
                            if (!endInput) {
                                endInput = document.querySelector(
                                    `input[name="${config.endDateField}"], input[name*="${config.endDateField}"]`);
                            }

                            // Method 3: Find by data attribute or ID
                            if (!startInput) {
                                startInput = document.querySelector(
                                    `[data-field-name="${config.startDateField}"], #${config.startDateField}`);
                            }
                            if (!endInput) {
                                endInput = document.querySelector(
                                    `[data-field-name="${config.endDateField}"], #${config.endDateField}`);
                            }

                            // Method 4: Find by Filament's field wrapper
                            if (!startInput) {
                                const startWrapper = document.querySelector(`[data-field-wrapper="${config.startDateField}"]`);
                                if (startWrapper) {
                                    startInput = startWrapper.querySelector('input[type="text"], input[type="date"]');
                                }
                            }
                            if (!endInput) {
                                const endWrapper = document.querySelector(`[data-field-wrapper="${config.endDateField}"]`);
                                if (endWrapper) {
                                    endInput = endWrapper.querySelector('input[type="text"], input[type="date"]');
                                }
                            }

                            // Method 5: Search through all inputs in the form (fallback)
                            if (!startInput || !endInput) {
                                const form = this.$el.closest('form');
                                if (form) {
                                    const allInputs = form.querySelectorAll(
                                        'input[type="text"], input[type="date"], input[type="hidden"]');
                                    allInputs.forEach(input => {
                                        const wireModel = input.getAttribute('wire:model') || input.getAttribute(
                                            'wire:model.defer') || '';
                                        const name = input.getAttribute('name') || '';

                                        if (!startInput && (wireModel.includes(config.startDateField) || name.includes(
                                                config.startDateField))) {
                                            startInput = input;
                                        }
                                        if (!endInput && (wireModel.includes(config.endDateField) || name.includes(
                                                config.endDateField))) {
                                            endInput = input;
                                        }
                                    });
                                }
                            }

                            // Update start date input
                            if (startInput) {
                                if (startInput.value !== normalizedStartDate) {
                                    startInput.value = normalizedStartDate;

                                    // Trigger input event for Livewire
                                    startInput.dispatchEvent(new Event('input', {
                                        bubbles: true,
                                        cancelable: true
                                    }));

                                    // Trigger change event for Livewire
                                        startInput.dispatchEvent(new Event('change', {
                                            bubbles: true,
                                            cancelable: true
                                        }));

                                    // Trigger additional events to ensure Filament picks up the change
                                    startInput.dispatchEvent(new Event('keyup', {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                }
                            }

                            // Update end date input
                            if (endInput) {
                                if (endInput.value !== normalizedEndDate) {
                                    endInput.value = normalizedEndDate;

                                    // Trigger input event for Livewire
                                    endInput.dispatchEvent(new Event('input', {
                                        bubbles: true,
                                        cancelable: true
                                    }));

                                    // Trigger change event for Livewire
                                        endInput.dispatchEvent(new Event('change', {
                                            bubbles: true,
                                            cancelable: true
                                        }));

                                    // Trigger additional events to ensure Filament picks up the change
                                    endInput.dispatchEvent(new Event('keyup', {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                }
                            }

                            // Also update the DateRangePicker's own state to trigger afterStateUpdated
                            // This ensures the sync happens even if the hidden fields aren't found
                            const componentInput = document.querySelector(
                                `input[wire\\:model="${config.statePath}"], input[wire\\:model*="${config.statePath}"]`);
                            if (componentInput) {
                                const stateValue = JSON.stringify({
                                    start_date: normalizedStartDate,
                                    end_date: normalizedEndDate
                                });
                                if (componentInput.value !== stateValue) {
                                    componentInput.value = stateValue;
                                    componentInput.dispatchEvent(new Event('input', {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                    componentInput.dispatchEvent(new Event('change', {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                }
                            }
                        } else {
                            // Update the component's own state
                            const componentInput = document.querySelector(`input[wire\\:model="${config.statePath}"]`);
                            if (componentInput) {
                                componentInput.value = JSON.stringify({
                                    start_date: normalizedStartDate,
                                    end_date: normalizedEndDate
                                });
                                componentInput.dispatchEvent(new Event('input', {
                                    bubbles: true
                                }));
                            }
                        }
                    },

                    previousMonth(type) {
                        if (type === 'start') {
                            this.startMonth--;
                            if (this.startMonth < 0) {
                                this.startMonth = 11;
                                this.startYear--;
                            }
                        } else {
                            this.endMonth--;
                            if (this.endMonth < 0) {
                                this.endMonth = 11;
                                this.endYear--;
                            }
                        }
                        this.renderCalendars();
                    },

                    nextMonth(type) {
                        if (type === 'start') {
                            this.startMonth++;
                            if (this.startMonth > 11) {
                                this.startMonth = 0;
                                this.startYear++;
                            }
                        } else {
                            this.endMonth++;
                            if (this.endMonth > 11) {
                                this.endMonth = 0;
                                this.endYear++;
                            }
                        }
                        this.renderCalendars();
                    },

                    previousYear(type) {
                        if (type === 'start') {
                            this.startYear--;
                        } else {
                            this.endYear--;
                        }
                        this.renderCalendars();
                    },

                    nextYear(type) {
                        if (type === 'start') {
                            this.startYear++;
                        } else {
                            this.endYear++;
                        }
                        this.renderCalendars();
                    },

                    renderCalendars() {
                        // Only render if picker is open
                        if (!this.isOpen) return;

                        // Use requestAnimationFrame to ensure DOM is ready
                        requestAnimationFrame(() => {
                            const startContainer = document.getElementById('start-calendar');
                            const endContainer = document.getElementById('end-calendar');

                            if (startContainer) {
                                this.renderCalendar('start', this.startMonth, this.startYear, 'start-calendar');
                            }
                            if (endContainer) {
                                this.renderCalendar('end', this.endMonth, this.endYear, 'end-calendar');
                            }
                        });
                    },

                    renderCalendar(type, month, year, containerId) {
                        var container = document.getElementById(containerId);
                        if (!container) {
                            // If container doesn't exist, try again after a short delay
                            setTimeout(() => {
                                this.renderCalendars();
                            }, 50);
                            return;
                        }

                        // Clear container safely
                        while (container.firstChild) {
                            container.removeChild(container.firstChild);
                        }

                        var firstDay = new Date(year, month, 1).getDay();
                        var daysInMonth = new Date(year, month + 1, 0).getDate();
                        var daysInPrevMonth = new Date(year, month, 0).getDate();

                        // Previous month days
                        for (var i = firstDay - 1; i >= 0; i--) {
                            var day = daysInPrevMonth - i;
                            var date = new Date(year, month - 1, day);
                            var dateStr = this.formatDate(date);
                            var btn = this.createDayButton(day, dateStr, type, true);
                            container.appendChild(btn);
                        }

                        // Current month days
                        for (var day = 1; day <= daysInMonth; day++) {
                            var date = new Date(year, month, day);
                            var dateStr = this.formatDate(date);
                            var btn = this.createDayButton(day, dateStr, type, false);
                            container.appendChild(btn);
                        }

                        // Next month days
                        var totalCells = container.children.length;
                        var remaining = 42 - totalCells;
                        for (var day = 1; day <= remaining; day++) {
                            var date = new Date(year, month + 1, day);
                            var dateStr = this.formatDate(date);
                            var btn = this.createDayButton(day, dateStr, type, true);
                            container.appendChild(btn);
                        }
                    },

                    createDayButton(day, dateStr, type, isOtherMonth) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className =
                            'w-8 h-8 sm:w-10 sm:h-10 text-xs sm:text-sm rounded-md transition-all duration-200 active:scale-95 touch-manipulation ' +
                            (isOtherMonth ? 'text-gray-400 dark:text-gray-600' :
                                'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600'
                            );

                        // Normalize dates for comparison
                        var normalizedStartDate = this.startDate ? this.normalizeDate(this.startDate) : '';
                        var normalizedEndDate = this.endDate ? this.normalizeDate(this.endDate) : '';
                        var normalizedDateStr = this.normalizeDate(dateStr);

                        var isStart = normalizedStartDate === normalizedDateStr;
                        var isEnd = normalizedEndDate === normalizedDateStr;
                        var isInRange = normalizedStartDate && normalizedEndDate &&
                            normalizedDateStr >= normalizedStartDate &&
                            normalizedDateStr <= normalizedEndDate;

                        if (isStart || isEnd) {
                            btn.className += ' bg-primary-500 text-white font-semibold shadow-md scale-105';
                        } else if (isInRange) {
                            btn.className += ' bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300';
                        }

                        btn.textContent = day;

                        // Add touch and click handlers
                        var touchStartTime = 0;
                        btn.addEventListener('touchstart', function(e) {
                            touchStartTime = Date.now();
                            e.preventDefault();
                        }, {
                            passive: false
                        });

                        var self = this;
                        btn.addEventListener('touchend', function(e) {
                            var touchDuration = Date.now() - touchStartTime;
                            if (touchDuration < 300) { // Quick tap
                                e.preventDefault();
                                e.stopPropagation();
                                // Ensure picker stays open
                                self.isOpen = true;
                                self.selectDate(dateStr, type);
                            }
                        }, {
                            passive: false
                        });

                        btn.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            // Ensure picker stays open
                            self.isOpen = true;
                            self.selectDate(dateStr, type);
                        };

                        // Check min/max date constraints
                        if (config.minDate && dateStr < config.minDate) {
                            btn.disabled = true;
                            btn.className += ' opacity-50 cursor-not-allowed';
                        }
                        if (config.maxDate && dateStr > config.maxDate) {
                            btn.disabled = true;
                            btn.className += ' opacity-50 cursor-not-allowed';
                        }

                        return btn;
                    }
                }
            }
        </script>
</x-dynamic-component>
