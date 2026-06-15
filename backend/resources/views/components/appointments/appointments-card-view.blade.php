@props([
    'appointments' => [],
    'title' => 'Appointments',
    'selectedDateLabel' => null,
    'selectedTimeSlot' => 'all',
    'typeCounts' => ['all' => 0, 'online' => 0, 'external' => 0],
    'currentFilter' => 'all',
])

<div class="flex flex-col h-[calc(100vh-161px)]"> {{-- FIXED HEIGHT FOR SCROLLING --}}

    {{-- Sticky Header --}}

    <x-shared.section-header class="bg-primary" type="appointments" :title="$title" :subtitle="$selectedDateLabel ?? \Carbon\Carbon::now()->format('D, d M Y')" :count="count($appointments ?? [])"
        countLabel="Appointment" :sticky="true" />

    {{-- Type Filter Tabs --}}
    @if ($selectedTimeSlot !== 'none')
        <div
            class="mx-0 mt-0 mb-3 shrink-0 p-0.5 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-between gap-1 shadow-sm border border-gray-200 dark:border-gray-700">
            @foreach ([
        'all' => [
            'label' => 'All',
            'activeBadge' => 'bg-white text-primary',
        ],
        'online' => [
            'label' => 'Online',
            'activeBadge' => 'bg-white text-green-600',
        ],
        'external' => [
            'label' => 'External',
            'activeBadge' => 'bg-white text-blue-600',
        ],
    ] as $filterKey => $filter)
                <button type="button" wire:click="setAppointmentFilter('{{ $filterKey }}')"
                    class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all duration-150 flex items-center justify-center gap-1.5 cursor-pointer
                    {{ $currentFilter === $filterKey
                        ? 'bg-primary text-white shadow-xs border border-transparent'
                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-transparent' }}">
                    <span>{{ $filter['label'] }}</span>
                    <span
                        class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[9px] font-black
                        {{ $currentFilter === $filterKey
                            ? $filter['activeBadge']
                            : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">
                        {{ $typeCounts[$filterKey] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Active Filter Banner --}}
    @if ($selectedTimeSlot !== 'none' && $selectedTimeSlot !== 'all')
        <div
            class="mx-0 mt-0 p-3 mb-3 bg-amber-50/50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-900/30 rounded-xl flex items-center justify-between gap-3 shadow-xs shrink-0">
            <div class="flex items-center gap-2">
                <x-heroicon-o-funnel class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Filtered by slot: <strong
                        class="text-amber-800 dark:text-amber-300 font-bold">{{ $selectedTimeSlot }}</strong>
                </span>
            </div>
            <button type="button" wire:click="selectTimeSlot('all')"
                class="text-[10px] font-extrabold text-primary hover:underline uppercase tracking-wider cursor-pointer bg-transparent border-0">
                Clear Filter
            </button>
        </div>
    @elseif ($selectedTimeSlot === 'all')
        <div
            class="mx-0 mt-2 mb-2 p-3 bg-primary-50/55 dark:bg-primary-900/10 border border-primary-100 dark:border-primary-900/30 rounded-xl flex items-center justify-between gap-3 shadow-xs shrink-0">
            <div class="flex items-center gap-2">
                <x-heroicon-o-squares-2x2 class="w-4 h-4 text-primary shrink-0" />
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Showing appointments for <strong class="text-primary font-bold">All Slots</strong>
                </span>
            </div>
        </div>
    @endif

    {{-- Scrollable Appointments Grid --}}
    <div class="flex-1 overflow-y-auto p-0 no-scrollbar"> {{-- NOW SCROLLABLE --}}
        @if (!empty($appointments))
            <div class="grid grid-cols-1 gap-4 p-0">
                @foreach ($appointments as $appointment)
                    <div class="group relative bg-white rounded-xl border border-gray-200 shadow-xs">

                        <div class="p-4">

                            {{-- Patient Info --}}
                            <div class="flex items-center gap-2 mb-2 gfg">
                                <img src="{{ $appointment['avatar'] ?? asset('images/user-avatar.png') }}"
                                    alt="{{ $appointment['patient_name'] ?? 'Patient' }}"
                                    onerror="this.onerror=null; this.src='{{ asset('images/user-avatar.png') }}';"
                                    class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-600 group-hover:border-primary-400 transition-colors" />

                                <div class="flex-1 min-w-0">
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white truncate mb-1 flex flex-wrap items-center gap-1.5">
                                        <span>{{ $appointment['patient_name'] ?? 'Unknown Patient' }}</span>
                                        @if (($appointment['type'] ?? 'online') === 'external')
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-blue-50 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-900/30 uppercase tracking-wider">
                                                External
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-green-50 text-green-800 dark:bg-green-900/40 dark:text-green-300 border border-green-200 dark:border-green-900/30 uppercase tracking-wider">
                                                Online
                                            </span>
                                        @endif
                                    </h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                        @if (!empty($appointment['patient_email']))
                                            {{ $appointment['patient_email'] }}
                                        @endif
                                        @if (!empty($appointment['patient_phone']))
                                            @if (!empty($appointment['patient_email']))
                                                |
                                            @endif
                                            {{ $appointment['patient_phone'] }}
                                        @endif
                                        @if (empty($appointment['patient_email']) && empty($appointment['patient_phone']))
                                            No contact info
                                        @endif
                                    </p>
                                </div>

                                {{-- Consultation Type & Time Slot Badges --}}
                                <div class="flex flex-col items-end gap-1.5 shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg leading-normal text-[10px] font-extrabold uppercase tracking-wider
                                        @if ($appointment['consultation_type'] == 'video' || $appointment['consultation_type'] == 'Video') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @else bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 @endif">
                                        {{ ucfirst($appointment['consultation_type'] ?? 'In Person') }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-900/30 whitespace-nowrap">
                                        <x-heroicon-o-clock class="w-3.5 h-3.5 text-amber-600" />
                                        {{ $appointment['start_time'] ?? '—' }}@if (isset($appointment['end_time']))
                                            -{{ $appointment['end_time'] }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Appointment Details --}}
                            <div class="space-y-3.5 border-t border-gray-200 dark:border-gray-700 py-3 mt-1.5">
                                <div class="grid grid-cols-2 gap-3.5">
                                    {{-- Left Column: Doctor & Unit No --}}
                                    <div class="space-y-3.5">
                                        {{-- Doctor --}}
                                        @if (isset($appointment['doctor_name']))
                                            <div class="flex items-start gap-2 text-sm">
                                                <div
                                                    class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50 shrink-0">
                                                    <x-heroicon-o-user-circle class="w-4 h-4 text-primary" />
                                                </div>
                                                <div class="min-w-0">
                                                    <span
                                                        class="block text-[9px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Doctor</span>
                                                    <span
                                                        class="font-extrabold text-xs text-gray-900 dark:text-white block truncate leading-tight mt-0.5">
                                                        {{ $appointment['doctor_name'] }}
                                                    </span>
                                                    @if (isset($appointment['department']) && !empty($appointment['department']))
                                                        <span
                                                            class="text-[9px] text-gray-500 dark:text-gray-400 truncate block mt-0.5 font-medium leading-none">
                                                            {{ $appointment['department'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Unit Number --}}
                                        @if (isset($appointment['unit_no']) && $appointment['unit_no'] !== '—' && !empty($appointment['unit_no']))
                                            <div class="flex items-start gap-2 text-sm">
                                                <div
                                                    class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50 shrink-0">
                                                    <x-heroicon-o-hashtag class="w-4 h-4 text-primary" />
                                                </div>
                                                <div>
                                                    <span
                                                        class="block text-[9px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Unit
                                                        Number</span>
                                                    <span
                                                        class="font-extrabold text-xs text-gray-900 dark:text-white block mt-0.5 leading-tight">
                                                        {{ $appointment['unit_no'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Right Column: Date & Time --}}
                                    <div class="space-y-3.5">
                                        {{-- Date --}}
                                        <div class="flex items-start gap-2 text-sm">
                                            <div
                                                class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50 shrink-0">
                                                <x-heroicon-o-calendar class="w-4 h-4 text-primary" />
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-[9px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Date</span>
                                                <span
                                                    class="font-extrabold text-xs text-gray-900 dark:text-white block mt-0.5 leading-tight">
                                                    {{ \Carbon\Carbon::parse($appointment['date'])->format('D, d M Y') }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Time --}}
                                        <div class="flex items-start gap-2 text-sm">
                                            <div
                                                class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50 shrink-0">
                                                <x-heroicon-o-clock class="w-4 h-4 text-primary" />
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-[9px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Time</span>
                                                <span
                                                    class="font-extrabold text-xs text-gray-900 dark:text-white block mt-0.5 leading-tight">
                                                    {{ $appointment['start_time'] ?? '—' }}@if (isset($appointment['end_time']))
                                                        -{{ $appointment['end_time'] }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Reason (full width) --}}
                                @if (isset($appointment['reason']) && !empty($appointment['reason']))
                                    <div
                                        class="pt-2.5 border-t border-gray-100 dark:border-gray-800 flex items-start gap-2 text-sm">
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50 shrink-0">
                                            <x-heroicon-o-document-text class="w-4 h-4 text-primary" />
                                        </div>
                                        <div class="flex-1">
                                            <span
                                                class="block text-[9px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Reason
                                                / Notes</span>
                                            <span
                                                class="text-xs text-gray-700 dark:text-gray-300 font-medium block mt-0.5 leading-relaxed">
                                                {{ $appointment['reason'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="border-gray-100 flex gap-2">
                                @if (isset($appointment['actions']))
                                    {{ $appointment['actions'] }}
                                @else
                                    <button type="button"
                                        class="flex-1 px-4 py-2 border-1 border-primary bg-transparent hover:bg-primary text-primary hover:text-white text-sm font-medium rounded-lg transition-all duration-200">
                                        View Appointments Details
                                    </button>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div
                class="flex flex-col items-center justify-center py-6 px-6 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <x-heroicon-o-calendar-days class="w-10 h-10 text-gray-400" />
                </div>
                @if ($selectedTimeSlot === 'none')
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Select a Slot</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                        Please select a time slot on the left to view scheduled patient appointments.
                    </p>
                @else
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No Appointments</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                        There are no appointments scheduled at the moment.
                    </p>
                @endif
            </div>

        @endif
    </div>
</div>
