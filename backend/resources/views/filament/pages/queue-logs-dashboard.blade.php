<x-filament-panels::page>
    <div class="space-y-6">
        <!-- SCREEN 3: Past Queue Records Logs / Audit (Redesigned Dashboard) -->
        @php
            $logs = $this->getQueueLogs();
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Sidebar: Doctors list selection -->
            <div class="lg:col-span-1 lg:sticky lg:top-24 bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-4 shadow-sm flex flex-col max-h-[calc(100vh-120px)] overflow-y-auto">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 px-2">Doctors Directory</h3>
                
                <!-- Search bar for doctors -->
                <div class="mb-4 px-2">
                    <input 
                        wire:model.live="doctorSearchQuery" 
                        type="text" 
                        placeholder="Search doctor..." 
                        class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-250 dark:border-gray-850 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                    />
                </div>

                <div class="space-y-1.5">
                    <!-- All Doctors Option -->
                    <button 
                        wire:click="selectDoctor(null)" 
                        class="w-full text-left px-3 py-2.5 rounded-xl text-xs transition flex items-center justify-between {{ is_null($logDoctorId) ? 'bg-primary text-white font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-950/40 text-primary flex items-center justify-center font-bold text-xs shrink-0 {{ is_null($logDoctorId) ? 'bg-white/20 text-white' : '' }}">ALL</div>
                            <div class="min-w-0">
                                <div class="font-bold truncate text-sm {{ is_null($logDoctorId) ? 'text-white' : 'text-gray-900 dark:text-white' }}">All Doctors</div>
                                <div class="text-[11px] truncate mt-0.5 {{ is_null($logDoctorId) ? 'text-white/80' : 'text-gray-550 dark:text-gray-400' }}">Download combined report</div>
                            </div>
                        </div>
                    </button>
 
                    <!-- List of Doctors -->
                    @foreach($this->getDoctors() as $doc)
                        <button 
                            wire:click="selectDoctor('{{ $doc['id'] }}')" 
                            class="w-full text-left px-3 py-2.5 rounded-xl text-xs transition flex items-center justify-between {{ $logDoctorId === $doc['id'] ? 'bg-primary text-white font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-950/40 text-primary flex items-center justify-center font-bold text-xs shrink-0 {{ $logDoctorId === $doc['id'] ? 'bg-white/20 text-white' : '' }}">
                                    {{ $doc['initials'] }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold truncate text-sm {{ $logDoctorId === $doc['id'] ? 'text-white' : 'text-gray-900 dark:text-white' }}">{{ $doc['name'] }}</div>
                                    <div class="text-[11px] truncate mt-0.5 {{ $logDoctorId === $doc['id'] ? 'text-white/80' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $doc['department'] }} • {{ $doc['log_count_today'] }} logs today
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>


            <!-- Right Main Area: Log feed, filters, export -->
            <div class="lg:col-span-3 space-y-6 min-w-0">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        @if($logDoctorId)
                            @php $currentLogDoc = \App\Models\Doctor::find($logDoctorId); @endphp
                            Audit Trail - Dr. {{ $currentLogDoc?->first_name }} {{ $currentLogDoc?->last_name }}
                        @else
                            All Doctors Audit Trail
                        @endif
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Daily queue log with shift calculation, appointment timing, break deduction, extra time and export report.</p>
                </div>

                <!-- Tabs Navigation -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-2 shadow-sm flex flex-wrap items-center gap-2">
                    <button 
                        wire:click="selectTab('summary')"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition {{ $logTab === 'summary' ? 'bg-primary text-white font-extrabold' : 'text-gray-550 hover:bg-gray-50 dark:hover:bg-gray-850' }}"
                    >
                        Today Summary
                    </button>
                    <button 
                        wire:click="selectTab('timeline')"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition {{ $logTab === 'timeline' ? 'bg-primary text-white font-extrabold' : 'text-gray-550 hover:bg-gray-50 dark:hover:bg-gray-850' }}"
                    >
                        Timeline Logs
                    </button>
                    <button 
                        wire:click="selectTab('consultations')"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition {{ $logTab === 'consultations' ? 'bg-primary text-white font-extrabold' : 'text-gray-550 hover:bg-gray-50 dark:hover:bg-gray-850' }}"
                    >
                        Patient-wise Consultations
                    </button>
                    <button 
                        wire:click="selectTab('download')"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition {{ $logTab === 'download' ? 'bg-primary text-white font-extrabold' : 'text-gray-550 hover:bg-gray-50 dark:hover:bg-gray-850' }}"
                    >
                        Download / Export
                    </button>
                </div>

                <!-- Filter Controls (Always Visible) -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">From Date</label>
                            <input 
                                wire:model.live="logFromDate" 
                                type="date" 
                                class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-250 dark:border-gray-850 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">To Date</label>
                            <input 
                                wire:model.live="logToDate" 
                                type="date" 
                                class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-250 dark:border-gray-850 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Report Type</label>
                            <select 
                                class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-250 dark:border-gray-850 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            >
                                <option value="single">Single doctor daily report</option>
                                <option value="combined">Combined daily report</option>
                            </select>
                        </div>
                        <div>
                            <button 
                                wire:click="applyFilters"
                                type="button"
                                class="w-full py-2 bg-primary hover:bg-primary-500 text-white rounded-xl text-xs font-bold transition shadow-sm"
                            >
                                Apply
                            </button>
                        </div>
                    </div>
                </div>

                @php
                    $isSingleDay = ($logFromDate === $logToDate);
                    $stats = null;
                    if ($logDoctorId && $isSingleDay) {
                        $stats = $this->getTimingStatsForDate($logDoctorId, $logFromDate);
                    }
                @endphp

                <!-- Global OPD Timing Shifts Selector (Always Visible on all tabs if single day & doctor selected) -->
                @if($logDoctorId && $isSingleDay && $stats && !$stats['is_future'] && count($stats['slots']) > 0)
                    <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-550 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Doctor Shift / OPD Availability timing
                            </h3>
                            @if(!is_null($selectedSlotIndex))
                                <button wire:click="$set('selectedSlotIndex', null)" class="text-xs text-primary hover:underline font-bold transition">Clear Selection / Show Full Day</button>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            <!-- Full Day Option Card -->
                            <button 
                                wire:click="$set('selectedSlotIndex', null)" 
                                class="text-left p-3.5 rounded-xl border transition-all duration-200 {{ is_null($selectedSlotIndex) ? 'bg-primary-50/50 border-primary dark:bg-primary-950/20 ring-2 ring-primary/20' : 'bg-gray-50 border-gray-150 hover:bg-gray-100 dark:bg-gray-850 dark:border-gray-800 dark:hover:bg-gray-800' }}"
                            >
                                <div class="font-bold text-[10px] uppercase text-gray-400">Full Day Summary</div>
                                <div class="text-sm font-extrabold mt-1 text-gray-900 dark:text-white">All Shifts Combined</div>
                                <div class="text-[10px] text-gray-450 dark:text-gray-550 mt-1 flex items-center gap-1">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                                    {{ count($stats['slots']) }} Shift(s) Scheduled
                                </div>
                            </button>

                            <!-- Slot Cards -->
                            @foreach($stats['slots'] as $idx => $slot)
                                @php
                                    $isSelected = ($selectedSlotIndex === $idx);
                                @endphp
                                <button 
                                    wire:click="$set('selectedSlotIndex', {{ $idx }})" 
                                    class="text-left p-3.5 rounded-xl border transition-all duration-200 {{ $isSelected ? 'bg-primary-50/50 border-primary dark:bg-primary-950/20 ring-2 ring-primary/20' : 'bg-gray-50 border-gray-150 hover:bg-gray-100 dark:bg-gray-850 dark:border-gray-800 dark:hover:bg-gray-800' }}"
                                >
                                    <div class="font-bold text-[10px] uppercase text-gray-455 flex items-center gap-1.5">
                                        <span>Shift {{ $idx + 1 }}</span>
                                        @if($slot['check_in'])
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        @else
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-extrabold mt-1 text-gray-900 dark:text-white">
                                        {{ $slot['label'] }}
                                    </div>
                                    <div class="text-[10px] text-gray-450 dark:text-gray-550 mt-1 font-semibold">
                                        @if($slot['check_in'])
                                            Attended: {{ $slot['check_in']->format('H:i') }} - {{ $slot['last_app_end'] ? $slot['last_app_end']->format('H:i') : '—' }}
                                        @else
                                            No Attendance Logged
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
                             @if($logTab === 'summary')
                    <!-- TODAY SUMMARY TAB -->
                    @if($logDoctorId)
                        @php
                            if ($stats && !$stats['is_future'] && $isSingleDay) {
                                if (is_null($selectedSlotIndex)) {
                                    $activeSec = $stats['overall']['active_seconds'];
                                    $breakSec = $stats['overall']['total_break_seconds'];
                                    $extraSec = $stats['overall']['extra_seconds'];
                                    $checkIn = $stats['overall']['check_in'];
                                    $lastAppEnd = $stats['overall']['last_app_end'];
                                    $firstConsult = $stats['overall']['first_consult_start'];
                                    $breaks = $stats['overall']['breaks'];
                                    $shiftLabel = count($stats['shift_intervals']) > 0 ? implode(' / ', $stats['shift_intervals']) : 'No Shift Scheduled';
                                    
                                    // Match overall check-in to closest slot for display
                                    $matchedStart = null;
                                    $checkInDiffMin = 0;
                                    if ($checkIn && !empty($stats['slots'])) {
                                        $minDiff = null;
                                        $closestSlot = null;
                                        foreach ($stats['slots'] as $s) {
                                            $diff = abs($checkIn->timestamp - $s['start']->timestamp);
                                            if (is_null($minDiff) || $diff < $minDiff) {
                                                $minDiff = $diff;
                                                $closestSlot = $s;
                                            }
                                        }
                                        if ($closestSlot) {
                                            $matchedStart = $closestSlot['start'];
                                            $checkInDiffMin = round(($checkIn->timestamp - $closestSlot['start']->timestamp) / 60);
                                        }
                                    }
                                    
                                    $matchedEnd = null;
                                    $checkoutDiffMin = 0;
                                    if ($lastAppEnd && !empty($stats['slots'])) {
                                        $minDiff = null;
                                        $closestSlot = null;
                                        foreach ($stats['slots'] as $s) {
                                            $diff = abs($lastAppEnd->timestamp - $s['end']->timestamp);
                                            if (is_null($minDiff) || $diff < $minDiff) {
                                                $minDiff = $diff;
                                                $closestSlot = $s;
                                            }
                                        }
                                        if ($closestSlot) {
                                            $matchedEnd = $closestSlot['end'];
                                            $checkoutDiffMin = round(($lastAppEnd->timestamp - $closestSlot['end']->timestamp) / 60);
                                        }
                                    }
                                } else {
                                    $slot = $stats['slots'][$selectedSlotIndex] ?? null;
                                    $activeSec = $slot ? $slot['active_seconds'] : 0;
                                    $breakSec = $slot ? $slot['total_break_seconds'] : 0;
                                    $extraSec = $slot ? $slot['extra_seconds'] : 0;
                                    $checkIn = $slot ? $slot['check_in'] : null;
                                    $lastAppEnd = $slot ? $slot['last_app_end'] : null;
                                    $firstConsult = $slot ? $slot['first_consult_start'] : null;
                                    $breaks = $slot ? $slot['breaks'] : [];
                                    $shiftLabel = $slot ? $slot['label'] : '—';
                                    
                                    $matchedStart = $slot ? $slot['start'] : null;
                                    $checkInDiffMin = $slot ? $slot['check_in_diff_minutes'] : 0;
                                    $matchedEnd = $slot ? $slot['end'] : null;
                                    $checkoutDiffMin = $slot ? $slot['checkout_diff_minutes'] : 0;
                                }
                            }
                        @endphp

                        @if($stats && $stats['is_future'])
                            <!-- Future Date Message -->
                            <div class="bg-amber-50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 p-6 rounded-2xl border border-amber-250 dark:border-amber-900 text-center font-semibold text-sm">
                                Future Date Selected: Logs and timing calculations are not available for future dates.
                            </div>
                        @elseif($isSingleDay)
                            <!-- Stats Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-gray-450 dark:text-gray-550">SCHEDULED SHIFT</div>
                                    <div class="text-lg font-extrabold text-gray-900 dark:text-white mt-1.5">
                                        {{ $shiftLabel }}
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-gray-455 dark:text-gray-550">ACTUAL ACTIVE TIME</div>
                                    <div class="text-2xl font-extrabold text-primary-600 dark:text-primary-400 mt-1.5">
                                        {{ $this->formatDurationMinutes($activeSec) }}
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-gray-455 dark:text-gray-550">TOTAL BREAK</div>
                                    <div class="text-2xl font-extrabold text-amber-500 mt-1.5">
                                        {{ $this->formatDurationMinutes($breakSec) }}
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-gray-455 dark:text-gray-550">EXTRA TIME</div>
                                    <div class="text-2xl font-extrabold text-red-500 mt-1.5">
                                        {{ $this->formatDurationMinutes($extraSec) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Daily Timing Calculation details -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Daily Timing Calculation</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Check-in -->
                                    <div class="bg-gray-50 dark:bg-gray-850 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                                        <div class="text-[11px] font-bold text-gray-400 dark:text-gray-550 uppercase">Doctor Check-in</div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1.5">
                                            @if($checkIn)
                                                {{ $checkIn->format('H:i') }}
                                                @if($matchedStart)
                                                    @if($checkInDiffMin > 0)
                                                        <span class="text-xs font-semibold text-red-500 ml-2">
                                                            ({{ $checkInDiffMin }} min late from {{ $matchedStart->format('H:i') }} slot start)
                                                        </span>
                                                    @else
                                                        <span class="text-xs font-semibold text-green-500 ml-2">
                                                            (On time / early for {{ $matchedStart->format('H:i') }} slot start)
                                                        </span>
                                                    @endif
                                                @endif
                                            @else
                                                — <span class="text-xs text-gray-400 font-semibold ml-2">(Not checked in)</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Last Appointment End -->
                                    <div class="bg-gray-50 dark:bg-gray-850 p-4 rounded-xl border border-gray-150 dark:border-gray-800">
                                        <div class="text-[11px] font-bold text-gray-400 dark:text-gray-550 uppercase">Last Appointment End</div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1.5">
                                            @if($lastAppEnd)
                                                {{ $lastAppEnd->format('H:i') }}
                                                @if($matchedEnd)
                                                    @if($checkoutDiffMin > 0)
                                                        <span class="text-xs font-semibold text-red-500 ml-2">
                                                            ({{ $checkoutDiffMin }} min extra after {{ $matchedEnd->format('H:i') }} slot end)
                                                        </span>
                                                    @else
                                                        <span class="text-xs font-semibold text-green-500 ml-2">
                                                            (Completed before {{ $matchedEnd->format('H:i') }} slot end)
                                                        </span>
                                                    @endif
                                                @endif
                                            @else
                                                — <span class="text-xs text-gray-400 font-semibold ml-2">(No consultations ended)</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- First Consultation Start -->
                                    <div class="bg-gray-50 dark:bg-gray-850 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                                        <div class="text-[11px] font-bold text-gray-400 dark:text-gray-550 uppercase">First Consultation Start</div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1.5">
                                            @if($firstConsult)
                                                {{ $firstConsult->format('H:i') }}
                                                @if($checkIn)
                                                    @php
                                                        $diffFirstSec = $firstConsult->timestamp - $checkIn->timestamp;
                                                        $diffFirstMin = round($diffFirstSec / 60);
                                                    @endphp
                                                    <span class="text-xs font-semibold text-gray-550 ml-2">
                                                        ({{ $diffFirstMin }} min after check-in)
                                                    </span>
                                                @endif
                                            @else
                                                — <span class="text-xs text-gray-400 font-semibold ml-2">(No consultations started)</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Break Deduction -->
                                    <div class="bg-gray-50 dark:bg-gray-850 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                                        <div class="text-[11px] font-bold text-gray-400 dark:text-gray-550 uppercase">Break Deduction</div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1.5">
                                            @if(count($breaks) > 0)
                                                @php
                                                    $breakList = [];
                                                    foreach($breaks as $b) {
                                                        $breakList[] = $b['start']->format('H:i') . ' - ' . ($b['end'] ? $b['end']->format('H:i') : 'Ongoing');
                                                    }
                                                @endphp
                                                <span class="text-sm font-semibold">{{ implode(', ', $breakList) }}</span>
                                                <span class="text-xs font-bold text-amber-600 ml-2">Total break: {{ round($breakSec / 60) }} minutes</span>
                                            @else
                                                No breaks taken
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Date range list daily stats -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Daily Shift & Timing Log</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-gray-50 dark:bg-gray-850 border-b border-gray-150 dark:border-gray-800 font-bold uppercase text-gray-450 dark:text-gray-500">
                                                <th class="px-4 py-3">Date</th>
                                                <th class="px-4 py-3">Scheduled Shift</th>
                                                <th class="px-4 py-3">Check-in Time</th>
                                                <th class="px-4 py-3">Last Appointment End</th>
                                                <th class="px-4 py-3 text-center">Break Duration</th>
                                                <th class="px-4 py-3 text-center">Active Time</th>
                                                <th class="px-4 py-3 text-center">Extra Time</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-850">
                                            @php
                                                $curr = \Carbon\Carbon::parse($logFromDate)->startOfDay();
                                                $end = \Carbon\Carbon::parse($logToDate)->startOfDay();
                                                $days = [];
                                                while($curr->lte($end)) {
                                                    $days[] = $curr->toDateString();
                                                    $curr->addDay();
                                                }
                                            @endphp
                                            @foreach(array_reverse($days) as $dayStr)
                                                @php
                                                    $dStats = $this->getTimingStatsForDate($logDoctorId, $dayStr);
                                                @endphp
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 text-gray-700 dark:text-gray-300">
                                                    <td class="px-4 py-3 font-semibold">{{ \Carbon\Carbon::parse($dayStr)->format('d/m/Y') }}</td>
                                                    <td class="px-4 py-3">
                                                        @if(count($dStats['shift_intervals']) > 0)
                                                            {{ implode(' / ', $dStats['shift_intervals']) }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 font-mono">{{ (isset($dStats['overall']['check_in']) && $dStats['overall']['check_in']) ? $dStats['overall']['check_in']->format('H:i') : '—' }}</td>
                                                    <td class="px-4 py-3 font-mono">{{ (isset($dStats['overall']['last_app_end']) && $dStats['overall']['last_app_end']) ? $dStats['overall']['last_app_end']->format('H:i') : '—' }}</td>
                                                    <td class="px-4 py-3 text-center font-bold text-amber-600">{{ $this->formatDurationMinutes($dStats['overall']['total_break_seconds'] ?? 0) }}</td>
                                                    <td class="px-4 py-3 text-center font-bold text-primary">{{ $this->formatDurationMinutes($dStats['overall']['active_seconds'] ?? 0) }}</td>
                                                    <td class="px-4 py-3 text-center font-bold text-red-500">{{ $this->formatDurationMinutes($dStats['overall']['extra_seconds'] ?? 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- All doctors consolidated summary list -->
                        <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Doctors Shift & Work Hours Summary</h3>
                            <p class="text-xs text-gray-550 dark:text-gray-400">Showing consolidated timings for all active doctors for the selected date.</p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-850 border-b border-gray-150 dark:border-gray-800 font-bold uppercase text-gray-450 dark:text-gray-550">
                                            <th class="px-4 py-3">Doctor</th>
                                            <th class="px-4 py-3">Department</th>
                                            <th class="px-4 py-3">Check-in Time</th>
                                            <th class="px-4 py-3">Last Appointment End</th>
                                            <th class="px-4 py-3 text-center">Total Break</th>
                                            <th class="px-4 py-3 text-center">Active Time</th>
                                            <th class="px-4 py-3 text-center">Extra Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-850">
                                        @foreach($this->getDoctors() as $doc)
                                            @php
                                                $dStats = $this->getTimingStatsForDate($doc['id'], $logFromDate);
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 text-gray-700 dark:text-gray-300">
                                                <td class="px-4 py-3 font-semibold">{{ $doc['name'] }}</td>
                                                <td class="px-4 py-3 text-gray-500">{{ $doc['department'] }}</td>
                                                <td class="px-4 py-3 font-mono">{{ (isset($dStats['overall']['check_in']) && $dStats['overall']['check_in']) ? $dStats['overall']['check_in']->format('H:i') : '—' }}</td>
                                                <td class="px-4 py-3 font-mono">{{ (isset($dStats['overall']['last_app_end']) && $dStats['overall']['last_app_end']) ? $dStats['overall']['last_app_end']->format('H:i') : '—' }}</td>
                                                <td class="px-4 py-3 text-center font-bold text-amber-600">{{ $this->formatDurationMinutes($dStats['overall']['total_break_seconds'] ?? 0) }}</td>
                                                <td class="px-4 py-3 text-center font-bold text-primary">{{ $this->formatDurationMinutes($dStats['overall']['active_seconds'] ?? 0) }}</td>
                                                <td class="px-4 py-3 text-center font-bold text-red-500">{{ $this->formatDurationMinutes($dStats['overall']['extra_seconds'] ?? 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Excel Export Info Box -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-550">Excel Export Should Include</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-gray-50 dark:bg-gray-850 p-4.5 rounded-xl border border-gray-100 dark:border-gray-800 space-y-2">
                                <h4 class="font-bold text-sm text-gray-800 dark:text-white">Doctor Summary</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-450 leading-relaxed">
                                    Doctor, department, room, shift start/end, check-in, last end, active time, break time, extra time.
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-850 p-4.5 rounded-xl border border-gray-100 dark:border-gray-800 space-y-2">
                                <h4 class="font-bold text-sm text-gray-800 dark:text-white">Patient Rows</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-450 leading-relaxed">
                                    Token, patient, phone, booked time, check-in, start, complete, waiting time, consultation duration, status.
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-850 p-4.5 rounded-xl border border-gray-100 dark:border-gray-800 space-y-2">
                                <h4 class="font-bold text-sm text-gray-800 dark:text-white">Audit Rows</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-450 leading-relaxed">
                                    Action type, action time, old status, new status, performed by, remarks, notification sent yes/no.
                                </p>
                            </div>
                        </div>
                    </div>

                @elseif($logTab === 'timeline')
                    <!-- TIMELINE FEED TAB -->
                    <div class="space-y-4">
                        @if($logs->hasPages())
                            <div class="mb-4">
                                {{ $logs->links('livewire::tailwind') }}
                            </div>
                        @endif
                        <div class="relative pl-8 border-l-2 border-gray-250 dark:border-gray-800 space-y-6">
                            @forelse($logs as $log)
                                @php
                                    $details = $this->getLogDetails($log);
                                    $colorMap = [
                                        'green' => [
                                            'bg' => 'bg-green-500',
                                            'bg-light' => 'bg-green-50 dark:bg-green-950/20',
                                            'text' => 'text-green-700 dark:text-green-400',
                                            'border' => 'border-green-200 dark:border-green-900/60',
                                        ],
                                        'danger' => [
                                            'bg' => 'bg-red-500',
                                            'bg-light' => 'bg-red-50 dark:bg-red-950/20',
                                            'text' => 'text-red-700 dark:text-red-400',
                                            'border' => 'border-red-200 dark:border-red-900/60',
                                        ],
                                        'amber' => [
                                            'bg' => 'bg-amber-500',
                                            'bg-light' => 'bg-amber-50 dark:bg-amber-950/20',
                                            'text' => 'text-amber-700 dark:text-amber-400',
                                            'border' => 'border-amber-200 dark:border-amber-900/60',
                                        ],
                                        'indigo' => [
                                            'bg' => 'bg-indigo-500',
                                            'bg-light' => 'bg-indigo-50 dark:bg-indigo-950/20',
                                            'text' => 'text-indigo-700 dark:text-indigo-400',
                                            'border' => 'border-indigo-200 dark:border-indigo-900/60',
                                        ],
                                        'gray' => [
                                            'bg' => 'bg-gray-500',
                                            'bg-light' => 'bg-gray-100 dark:bg-gray-800',
                                            'text' => 'text-gray-700 dark:text-gray-300',
                                            'border' => 'border-gray-200 dark:border-gray-700',
                                        ],
                                    ];
                                    
                                    $style = $colorMap[$details['color']] ?? $colorMap['gray'];
                                    
                                    $pngIconMap = [
                                        'check_in' => 'checkin.png',
                                        'check_out' => 'no-show.png',
                                        'break_start' => 'skip.png',
                                        'break_end' => 'checkin.png',
                                        'start' => 'start.png',
                                        'complete' => 'completed.png',
                                        'skip' => 'skip.png',
                                        'not_complete' => 'no-show.png',
                                        'revert' => 'checkin.png'
                                    ];
                                    $pngIcon = $pngIconMap[$log->action] ?? 'checkin.png';
                                @endphp
                                
                                <div class="relative">
                                    <!-- Icon Marker Dot -->
                                    <span class="absolute -left-[45px] top-1 flex h-8 w-8 items-center justify-center rounded-full bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-800 shrink-0 z-10">
                                        <img src="/images/queue-images/{{ $pngIcon }}" class="h-4.5 w-4.5 object-contain" alt="{{ $log->action }}" />
                                    </span>
                                    
                                    <!-- Entry Card -->
                                    <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-850 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-md transition-all duration-300">
                                        <div class="space-y-1.5 min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $style['bg-light'] }} {{ $style['text'] }} border {{ $style['border'] }}">
                                                    {{ $details['title'] }}
                                                </span>
                                                
                                                @if(!$logDoctorId && $log->doctor)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-50 text-primary dark:bg-primary-950/20 border border-primary-100 dark:border-primary-900/60">
                                                        Dr. {{ $log->doctor->first_name }}
                                                    </span>
                                                @endif

                                                @if($details['duration'])
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-50 border border-gray-250 text-gray-650 dark:bg-gray-850 dark:border-gray-800 dark:text-gray-300">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Duration: {{ $details['duration'] }}
                                                    </span>
                                                @endif
                                                
                                                <span class="text-[11px] font-semibold text-gray-450 dark:text-gray-500">
                                                    {{ $log->created_at->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-350 leading-relaxed">
                                                {{ $details['desc'] }}
                                            </p>

                                            @if($details['remarks'])
                                                <div class="mt-2 text-xs bg-gray-50 dark:bg-gray-800 rounded-lg p-2.5 border border-gray-150 dark:border-gray-850 text-gray-600 dark:text-gray-450">
                                                    <span class="font-bold text-gray-500 dark:text-gray-400">Note:</span> {{ $details['remarks'] }}
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-1.5 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-800 text-xs shrink-0">
                                            <div class="text-xs font-extrabold text-gray-900 dark:text-white">
                                                {{ $details['time_range'] }}
                                            </div>
                                            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                                By {{ $log->creator ? $log->creator->name : 'System' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center text-gray-500 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    <h3 class="font-semibold text-gray-700 dark:text-gray-300">No events logged yet</h3>
                                    <p class="text-sm text-gray-450 mt-1">Activities will show up here as actions are performed.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($logs->hasPages())
                        <div class="mt-4">
                            {{ $logs->links('livewire::tailwind') }}
                        </div>
                    @endif

                @elseif($logTab === 'consultations')
                    <!-- PATIENT CONSULTATIONS TAB -->
                    @if($logDoctorId)
                        <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Patient-wise Consultation Summary</h3>
                                <p class="text-xs text-gray-550 dark:text-gray-400 mt-0.5">Use this table for auditing patient arrivals, wait times, and consultation durations.</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-850 border-b border-gray-150 dark:border-gray-800 font-bold uppercase text-gray-450 dark:text-gray-500">
                                            <th class="px-4 py-3">Token</th>
                                            <th class="px-4 py-3">Patient</th>
                                            <th class="px-4 py-3">Booked Time</th>
                                            <th class="px-4 py-3">Check-In</th>
                                            <th class="px-4 py-3">Started</th>
                                            <th class="px-4 py-3">Completed</th>
                                            <th class="px-4 py-3 text-center">Waiting Time</th>
                                            <th class="px-4 py-3 text-center">Consult Time</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-850">
                                        @forelse($this->getPatientConsultations() as $appt)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 text-gray-700 dark:text-gray-300">
                                                <td class="px-4 py-3 font-bold text-primary-600 dark:text-primary-400 font-mono">{{ $appt['token'] }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $appt['patient_name'] }}</div>
                                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $appt['phone'] }}</div>
                                                </td>
                                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $appt['booked_time'] }}</td>
                                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $appt['check_in'] ? $appt['check_in']->format('H:i') : '—' }}</td>
                                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $appt['started'] ? $appt['started']->format('H:i') : '—' }}</td>
                                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $appt['completed'] ? $appt['completed']->format('H:i') : '—' }}</td>
                                                <td class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ $appt['waiting_seconds'] !== null ? $this->formatDurationMinutes($appt['waiting_seconds']) : '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ $appt['consult_seconds'] !== null ? $this->formatDurationMinutes($appt['consult_seconds']) : '—' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold 
                                                        {{ $appt['status'] === 'completed' ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 border border-green-200' : '' }}
                                                        {{ $appt['status'] === 'started' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200' : '' }}
                                                        {{ $appt['status'] === 'checkin' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200' : '' }}
                                                        {{ $appt['status'] === 'skipped' ? 'bg-gray-150 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border border-gray-250' : '' }}
                                                        {{ $appt['status'] === 'no_show' ? 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400 border border-red-200' : '' }}
                                                    ">
                                                        {{ $appt['status'] === 'no_show' ? 'No Show' : ($appt['status'] === 'checkin' ? 'Checked-in' : ($appt['status'] === 'started' ? 'Started' : ucfirst($appt['status']))) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 max-w-[150px] truncate" title="{{ $appt['remarks'] }}">
                                                    {{ $appt['remarks'] ?: '—' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="py-8 text-center text-gray-500">No consultation records found for this doctor on selected dates.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <!-- Select doctor view prompt -->
                        <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-800 rounded-2xl p-12 shadow-sm text-center space-y-4">
                            <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div class="max-w-md mx-auto">
                                <h3 class="font-bold text-gray-950 dark:text-white text-lg">Select a Doctor</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please select an individual doctor from the left directory to view patient-by-patient waiting times and consultation durations.</p>
                            </div>
                        </div>
                    @endif

                @elseif($logTab === 'download')
                    <!-- DOWNLOAD / EXPORT TAB -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-850 rounded-2xl p-8 shadow-sm text-center space-y-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div class="max-w-md mx-auto">
                            <h3 class="font-bold text-gray-950 dark:text-white text-lg">Generate Spreadsheet Report</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Export queue operations audit logs, doctor shift calculations, and patient-wise consult logs for the selected date range as a single unified spreadsheet file.</p>
                        </div>
                        <button 
                            wire:click="downloadLogs"
                            class="px-6 py-2.5 bg-primary hover:bg-primary-500 text-white rounded-xl text-xs font-bold transition duration-200 shadow-sm inline-flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Spreadsheet (CSV)
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>