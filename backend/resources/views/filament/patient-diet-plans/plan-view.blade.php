@php
    $record = $getState();
    $record->loadMissing(['patient', 'doctor.user', 'template', 'days.meals']);

    $patientName = trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')) ?: '—';
    $doctorName = trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? '')) ?: ($record->doctor?->user?->name ?? '—');
    $status = ucfirst((string) $record->status);

    $statusClass = match ((string) $record->status) {
        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'paused' => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-gray-50 text-gray-700 border-gray-200',
    };
@endphp

<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-primary">Assigned Diet Plan</div>
                <h2 class="mt-1 text-2xl font-bold text-gray-950">{{ $record->template_name ?: ($record->template?->name ?? 'Diet Plan') }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-gray-600">{{ $record->template_description ?: 'No template description recorded.' }}</p>
            </div>

            <span class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                {{ $status }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="text-xs font-bold uppercase text-gray-500">Patient</div>
                <div class="mt-1 text-sm font-semibold text-gray-950">{{ $patientName }}</div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="text-xs font-bold uppercase text-gray-500">Doctor</div>
                <div class="mt-1 text-sm font-semibold text-gray-950">Dr. {{ $doctorName }}</div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="text-xs font-bold uppercase text-gray-500">Schedule</div>
                <div class="mt-1 text-sm font-semibold text-gray-950">
                    {{ optional($record->start_date)->format('d M Y') ?: '—' }} - {{ optional($record->end_date)->format('d M Y') ?: '—' }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="text-xs font-bold uppercase text-gray-500">Duration</div>
                <div class="mt-1 text-sm font-semibold text-gray-950">{{ $record->duration_days }} days</div>
            </div>
        </div>

        @if($record->special_instructions)
            <div class="mt-4 rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
                <span class="font-bold">Special instructions:</span> {{ $record->special_instructions }}
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($record->days as $day)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <div>
                        <div class="text-sm font-bold text-gray-950">Day {{ $day->day_number }} - {{ ucfirst(strtolower((string) $day->week_day)) }}</div>
                        <div class="text-xs text-gray-500">{{ optional($day->date)->format('d M Y') ?: 'No date' }}</div>
                    </div>
                    <div class="text-xs font-semibold text-gray-500">{{ $day->meals->count() }} meals</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-white text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Time</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Meal</th>
                                <th class="px-5 py-3">Calories</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($day->meals as $meal)
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-gray-700">{{ $meal->meal_time ?: '—' }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ str_replace('_', ' ', ucfirst(strtolower((string) $meal->meal_type))) }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-gray-950">{{ $meal->meal_name }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $meal->instructions ?: 'No instructions' }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ $meal->calories ? $meal->calories . ' kcal' : '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700">
                                            {{ ucfirst((string) $meal->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                No meal chart days found for this assigned diet plan.
            </div>
        @endforelse
    </div>
</div>
