@php
    use App\Filament\Resources\MedicalReports\MedicalReportResource;
    use App\Filament\Resources\Appointments\AppointmentResource;

    $record = $getState();
    $documentName = $record->name;
    $modelType = $record->model_type;
    $modelId = $record->model_id;

    // Fetch all documents with the same name, model_type, and model_id
    $allDocuments = \App\Models\ModuleDocument::where('name', $documentName)
        ->where('model_type', $modelType)
        ->where('model_id', $modelId)
        ->with(['model', 'creator'])
        ->orderBy('created_at', 'desc')
        ->get();

    $totalDocuments = $allDocuments->count();
    $totalFiles = $allDocuments->sum(fn($doc) => count($doc->files ?? []));

    // Dynamic Name Formatting
    $displayName = ucwords(str_replace(['_', '-'], ' ', $documentName));

    // Get unique model types
    $modelTypes = $allDocuments->pluck('model_type')->unique();
    $modelTypeCounts = $allDocuments->groupBy('model_type')->map->count();

    // Get unique creators
    $creators = $allDocuments->pluck('creator')->filter()->unique('id');
@endphp

<div class="space-y-6">

    {{-- Overview Section --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <x-heroicon-m-document-text class="w-6 h-6 text-primary-500" />
                <span>{{ $displayName }} Group Analysis</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Logic Identifier</p>
                <p class="text-sm font-mono font-bold text-gray-900 dark:text-white mt-1">{{ $documentName }}</p>
            </div>

            <div
                class="p-4 bg-primary-50 dark:bg-primary-900/10 rounded-xl border border-primary-100 dark:border-primary-800/50 shadow-sm">
                <p class="text-[10px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-widest">Total
                    Entries</p>
                <p class="text-2xl font-black text-primary-700 dark:text-primary-300 mt-1">{{ $totalDocuments }}</p>
            </div>

            <div
                class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-800/50 shadow-sm">
                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Stored
                    Files</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ $totalFiles }}</p>
            </div>

            <div
                class="p-4 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-100 dark:border-purple-800/50 shadow-sm">
                <p class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest">Models
                    Linked</p>
                <p class="text-2xl font-black text-purple-700 dark:text-purple-300 mt-1">{{ $modelTypes->count() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-100 dark:border-gray-800">
            {{-- Distribution --}}
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Model
                    Distribution</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($modelTypeCounts as $type => $count)
                        <div
                            class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-100 dark:border-gray-800">
                            <span
                                class="text-xs font-bold text-gray-900 dark:text-white">{{ class_basename($type) }}</span>
                            <span
                                class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-black text-gray-600 dark:text-gray-400">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Team --}}
            @if ($creators->count() > 0)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                        Contribution History</h4>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($creators as $creator)
                            <div
                                class="flex items-center gap-2 px-1 py-1 bg-white dark:bg-gray-800 rounded-full border border-gray-100 dark:border-gray-700 shadow-sm">
                                <img src="{{ storage_url($creator->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($creator->name)) }}"
                                    class="w-7 h-7 rounded-full object-cover" alt="{{ $creator->name }}"
                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($creator->name) }}&color=7F9CF5&background=EBF4FF'">
                                <span
                                    class="text-xs pr-1 font-semibold text-gray-700 dark:text-gray-300">{{ $creator->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Detailed List --}}
    <div class="space-y-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white px-1">Detailed Records</h3>

        @foreach ($allDocuments as $doc)
            @php
                $linkedModel = $doc->model;
                $files = $doc->files ?? [];
                $modelName = class_basename($doc->model_type);
                $lowerModel = strtolower($modelName);

                // Dynamic Display Info Resolution
                $displayTitle = 'Entry Reference: ' . $doc->model_id;
                $displaySubtitle = $modelName . ' Model';
                $linkUrl = null;

                if ($linkedModel) {
                    if (isset($linkedModel->name)) {
                        $displayTitle = $linkedModel->name;
                    } elseif (isset($linkedModel->first_name)) {
                        $displayTitle = trim(($linkedModel->first_name ?? '') . ' ' . ($linkedModel->last_name ?? ''));
                    } elseif ($modelName === 'Appointment') {
                        $displayTitle = 'Appointment ID: ' . substr($doc->model_id, 0, 8);
                        if ($linkedModel->appointment_date) {
                            $displaySubtitle = 'Dated ' . $linkedModel->appointment_date->format('M d, Y');
                        }
                        $linkUrl = AppointmentResource::getUrl('view', ['record' => $linkedModel]);
                    }

                    // Special Link for Medical Reports
                    if ($modelName === 'MedicalReport') {
                        $linkUrl = MedicalReportResource::getUrl('view', ['record' => $linkedModel]);
                    }
                }
            @endphp

            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition">
                <div
                    class="p-4 border-b border-gray-50 dark:border-gray-900/50 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
                    <div class="flex items-center gap-3">
                        <div
                            class="px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded text-[10px] font-black uppercase tracking-widest">
                            {{ $modelName }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $displayTitle }}</p>
                            <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ $displaySubtitle }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($linkUrl)
                            <x-filament::button tag="a" href="{{ $linkUrl }}" size="xs"
                                color="primary" outlined icon="heroicon-m-arrow-right" icon-position="after">
                                Open Source
                            </x-filament::button>
                        @endif

                        {{-- Delete Record Button --}}
                        <x-filament::icon-button icon="heroicon-m-trash" color="danger"
                            wire:click="deleteFile('{{ $doc->id }}')"
                            wire:confirm="Are you sure you want to delete this entire document record and all its files?"
                            tooltip="Delete Entire Record" size="sm" />

                        <div class="text-right border-l border-gray-200 dark:border-gray-700 pl-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Uploaded</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-white">
                                {{ $doc->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                @if (count($files) > 0)
                    <div class="p-4 bg-white dark:bg-gray-800">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($files as $index => $file)
                                @php
                                    $url = storage_url($file);
                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf = $ext === 'pdf';
                                @endphp

                                <div
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800 group hover:border-primary-300 dark:hover:border-primary-800 transition shadow-sm">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-inner flex items-center justify-center">
                                        @if ($isImage)
                                            <img src="{{ $url }}" class="w-full h-full object-cover">
                                        @elseif($isPdf)
                                            <x-heroicon-o-document-text class="w-5 h-5 text-red-500" />
                                        @else
                                            <x-heroicon-o-paper-clip class="w-5 h-5 text-primary-500" />
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate"
                                            title="{{ basename($file) }}">
                                            {{ basename($file) }}
                                        </p>
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                                            {{ $ext }} file</p>
                                    </div>

                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                        <x-filament::icon-button icon="heroicon-m-eye" color="gray" tag="a"
                                            target="_blank" href="{{ $url }}" size="xs" />
                                        <x-filament::icon-button icon="heroicon-m-arrow-down-tray" color="gray"
                                            tag="a" href="{{ $url }}" download size="xs" />
                                        <x-filament::icon-button icon="heroicon-m-trash" color="danger"
                                            wire:click="deleteFile('{{ $doc->id }}', {{ $index }})"
                                            wire:confirm="Are you sure you want to delete this file?"
                                            tooltip="Delete File" size="xs" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-4 text-center text-xs text-gray-400 italic bg-white dark:bg-gray-800">
                        No files available for this record.
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
