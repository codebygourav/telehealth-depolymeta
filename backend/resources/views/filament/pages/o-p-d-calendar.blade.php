<x-filament-panels::page>
    {{-- Header with View Switcher and Filters --}}
    {{-- <div class="mb-6 space-y-4"> --}}

    <x-ui.page-header>

        {{-- Filters --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- View Mode Buttons --}}
        <div class="flex flex-wrap gap-2">
            <x-filament::button wire:click="changeView('day')" :color="$viewMode === 'day' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'day'"
                class="{{ $viewMode === 'day' ? 'font-semibold' : '' }}">
                Day
            </x-filament::button>
            <x-filament::button wire:click="changeView('week')" :color="$viewMode === 'week' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'week'"
                class="{{ $viewMode === 'week' ? 'font-semibold' : '' }}">
                Week
            </x-filament::button>
            <x-filament::button wire:click="changeView('month')" :color="$viewMode === 'month' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'month'"
                class="{{ $viewMode === 'month' ? 'font-semibold' : '' }}">
                Month
            </x-filament::button>
        </div>
        {{--
    </div> --}}
    </x-ui.page-header>

    {{-- Calendar View --}}
    <x-ui.page-body>
        @php
            $timeSlots = collect($selectedDateSlots)
                ->map(fn($slot) => ($slot['start'] && $slot['end']) ? $slot['start'] . ' - ' . $slot['end'] : null)
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        @endphp

        @if (!empty($timeSlots) && count($timeSlots) > 0)
            <div class="mb-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden transition-all duration-200">
                <!-- Top Row: Date & Summary Stats -->
                <div class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary border border-primary-100 dark:border-primary-800 shrink-0">
                            <x-heroicon-o-calendar-days class="w-5.5 h-5.5" />
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-extrabold uppercase tracking-wider">Active Date</span>
                            <div class="text-base font-black text-gray-900 dark:text-white leading-tight">
                                {{ $selectedDateLabel ?? \Carbon\Carbon::now()->format('d F, Y') }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Badge Row -->
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold bg-primary-50 text-primary border border-primary-100 dark:bg-primary-950/30 dark:border-primary-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                            {{ count($selectedDateSlots) }} Total Slots
                        </span>
                        
                        @php
                            $totalBooked = collect($selectedDateSlots)->sum('total_booked');
                            $onlineBooked = collect($selectedDateSlots)->sum('internal_booked');
                            $extBooked = collect($selectedDateSlots)->sum('external_booked');
                        @endphp
                        
                        @if($totalBooked > 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-semibold bg-green-50 text-green-700 border border-green-100 dark:bg-green-950/30 dark:border-green-800">
                                {{ $onlineBooked }} Online
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-semibold bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:border-blue-800">
                                {{ $extBooked }} External
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-semibold bg-gray-50 text-gray-500 border border-gray-100 dark:bg-gray-950/30 dark:border-gray-800">
                                No bookings
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Bottom Row: Time Slots Wrap -->
                <div class="p-4 bg-white dark:bg-gray-900">
                    <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mb-2.5">Filter by OPD Time Slot</span>
                    <div class="flex flex-wrap gap-2.5 select-none">
                        <!-- Overview Button -->
                        <button
                            type="button"
                            wire:click="selectTimeSlot('none')"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold border transition duration-200 flex items-center gap-2 hover:scale-[1.02] active:scale-95 shadow-sm cursor-pointer
                            {{ $selectedTimeSlot === 'none'
                                ? 'bg-primary text-white border-primary ring-2 ring-primary/20 dark:ring-primary/40 font-semibold'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 dark:border-gray-700' }}"
                        >
                            <x-heroicon-o-list-bullet class="w-4.5 h-4.5" />
                            <span>Overview</span>
                        </button>
                        
                        <!-- All Slots Button -->
                        <button
                            type="button"
                            wire:click="selectTimeSlot('all')"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold border transition duration-200 flex items-center gap-2 hover:scale-[1.02] active:scale-95 shadow-sm cursor-pointer
                            {{ $selectedTimeSlot === 'all'
                                ? 'bg-primary text-white border-primary ring-2 ring-primary/20 dark:ring-primary/40 font-semibold'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 dark:border-gray-700' }}"
                        >
                            <x-heroicon-o-squares-2x2 class="w-4.5 h-4.5" />
                            <span>All Slots</span>
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $selectedTimeSlot === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ count($selectedDateSlots) }}
                            </span>
                        </button>
                        
                        <!-- Individual Time Slots -->
                        @foreach ($timeSlots as $ts)
                            @php
                                $matchingSlots = collect($selectedDateSlots)->filter(function($s) use ($ts) {
                                    return ($s['start'] && $s['end']) ? ($s['start'] . ' - ' . $s['end']) === $ts : false;
                                });
                                $count = $matchingSlots->count();
                                $hasBlocked = $matchingSlots->contains('status', 'blocked');
                            @endphp
                            <button
                                type="button"
                                wire:click="selectTimeSlot('{{ $ts }}')"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold border transition duration-200 flex items-center gap-2 hover:scale-[1.02] active:scale-95 shadow-sm cursor-pointer
                                {{ $selectedTimeSlot === $ts
                                    ? 'bg-primary text-white border-primary ring-2 ring-primary/20 dark:ring-primary/40 font-semibold'
                                    : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 dark:border-gray-700' }}"
                            >
                                <x-heroicon-o-clock class="w-4.5 h-4.5" />
                                <span>{{ $ts }}</span>
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $selectedTimeSlot === $ts ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ $count }}
                                </span>
                                @if($hasBlocked && $selectedTimeSlot !== $ts)
                                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Contains blocked slots"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($viewMode === 'day')
            @include('components.calendar.day-view')
        @elseif ($viewMode === 'week')
            @include('components.calendar.week-view')
        @elseif ($viewMode === 'month')
            @include('components.calendar.month-view')
        @endif
    </x-ui.page-body>
</x-filament-panels::page>
