@props(['record'])

@php
    use App\Models\DepartmentDoctor;
    use App\Models\Doctor;

    $departmentId = $record->id;

    // Load doctors for this department
    $pivots = DepartmentDoctor::where('department_id', $departmentId)->with('doctor.user')->get();

    $doctors = $pivots
        ->map(function ($pivot) {
            $doctor = $pivot->doctor;
            if (!$doctor) {
                return null;
            }

            $user = $doctor->user;
            $name =
                $user?->name ?? trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? '')) ?:
                'Unknown Doctor';
            $qualification = $doctor?->qualification;
            $tooltip = $qualification ? "{$name} ({$qualification})" : $name;

            return [
                'id' => $doctor->id,
                'slug' => $doctor->slug ?? null,
                'url' => $user?->profile_photo_url ?? asset('images/user-avatar.png'),
                'tooltip' => $tooltip,
            ];
        })
        ->filter()
        ->values();

    $limit = 3;
    $displayed = $doctors->take($limit);
    $overflow = max(0, $doctors->count() - $limit);

    // Cycle through a handful of possible badge background colors for overflow (to look like screenshot)
    $overflowColors = [
        'bg-yellow-400 text-white',
        'bg-red-500 text-white',
        'bg-green-600 text-white',
        'bg-blue-500 text-white',
        'bg-blue-700 text-white',
    ];
    $overflowColor = $overflow > 0 ? $overflowColors[(int) ($record->id ?? 0) % count($overflowColors)] : '';

    // Build a safe Filament doctor view URL
    $makeDoctorUrl = function ($doctor) {
        $key = $doctor['slug'] ?: $doctor['id'];
        return url("/admin/resources/doctors/records/{$key}/view");
    };
@endphp

<div class="flex items-center">
    <div class="flex -space-x-4">
        @foreach ($displayed as $idx => $doctor)
            @php $href = $makeDoctorUrl($doctor); @endphp
            <a href="{{ $href }}" title="{{ $doctor['tooltip'] }}" class="relative group z-10"
                style="margin-left:{{ $idx === 0 ? '0' : '-12px' }};">
                <img src="{{ $doctor['url'] }}" alt="{{ $doctor['tooltip'] }}"
                    style="width: 32px; min-width: 32px; max-width: 32px; height: 32px; min-height: 32px; max-height: 32px;"
                    class="rounded-xl object-cover transition-transform transform group-hover:scale-110"
                    loading="lazy" />
                <span
                    class="pointer-events-none absolute -bottom-2 left-1/2 transform -translate-x-1/2 translate-y-full opacity-0 group-hover:opacity-100 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-30 transition-opacity">
                    {{ $doctor['tooltip'] }}
                </span>
            </a>
        @endforeach
        @if ($overflow > 0)
            <div class="inline-flex items-center justify-center w-8 h-8 rounded-xl border-2 border-white shadow-sm text-[11px] font-medium z-10 {{ $overflowColor }}"
                title="{{ $overflow }} more doctors" style="margin-left:-12px;">
                +{{ $overflow }}
            </div>
        @endif
    </div>
</div>
