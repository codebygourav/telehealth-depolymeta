@props([
    'selectedDateLabel' => null,
    'selectedDateSlots' => [],
    'showHeader' => true,
    'gridLayout' => false,
    'title' => 'Doctor OPD Schedules',
])

<div class="col-span-4">
    <div class="space-y-4">
        <x-shared.section-header class="bg-primary" type="calendar" :title="$title" :subtitle="$selectedDateLabel ?? \Carbon\Carbon::now()->format('D, M d')" :count="count($selectedDateSlots)"
            countLabel="OPD" :sticky="true" />

        {{-- Slots Container --}}
        <div
            class="@if ($gridLayout) grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 col-span-4 @else col-span-4 @endif max-h-[calc(100vh-259px)] overflow-y-auto space-y-3 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700">
            @forelse ($selectedDateSlots as $slot)
                <div
                    class="group rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200">
                    <div class="p-4">

                        <div class="flex items-center gap-2 mb-2 hjkhjkhj">
                            <img src="{{ storage_url($slot['avatar'] ?? null) }}" alt="{{ $slot['doctor'] ?? 'Doctor' }}"
                                class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-600 group-hover:border-primary-400 transition-colors" />

                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate mb-1">
                                    {{ $slot['doctor'] ?? 'Dr. Unknown' }}
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                    {{ $slot['departments'] ?? 'General' }}
                                </p>
                                @if (isset($slot['specialization']))
                                    <p class="text-xs text-gray-500 dark:text-gray-500 truncate mt-1">
                                        {{ $slot['specialization'] }}
                                    </p>
                                @endif
                            </div>

                            {{-- Right Side: Consultation Type & Capacity --}}
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
                            </div>
                        </div>

                        {{-- Appointment Details --}}
                        <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 py-2">
                            {{-- Date --}}
                            @if (isset($slot['date_label']) || (isset($slot['is_recurring']) && $slot['is_recurring']))
                                <div class="flex items-center gap-2 text-sm">
                                    @if (isset($slot['is_recurring']) && $slot['is_recurring'])
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                    @else
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-calendar class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            @if (isset($slot['is_recurring']) && $slot['is_recurring'])
                                                {{ $slot['recurring_label'] ?? 'Recurring Weekly' }}
                                            @else
                                                {{ $slot['date_label'] }}
                                            @endif
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
        </div>
    </div>
</div>
