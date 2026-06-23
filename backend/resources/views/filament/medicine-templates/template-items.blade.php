@php
    $template = $getState();
    $items = $template?->items ?? collect();
@endphp

<div class="space-y-3">
    @forelse ($items as $index => $item)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $index + 1 }}. {{ $item->medicine_name }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $item->medicine_type ?: 'Type not specified' }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (($item->use_type ?? 'regular') === 'sos')
                        <div class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            SOS / As Needed
                        </div>
                    @else
                        <div class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            {{ $item->doses_per_day ?: 1 }}x / day
                        </div>
                        <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ $item->frequency ?: 'Frequency not set' }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-3 text-sm text-gray-700 dark:text-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Dosage</div>
                    <div>{{ $item->dosage ?: '-' }}</div>
                </div>
                <div>
                    @if (($item->use_type ?? 'regular') === 'sos')
                        <div class="text-xs uppercase tracking-wide text-gray-500">SOS Details</div>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @if ($item->take_when)
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">When: {{ $item->take_when }}</span>
                            @endif
                            @if ($item->min_gap)
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">Gap: {{ $item->min_gap }}</span>
                            @endif
                            @if ($item->max_doses_per_day)
                                <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-950 dark:text-teal-300">Max: {{ $item->max_doses_per_day }}</span>
                            @endif
                        </div>
                    @else
                        <div class="text-xs uppercase tracking-wide text-gray-500">Timings</div>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @forelse (($item->frequency_times ?? []) as $time)
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ $time }}</span>
                            @empty
                                <span>-</span>
                            @endforelse
                        </div>
                    @endif
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Meal</div>
                    <div>{{ str($item->meal_timing ?: '-')->replace('_', ' ')->title() }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Duration</div>
                    <div>
                        {{ $item->duration_value ? $item->duration_value . ' ' . $item->duration_type : 'No end date' }}
                    </div>
                </div>
            </div>

            @if ($item->instructions)
                <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Instructions</div>
                    {{ $item->instructions }}
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
            No medicines added to this template.
        </div>
    @endforelse
</div>
