<div class="h-full w-full bg-white dark:bg-gray-800 rounded-xl group overflow-hidden p-0">
    @php
        $documentName = $getRecord()->name;

        // Fetch all documents with the same name
        $allDocuments = \App\Models\ModuleDocument::where('name', $documentName)->get();

        $totalDocuments = $allDocuments->count();
        $totalFiles = $allDocuments->sum(fn($doc) => count($doc->files ?? []));

        // Get unique model types linked to this document name
        $modelTypes = $allDocuments->pluck('model_type')->unique();

        // Get creators
        $creatorIds = $allDocuments->whereNotNull('created_by')->pluck('created_by')->unique();
        $creators = \App\Models\User::whereIn('id', $creatorIds)->limit(3)->get();
        $moreCreators = $creatorIds->count() - $creators->count();

        // Dynamic name formatting: replace underscores with spaces and capitalize
        $displayName = ucwords(str_replace(['_', '-'], ' ', $documentName));

        // Dynamic icon and color based on keywords in the name
        $iconClass = 'heroicon-m-document-text';
        $colorClass = 'primary';
        $lowerName = strtolower($documentName);

        if (str_contains($lowerName, 'prescription_pdf')) {
            $iconClass = 'heroicon-m-clipboard-document-list';
            $colorClass = 'success';
        } elseif (str_contains($lowerName, 'signature')) {
            $iconClass = 'heroicon-m-pencil-square';
            $colorClass = 'info';
        } elseif (str_contains($lowerName, 'stamp')) {
            $iconClass = 'heroicon-m-shield-check';
            $colorClass = 'warning';
        } elseif (str_contains($lowerName, 'report')) {
            $iconClass = 'heroicon-m-document-chart-bar';
            $colorClass = 'danger';
        } elseif (str_contains($lowerName, 'image') || str_contains($lowerName, 'photo')) {
            $iconClass = 'heroicon-m-photo';
            $colorClass = 'purple';
        }

        // Check if any linked model is a MedicalReport for the redirect button
        $hasMedicalReports = $modelTypes->contains('App\\Models\\MedicalReport');
        // Count specific links if they exist
        $linkedAppointmentCount = $allDocuments
            ->where('model_type', 'App\\Models\\Appointment')
            ->pluck('model_id')
            ->unique()
            ->count();
        $linkedMedicalReportCount = $allDocuments
            ->where('model_type', 'App\\Models\\MedicalReport')
            ->pluck('model_id')
            ->unique()
            ->count();
    @endphp

    {{-- Header --}}
    <div
        class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-{{ $colorClass }}-50 to-{{ $colorClass }}-100/50 dark:from-{{ $colorClass }}-900/20 dark:to-{{ $colorClass }}-800/10">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <div
                    class="w-10 h-10 rounded-lg bg-grey dark:bg-{{ $colorClass }} flex items-center justify-center shadow-lg">
                    <x-dynamic-component :component="$iconClass" class="w-6 h-6 text-black" />
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-white text-base leading-tight">
                    {{ $displayName }}
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mt-1">
                    {{ $modelTypes->map(fn($t) => class_basename($t))->join(', ') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div
        class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-700 border-b border-gray-100 dark:border-gray-700">
        <div class="p-3 text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Entries</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalDocuments }}</p>
        </div>
        <div class="p-3 text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Files</p>
            <p class="text-2xl font-bold text-{{ $colorClass }}-600">{{ $totalFiles }}</p>
        </div>
    </div>

    @if ($linkedAppointmentCount > 0 || $linkedMedicalReportCount > 0 || $modelTypes->count() > 1)
        {{-- Body Context --}}
        <div class="p-4 space-y-4 flex-1">
            @if ($linkedAppointmentCount > 0)
                <div
                    class="flex items-center justify-between text-sm p-2.5 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <x-heroicon-m-calendar-days class="w-4 h-4" />
                        <span class="font-medium">Appointments</span>
                    </div>
                    <span class="font-bold text-green-900 dark:text-green-300">{{ $linkedAppointmentCount }}</span>
                </div>
            @endif

            @if ($linkedMedicalReportCount > 0)
                <div
                    class="flex items-center justify-between text-sm p-2.5 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                        <x-heroicon-m-document-chart-bar class="w-4 h-4" />
                        <span class="font-medium">Medical Reports</span>
                    </div>
                    <span class="font-bold text-red-900 dark:text-red-300">{{ $linkedMedicalReportCount }}</span>
                </div>
            @endif

            {{-- Multiple Model Types Pill Display --}}
            @if ($modelTypes->count() > 1)
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($modelTypes as $type)
                        <span
                            class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-[10px] font-bold uppercase tracking-tight">
                            {{ class_basename($type) }}
                        </span>
                    @endforeach
                </div>
            @endif

        </div>
    @endif
    {{-- Footer Actions --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
        @if ($hasMedicalReports)
            <a href="{{ url('/admin/medical-reports') }}"
                class="w-full flex items-center justify-center gap-2 py-2 px-4 bg-red-400 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded-lg text-xs font-bold font-heading transition shadow-sm">
                <x-heroicon-m-arrow-right class="w-4 h-4" />
                Medical Reports Screen
            </a>
        @else
            <div
                class="w-full py-2 px-4 bg-{{ $colorClass }}-50 text-{{ $colorClass }}-700 dark:bg-{{ $colorClass }}-900/40 dark:text-{{ $colorClass }}-300 rounded-lg text-xs font-bold text-center border border-{{ $colorClass }}-100 dark:border-{{ $colorClass }}-800/50">
                View Details
            </div>
        @endif
    </div>
</div>
