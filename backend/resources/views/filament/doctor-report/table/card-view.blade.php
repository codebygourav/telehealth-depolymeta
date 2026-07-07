@php
    /** @var \App\Models\Appointment $record */
    $record = $getRecord();
    $patient = $record->patient?->user;
    $doctor = $record->doctor?->user;
@endphp

<div
    class="group relative h-full w-full rounded-2xl border border-gray-100 bg-gray-50/80 p-4 shadow-sm transition hover:border-primary-200 hover:bg-white hover:shadow-md dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-primary-700/60 dark:hover:bg-gray-900">
    {{-- Left accent for status --}}
    <div
        class="pointer-events-none absolute inset-y-3 left-2 w-0.5 rounded-full bg-gradient-to-b
        @class([
            'from-emerald-500 to-emerald-400' =>
                (string) $record->status === 'completed' ||
                (string) $record->status === 'confirmed',
            'from-sky-500 to-sky-400' => (string) $record->status === 'scheduled',
            'from-amber-500 to-amber-400' => (string) $record->status === 'no_show',
            'from-rose-500 to-rose-400' => (string) $record->status === 'cancelled',
            'from-gray-400 to-gray-300' => !in_array((string) $record->status, [
                'completed',
                'confirmed',
                'scheduled',
                'no_show',
                'cancelled',
            ]),
        ])">
    </div>

    <div class="ml-2 flex flex-col gap-3">
        {{-- Header: Patient & Doctor --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                    Patient
                </p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    {{ $patient?->name ?? 'Unknown patient' }}
                </h3>

                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-user class="h-4 w-4 text-gray-400" />
                    <span class="font-medium">
                        {{ $doctor?->name ? $doctor->name : 'Unassigned doctor' }}
                    </span>
                </div>
            </div>

            {{-- Date & time --}}
            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                <p class="font-semibold text-gray-800 dark:text-gray-200">
                    {{ optional($record->appointment_date)->format('d M Y') ?? '—' }}
                </p>
                <p class="mt-1 font-mono text-sm text-gray-700 dark:text-gray-100">
                    {{ $record->appointment_time ?: '—' }}
                </p>
            </div>
        </div>

        {{-- Badges row --}}
        <div class="flex flex-wrap items-center gap-2 text-[11px]">
            @php
                $type = (string) $record->consultation_type;
                $status = (string) $record->status;
            @endphp

            @if ($type)
                <span @class([
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-medium capitalize',
                    'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-300' => in_array(
                        $type,
                        ['in_person', 'in-person']),
                    'bg-sky-50 text-sky-700 border border-sky-100 dark:bg-sky-900/20 dark:border-sky-800 dark:text-sky-300' =>
                        $type === 'video',
                    'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200' => !in_array(
                        $type,
                        ['in_person', 'in-person', 'video']),
                ])>
                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                    {{ str_replace('_', ' ', $type) }}
                </span>
            @endif

            @if ($status)
                <span @class([
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-medium capitalize',
                    'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-300' => in_array(
                        $status,
                        ['completed', 'confirmed']),
                    'bg-sky-50 text-sky-700 border border-sky-100 dark:bg-sky-900/20 dark:border-sky-800 dark:text-sky-300' =>
                        $status === 'scheduled',
                    'bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300' => in_array(
                        $status,
                        ['no_show', 'no-show']),
                    'bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-300' =>
                        $status === 'cancelled',
                    'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200' => !in_array(
                        $status,
                        [
                            'completed',
                            'confirmed',
                            'scheduled',
                            'no_show',
                            'no-show',
                            'cancelled',
                        ]),
                ])>
                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                    {{ str_replace('_', ' ', $status) }}
                </span>
            @endif
        </div>

        {{-- Footer row --}}
        <div class="mt-2 flex items-center justify-between">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-600 dark:text-gray-300">Fee</span>
                <span class="ml-1 font-semibold text-gray-900 dark:text-gray-50">
                    ₹{{ number_format((float) $record->fee_amount, 2) }}
                </span>
            </div>

            <div class="flex items-center gap-1 text-[11px] text-primary-700 dark:text-primary-400">
                <span>View appointment</span>
                <x-heroicon-o-arrow-right class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
            </div>
        </div>
    </div>
</div>
