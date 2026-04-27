@props(['appointments' => [], 'title' => 'Appointments', 'selectedDateLabel' => null])

<div class="flex flex-col h-[calc(100vh-161px)]"> {{-- FIXED HEIGHT FOR SCROLLING --}}

    {{-- Sticky Header --}}

    <x-shared.section-header class="bg-primary" type="appointments" :title="$title" :subtitle="$selectedDateLabel ?? \Carbon\Carbon::now()->format('D, d M Y')" :count="count($appointments ?? [])"
        countLabel="Appointment" :sticky="true" />

    {{-- Scrollable Appointments Grid --}}
    <div class="flex-1 overflow-y-auto"> {{-- NOW SCROLLABLE --}}
        @if (!empty($appointments))
            <div class="grid grid-cols-1 gap-4">
                @foreach ($appointments as $appointment)
                    <div class="group relative bg-white rounded-xl border border-gray-200">

                        <div class="p-4">

                            {{-- Patient Info --}}
                            <div class="flex items-center gap-2 mb-2 gfg">
                                <img src="{{ $appointment['avatar'] ?? asset('images/user-avatar.png') }}"
                                    alt="{{ $appointment['patient_name'] ?? 'Patient' }}"
                                    class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-600 group-hover:border-primary-400 transition-colors" />

                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate mb-1">
                                        {{ $appointment['patient_name'] ?? 'Unknown Patient' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ $appointment['patient_email'] ?? 'No email' }}
                                        ({{ $appointment['patient_phone'] }})
                                    </p>

                                </div>

                                {{-- Consultation Type Badge --}}
                                <div class="flex-shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg leading-normal text-xs font-medium
                                        @if ($appointment['consultation_type'] == 'video' || $appointment['consultation_type'] == 'Video') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @else bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 @endif">
                                        {{ ucfirst($appointment['consultation_type'] ?? 'In Person') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Appointment Details --}}
                            <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 py-2">

                                {{-- Date --}}
                                <div class="flex items-center gap-2 text-sm">
                                    <div
                                        class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                        <x-heroicon-o-calendar class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                    </div>
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ \Carbon\Carbon::parse($appointment['date'])->format('D, d M Y') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Time --}}
                                <div class="flex items-center gap-2 text-sm">
                                    <div
                                        class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                        <x-heroicon-o-clock class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                    </div>
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $appointment['start_time'] ?? '—' }}
                                            @if (isset($appointment['end_time']))
                                                - {{ $appointment['end_time'] }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Reason --}}
                                @if (isset($appointment['reason']))
                                    <div class="flex items-center gap-2 text-sm">
                                        <div
                                            class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/50">
                                            <x-heroicon-o-document-text
                                                class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-gray-700 dark:text-gray-300">
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
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No Appointments</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                    There are no appointments scheduled at the moment.
                </p>
            </div>

        @endif
    </div>
</div>
