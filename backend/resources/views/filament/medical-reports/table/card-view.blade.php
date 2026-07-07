<div class="h-full w-full bg-white dark:bg-gray-800 rounded-xl group overflow-hidden p-0">
    @php
    $patient = $getRecord()->patient;
    $patientId = $getRecord()->patient_id;

    // Fetch aggregates
    $allReports = $patientId ? \App\Models\MedicalReport::where('patient_id', $patientId)->get() : collect();
    $totalFiles = $allReports->count();
    $sharedCount = $allReports->where('is_shared', true)->whereNotNull('appointment_id')->count();
    $unlinkedCount = $allReports->whereNull('appointment_id')->count();
    $linkedAppointmentCount = $allReports
    ->whereNotNull('appointment_id')
    ->pluck('appointment_id')
    ->unique()
    ->count();

    // Get unique doctors linked to these reports
    $doctorIds = $allReports->whereNotNull('doctor_id')->pluck('doctor_id')->unique();
    $doctors = $doctorIds->isNotEmpty()
    ? \App\Models\Doctor::whereIn('id', $doctorIds)->with('user')->limit(3)->get()
    : collect();
    $moreDoctors = max(0, $doctorIds->count() - $doctors->count());
    @endphp

    {{-- Header --}}
    <div
        class="p-1 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="relative">
                @php
                $avatar = $patient?->user?->avatar;
                $hasAvatar = !empty($avatar);
                $avatarUrl = $hasAvatar ? storage_url($avatar) : null;
                $fullName = trim(($patient?->first_name ?? 'Unknown') . ' ' . ($patient?->last_name ?? ''));
                $fallbackAvatar =
                'https://ui-avatars.com/api/?name=' .
                urlencode($fullName) .
                '&color=7F9CF5&background=EBF4FF';
                @endphp
                <img src="{{ $avatarUrl ?? $fallbackAvatar }}"
                    class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-700 shadow-sm object-cover"
                    alt="{{ $patient?->first_name ?? 'Unknown' }}" onerror="this.onerror=null;this.src='{{ $fallbackAvatar }}';">

            </div>
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base leading-tight">
                    {{ $patient?->first_name ?? 'Unknown' }} {{ $patient?->last_name ?? '' }}
                </h3>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    ID: <span class="font-mono">{{ $patient ? substr($patient->id, 0, 8) : 'N/A' }}...</span>
                </p>
            </div>
        </div>

    </div>

    {{-- Stats Grid --}}
    <div
        class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-700 border-b border-gray-100 dark:border-gray-700 pt-2">
        <div class="p-1 text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Reports</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $totalFiles }}</p>
        </div>
        <div class="p-1 text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Shared</p>
            <p class="text-lg font-bold text-primary-600">{{ $sharedCount }}</p>
        </div>
    </div>

    {{-- Body Context --}}
    <div class="p-4 space-y-4 flex-1">

        {{-- Appointments Info --}}
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <x-heroicon-m-calendar-days class="w-4 h-4 text-primary-500" />
                <span>Linked Appointments</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white">{{ $linkedAppointmentCount }}</span>
        </div>

        {{-- Doctors Info --}}
        @if ($doctors->count() > 0)
        <div class="space-y-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Shared With Doctors</p>
            <div class="flex items-center -space-x-2 overflow-hidden py-1">
                @foreach ($doctors as $doctor)
                <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover"
                    src="{{ storage_url($doctor->avatar) }}" alt="{{ $doctor->user?->name }}"
                    title="{{ $doctor->user?->name }}">
                @endforeach
                @if ($moreDoctors > 0)
                <div
                    class="h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-500">
                    +{{ $moreDoctors }}
                </div>
                @endif
            </div>
            <div class="text-xs text-gray-500">
                {{ $doctors->pluck('user.name')->join(', ') }} {{ $moreDoctors > 0 ? 'and others' : '' }}
            </div>
        </div>
        @else
        <div class="py-2 text-xs text-gray-400 italic flex items-center gap-1">
            <x-heroicon-o-users class="w-4 h-4" />
            No doctors linked yet
        </div>
        @endif

    </div>

    {{-- Footer Actions --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-2">
            @if ($unlinkedCount > 0)
            <div
                class="flex-1 py-1.5 px-3 bg-red-50 text-red-600 rounded text-xs font-bold text-center border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                {{ $unlinkedCount }} Unlinked Files
            </div>
            @else
            <div
                class="flex-1 py-1.5 px-3 bg-green-50 text-green-600 rounded text-xs font-bold text-center border border-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                All Synced
            </div>
            @endif
        </div>
    </div>
</div>