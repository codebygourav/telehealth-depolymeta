<div class="h-full w-full bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 hover:border-primary-500/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group">
    @php
        $record = $getRecord();
        $documentName = $record->name;
        $modelType = $record->model_type;
        $modelId = $record->model_id;

        // Fetch all documents with the same name, model type, and id
        $allDocuments = \App\Models\ModuleDocument::where('name', $documentName)
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->get();

        $totalFiles = $allDocuments->sum(fn($doc) => count($doc->files ?? []));

        // Format name
        $displayName = ucwords(str_replace(['_', '-'], ' ', $documentName));

        // Resolve patient details
        $linkedModel = $record->model;
        $patientName = '';
        $formattedDate = '';
        if ($linkedModel) {
            $modelBase = class_basename($modelType);
            if ($modelBase === 'Appointment') {
                if ($linkedModel->patient) {
                    $patientName = trim(($linkedModel->patient->first_name ?? '') . ' ' . ($linkedModel->patient->last_name ?? ''));
                }
                if ($linkedModel->appointment_date) {
                    $formattedDate = $linkedModel->appointment_date->format('M d, Y');
                }
            }
        }
    @endphp

    <div class="p-5 flex-1 flex flex-col gap-4 asdgfd">
        {{-- Top Section: Title & Icon --}}
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-400 border border-primary-100/50 dark:border-primary-900/30">
                    {{ class_basename($modelType) }}
                </span>
                <h3 class="text-base font-bold text-[var(--app-primary-hex)] dark:text-white leading-snug">
                    {{ $displayName }}
                </h3>
            </div>
            
            <div class="w-10 h-10 rounded-lg bg-gray-50 dark:bg-gray-700/50 flex items-center justify-center border border-gray-100 dark:border-gray-600 shrink-0">
                <x-heroicon-m-clipboard-document-list class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
        </div>

        {{-- Meta list --}}
        <div class="space-y-2 text-xs py-1 border-t border-b border-gray-100 dark:border-gray-700">
            @if ($patientName)
                <div class="flex justify-between items-center mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Patient</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $patientName }}</span>
                </div>
            @endif

            @if ($formattedDate)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">Date</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $formattedDate }}</span>
                </div>
            @endif

            <div class="flex justify-between items-center mb-1">
                <span class="text-gray-500 dark:text-gray-400">Reference ID</span>
                <span class="font-mono text-[11px] text-gray-600 dark:text-gray-400">#{{ substr($modelId, 0, 8) }}</span>
            </div>
        </div>

        {{-- Stats and Files indicator --}}
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
            <span class="flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                {{ $allDocuments->count() }} {{ \Illuminate\Support\Str::plural('Entry', $allDocuments->count()) }}
            </span>
            <span class="font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/20 px-2 py-0.5 rounded">
                {{ $totalFiles }} {{ \Illuminate\Support\Str::plural('File', $totalFiles) }}
            </span>
        </div>
    </div>

    {{-- Action Footer --}}
    <div class="px-5 py-3.5 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between group-hover:bg-primary-50/10 transition-colors duration-200">
        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[var(--app-primary-hex)] dark:group-hover:text-[var(--app-primary-hex)] transition-colors">
            View Details
        </span>
        <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-400 group-hover:text-[var(--app-primary-hex)] group-hover:translate-x-0.5 transition-all" />
    </div>
</div>
