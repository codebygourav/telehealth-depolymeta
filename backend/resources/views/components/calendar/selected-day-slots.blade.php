@props([
    'selectedDateLabel' => null,
    'selectedDateSlots' => [],
    'allSlots' => [],
    'selectedTimeSlot' => 'none',
    'showHeader' => true,
    'gridLayout' => false,
    'title' => 'Doctor OPD Schedules',
])

<div class="col-span-4 flex flex-col h-[calc(100vh-161px)]">
    <x-shared.section-header class="bg-primary shrink-0" type="calendar" :title="$title" :subtitle="$selectedDateLabel ?? \Carbon\Carbon::now()->format('D, M d')" :count="$selectedTimeSlot === 'none' ? count($allSlots) : count($selectedDateSlots)"
        countLabel="OPD" :sticky="true" />

    @if ($selectedTimeSlot !== 'none')
        <!-- Back to Overview Button -->
        <div class="mx-0 mt-0 mb-3 shrink-0 m-0">
            <button type="button" wire:click="selectTimeSlot('none')"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold transition shadow-sm hover:scale-[1.01] active:scale-99 cursor-pointer">
                <x-heroicon-m-arrow-left class="w-4.5 h-4.5 text-primary" />
                <span>Back to Overview (Show All Time Slots)</span>
            </button>
        </div>
    @endif

    {{-- Slots Container --}}
    <div
        class="flex-1 overflow-y-auto p-0 space-y-3 no-scrollbar @if ($gridLayout) grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 @endif">

            @if ($selectedTimeSlot === 'none')
                {{-- OVERVIEW / SELECTOR MODE --}}
                <div class="mb-2 p-3 bg-primary-50/30 border border-primary-100 dark:bg-primary-950/10 dark:border-primary-900/30 rounded-xl flex items-center gap-2 shrink-0">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-primary shrink-0" />
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Click on any schedule card below to view its details and bookings.
                    </span>
                </div>
                @forelse ($allSlots as $slot)
                    @php
                        $slotTime = ($slot['start'] && $slot['end']) ? $slot['start'] . ' - ' . $slot['end'] : 'No Time';
                        $statusClass = $slot['status'] === 'active' 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' 
                            : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
                        $statusLabel = $slot['status'] === 'active' ? 'Available' : 'Blocked';
                        
                        $sourceClass = $slot['source'] === 'override'
                            ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                        $sourceLabel = ucfirst($slot['source']);
                    @endphp
                    <div wire:click="selectTimeSlot('{{ $slotTime }}')"
                        class="group rounded-xl border {{ $slot['status'] === 'blocked' ? 'border-red-200 dark:border-red-900/40 bg-red-50/5' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 cursor-pointer p-4">
                        
                        <div class="flex items-center gap-3">
                            <img src="{{ $slot['avatar'] }}" alt="{{ $slot['doctor'] }}"
                                onerror="this.onerror=null; this.src='{{ asset('images/user-avatar.png') }}';"
                                class="w-10 h-10 rounded-xl object-cover border border-gray-200 dark:border-gray-600 group-hover:border-primary-400 transition-colors" />

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $slot['doctor'] }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $sourceClass }}">
                                        {{ $sourceLabel }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                    {{ $slot['departments'] }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-2.5">
                            <div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-900/30">
                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                    {{ $slotTime }}
                                </span>
                            </div>

                            <div class="text-[11px] font-medium text-gray-600 dark:text-gray-400">
                                @if ($slot['total_booked'] > 0)
                                    <span class="text-primary font-bold">{{ $slot['internal_booked'] }} Online</span> / 
                                    <span class="text-gray-600 dark:text-gray-400">{{ $slot['external_booked'] }} Ext</span>
                                    <span class="text-gray-400">({{ $slot['total_booked'] }} total)</span>
                                @else
                                    <span class="text-gray-400">0 bookings</span>
                                @endif
                                @if (isset($slot['room']) && !empty($slot['room']))
                                    <span class="text-gray-400">| Rm: {{ $slot['room'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-6 px-6 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <x-heroicon-o-calendar-days class="w-10 h-10 text-gray-400" />
                        </div>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No slots available</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">No doctor availability slots exist for this date.</p>
                    </div>
                @endforelse
            @else
                {{-- DETAILED VIEW MODE --}}
                @forelse ($selectedDateSlots as $slot)
                    <div
                        class="group rounded-xl border {{ $slot['status'] === 'blocked' ? 'border-red-200 dark:border-red-900/40 bg-red-50/5' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200">
                        <div class="p-4">

                            <div class="flex items-center gap-2 mb-2 hjkhjkhj">
                                <img src="{{ $slot['avatar'] ?? asset('images/user-avatar.png') }}" alt="{{ $slot['doctor'] ?? 'Doctor' }}"
                                    onerror="this.onerror=null; this.src='{{ asset('images/user-avatar.png') }}';"
                                    class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-600 group-hover:border-primary-400 transition-colors" />

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                            {{ $slot['doctor'] ?? 'Unknown' }}
                                        </h3>
                                        @if ($slot['status'] === 'blocked')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                                Blocked
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                Available
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $slot['source'] === 'override' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                                            {{ ucfirst($slot['source']) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                        {{ $slot['departments'] ?? 'General' }}
                                    </p>
                                    @if (isset($slot['specialization']))
                                        <p class="text-xs text-gray-500 dark:text-gray-500 truncate mt-1">
                                            {{ $slot['specialization'] }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Right Side: Consultation Type & Capacity & Time --}}
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    {{-- Combined Consultation Type + OPD Type Badge --}}
                                    @if (isset($slot['consultation_type']) && isset($slot['opd_type']))
                                        @php
                                            $consultationLabel = ucfirst(str_replace('-', ' ', $slot['consultation_type']));
                                            $opdLabel = ucfirst($slot['opd_type']);
                                        @endphp
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium
                                            {{ $slot['consultation_type'] === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300' }}">
                                            {{ $consultationLabel }} @if ($slot['consultation_type'] !== 'video')
                                                ({{ $opdLabel }})
                                            @endif
                                        </span>
                                    @endif

                                    {{-- Capacity Badge --}}
                                    @if (isset($slot['capacity']))
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-primary bg-primary-100">
                                            {{ $slot['capacity'] }} {{ Str::plural('patient', $slot['capacity']) }}
                                        </span>
                                    @endif

                                    {{-- Time Slot Badge --}}
                                    @if (isset($slot['start']) && isset($slot['end']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-900/30">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                            {{ $slot['start'] }} - {{ $slot['end'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Appointment Details --}}
                            <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 py-2">
                                {{-- Bookings count info --}}
                                <div class="flex items-center gap-2 text-sm">
                                    <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                        <x-heroicon-o-users class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                    </div>
                                    <div class="flex-1 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                        @if ($slot['total_booked'] > 0)
                                            <span class="font-semibold text-primary">{{ $slot['internal_booked'] }} Online</span> / 
                                            <span>{{ $slot['external_booked'] }} External</span>
                                            <span class="text-gray-500">({{ $slot['total_booked'] }} total booked)</span>
                                        @else
                                            <span class="text-gray-500">0 bookings</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Room and Fee --}}
                                @if ((isset($slot['room']) && !empty($slot['room'])) || (isset($slot['fee']) && $slot['fee'] > 0))
                                    <div class="flex items-center gap-2 text-sm">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-home class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                        <div class="flex-1 text-xs text-gray-700 dark:text-gray-300">
                                            @if (isset($slot['room']) && !empty($slot['room']))
                                                <span>Room: <strong class="text-gray-900 dark:text-white">{{ $slot['room'] }}</strong></span>
                                            @endif
                                            @if (isset($slot['fee']) && $slot['fee'] > 0)
                                                <span class="ml-3">Fee: <strong class="text-gray-900 dark:text-white">₹{{ $slot['fee'] }}</strong></span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Date (Only for recurring slots) --}}
                                @if (isset($slot['is_recurring']) && $slot['is_recurring'])
                                    <div class="flex items-center gap-2 text-sm">
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $slot['recurring_label'] ?? 'Recurring Weekly' }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                {{-- Time --}}
                                @if (isset($slot['start']) && isset($slot['end']))
                                    <div class="flex items-center gap-2 text-sm">
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $slot['start'] }} - {{ $slot['end'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                {{-- Notes --}}
                                @if (isset($slot['notes']) && !empty($slot['notes']))
                                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 italic line-clamp-2">
                                            "{{ $slot['notes'] }}"
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="border-gray-100 dark:border-gray-700 flex gap-2">
                                @if (isset($slot['actions']))
                                    {{ $slot['actions'] }}
                                @else
                                    @php
                                        $doctorSlug = $slot['doctor_slug'] ?? ($slot['doctor_id'] ?? null);
                                        $doctorUrl = $doctorSlug
                                            ? route('filament.admin.resources.doctors.view', ['record' => $doctorSlug])
                                            : null;
                                    @endphp
                                    @if ($doctorUrl)
                                        <a target="_blank" href="{{ $doctorUrl }}"
                                            class="flex-1 px-4 py-2 bg-transparent outline outline-primary text-primary text-sm font-medium rounded-lg transition-colors hover:bg-primary-50 dark:hover:bg-primary-900/20 text-center">
                                            View OPD Details
                                        </a>
                                    @else
                                        <button type="button"
                                            class="flex-1 px-4 py-2 bg-transparent outline outline-primary text-primary text-sm font-medium rounded-lg transition-colors">
                                            View OPD Details
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="flex flex-col items-center justify-center py-6 px-6 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div
                            class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <x-heroicon-o-calendar-days class="w-10 h-10 text-gray-400" />
                        </div>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No slots available</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">Select a day to view available time
                            slots
                        </p>
                    </div>
                @endforelse
            @endif
        </div>
</div>
