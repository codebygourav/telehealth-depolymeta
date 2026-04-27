@props(['doctor'])

@php
    use Carbon\Carbon;
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
@endphp

<section class="bg-white border border-gray-100 rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Availability</h2>

    <div class="availability-tabs">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                @foreach ($days as $day)
                    <button type="button" @click="activeTab = '{{ $day }}'"
                        :class="{
                            'border-blue-500 text-blue-600': activeTab === '{{ $day }}',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== '{{ $day }}'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        {{ $day }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="mt-6" x-data="{ activeTab: 'Monday' }">
            @foreach ($days as $day)
                <div x-show="activeTab === '{{ $day }}'" x-cloak>
                    @php
                        $daySlots = collect($doctor->availabilities ?? [])
                            ->filter(fn($slot) => strtolower($slot['day_of_week'] ?? '') === strtolower($day))
                            ->values();
                    @endphp

                    @if ($daySlots->isEmpty())
                        <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                            <x-heroicon-o-calendar class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-500 text-lg font-medium">No availability slots for {{ $day }}
                            </p>
                            <p class="text-gray-400 text-sm mt-1">Add slots to start accepting appointments</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($daySlots as $index => $slot)
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <!-- Time Slot -->
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-100 p-2 rounded-lg">
                                                <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Time Slot</p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $slot['start_time'] ?? '—' }} - {{ $slot['end_time'] ?? '—' }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Capacity -->
                                        <div class="flex items-center gap-3">
                                            <div class="bg-green-100 p-2 rounded-lg">
                                                <x-heroicon-o-user-group class="w-5 h-5 text-green-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Capacity</p>
                                                <p class="text-sm text-gray-600">{{ $slot['capacity'] ?? '—' }} patients
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Consultation Type -->
                                        <div class="flex items-center gap-3">
                                            <div class="bg-purple-100 p-2 rounded-lg">
                                                <x-heroicon-o-computer-desktop class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Type</p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $slot['consultation_type'] === 'in-person' ? 'In-Person' : ($slot['consultation_type'] === 'video' ? 'Video' : ucfirst($slot['consultation_type'] ?? 'In-Person')) }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Recurring Status -->
                                        <div class="flex items-center gap-3">
                                            <div class="bg-orange-100 p-2 rounded-lg">
                                                <x-heroicon-o-arrow-path class="w-5 h-5 text-orange-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Recurring</p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $slot['is_recurring'] ? 'Yes' : 'No' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Details -->
                                    @if ($slot['date'] || $slot['recurring_start_date'])
                                        <div
                                            class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
                                            @if ($slot['date'])
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                                                    <span class="text-sm text-gray-600">
                                                        <strong>Date:</strong>
                                                        {{ Carbon::parse($slot['date'])->format('D, d M Y') }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if ($slot['recurring_start_date'])
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-o-play class="w-4 h-4 text-gray-400" />
                                                    <span class="text-sm text-gray-600">
                                                        <strong>Rec. Start:</strong>
                                                        {{ Carbon::parse($slot['recurring_start_date'])->format('d M Y') }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if ($slot['recurring_end_date'])
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-o-stop class="w-4 h-4 text-gray-400" />
                                                    <span class="text-sm text-gray-600">
                                                        <strong>Rec. End:</strong>
                                                        {{ Carbon::parse($slot['recurring_end_date'])->format('d M Y') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Summary -->
                        <div class="mt-6 bg-blue-50 rounded-lg border border-blue-200 p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
                                    <span class="text-sm font-medium text-blue-900">
                                        {{ $daySlots->count() }} slot(s) available on {{ $day }}
                                    </span>
                                </div>
                                <span class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded-xl">
                                    Total Capacity: {{ $daySlots->sum('capacity') }} patients
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
