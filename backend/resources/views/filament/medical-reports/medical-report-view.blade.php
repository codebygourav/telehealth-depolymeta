<div class="space-y-6">
    @php
        $patient = $getState()->patient;
        // Fetch all reports for this patient, ordered by date
        $reports = $patient
            ? $patient
                ->medicalReports()
                ->with(['doctor.user', 'appointment', 'creator'])
                ->latest()
                ->get()
            : collect([$getState()]);
    @endphp

    {{-- Patient Header Section --}}
    @if ($patient)
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div
                class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <x-heroicon-o-user-circle class="w-6 h-6 text-primary-500" />
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Patient Profile</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                <div class="flex items-center gap-4">
                    <img src="{{ storage_url($patient->avatar) }}"
                        class="w-16 h-16 rounded-full border-4 border-primary-50 dark:border-primary-900 shadow-sm object-cover"
                        alt="Patient Avatar">
                    <div>
                        <p class="font-bold text-xl text-gray-900 dark:text-white">{{ $patient->first_name }}
                            {{ $patient->last_name }}
                        </p>
                        <p class="text-xs text-gray-500 font-mono tracking-wide">ID: {{ $patient->id }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Demographics</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ ucfirst($patient->gender ?? 'N/A') }} • {{ $patient->age ?? 'N/A' }} Years
                    </p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Contact</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-1">
                        <x-heroicon-o-envelope class="w-5 h-5 text-gray-400" /> {{ $patient->email ?? 'N/A' }}
                    </p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-1">
                        <x-heroicon-o-phone class="w-5 h-5 text-gray-400" /> {{ $patient->mobile_no ?? 'N/A' }}
                    </p>
                </div>

                <div class="flex justify-end">
                    <div class="text-right">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Total Reports</p>
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm font-bold">
                            {{ $reports->count() }} Files
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reports List --}}
    <div class="space-y-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 px-1">
            <x-heroicon-o-document-duplicate class="w-5 h-5 text-gray-400" />
            Medical Files & Reports
        </h3>

        @foreach ($reports as $report)
            @php
                $fileUrl = $report->file_url;
                $fileName = $report->file_name ?? 'Document File';
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                $isPdf = $extension === 'pdf';
                $isOffice = in_array($extension, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx']);

                $viewUrl = $fileUrl;
                if ($isOffice && $fileUrl) {
                    $viewUrl = 'https://docs.google.com/viewer?url=' . urlencode($fileUrl) . '&embedded=true';
                }

                $isCurrent = $getState()->id === $report->id;
            @endphp

            <div
                class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $isCurrent ? 'border-primary-500 ring-1 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }} hover:shadow-md transition-all duration-300 overflow-hidden">

                {{-- Card Header: Status & Meta --}}
                <div
                    class="bg-gray-50 dark:bg-gray-900/30 px-6 py-3 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div
                            class="p-2 {{ $isCurrent ? 'bg-primary-100 text-primary-600' : 'bg-primary-100 text-gray-500' }} rounded-lg">
                            @if ($report->type === 'lab_report')
                                <x-heroicon-o-beaker class="w-5 h-5" />
                            @elseif($report->type === 'radiology')
                                <x-heroicon-o-photo class="w-5 h-5" />
                            @elseif($report->type === 'prescription')
                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                            @else
                                <x-heroicon-o-document-text class="w-5 h-5" />
                            @endif
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white leading-tight">{{ $report->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $report->type_label }} •
                                {{ $report->created_at->format('M d, Y') }}
                            </p>
                            @php
                                $uploaderName = 'Unknown';
                                if ($report->uploader_type === 'Doctor' && $report->uploader_id) {
                                    $doctor = \App\Models\Doctor::find($report->uploader_id);
                                    if ($doctor) {
                                        $firstName = $doctor->first_name ?? '';
                                        $lastName = $doctor->last_name ?? '';
                                        $uploaderName = trim($firstName . ' ' . $lastName);
                                    }
                                } elseif (
                                    ($report->uploader_type === 'Patient' && $report->uploader_id) ||
                                    $report->patient_id
                                ) {
                                    $patient_id = $report->patient_id ?? $report->uploader_id;
                                    $patient = \App\Models\Patient::find($patient_id);
                                    if ($patient) {
                                        $firstName = $patient->first_name ?? '';
                                        $lastName = $patient->last_name ?? '';
                                        $uploaderName = trim($firstName . ' ' . $lastName);
                                    }
                                }
                            @endphp
                            <p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
                                <x-heroicon-s-user class="w-3 h-3 text-primary-500" />
                                Uploaded by: <span
                                    class="text-gray-600 dark:text-gray-400 font-bold uppercase">{{ $uploaderName }}</span>
                            </p>


                        </div>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium {{ match ($report->status?->value) {
                            'active' => 'bg-green-100 text-green-700',
                            'shared' => 'bg-blue-100 text-blue-700',
                            'uploaded' => 'bg-yellow-100 text-yellow-700',
                            default => 'bg-gray-100 text-gray-700',
                        } }}">
                        {{ $report->status?->label() ?? 'Unknown' }}
                    </span>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">

                    {{-- 1. File Preview Section --}}
                    <div
                        class="lg:col-span-1 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-100 dark:border-gray-800">
                        @if ($isImage && $fileUrl)
                            <img src="{{ $fileUrl }}"
                                class="h-24 object-cover rounded shadow-sm mb-3 cursor-pointer hover:opacity-90 transition"
                                onclick="togglePreview('{{ $viewUrl }}', 'image')" alt="Preview">
                        @else
                            <div
                                class="w-16 h-16 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center mb-3">
                                @if ($isPdf)
                                    <x-heroicon-o-document-text class="w-8 h-8 text-red-500" />
                                @elseif($isOffice)
                                    <x-heroicon-o-presentation-chart-bar class="w-8 h-8 text-blue-500" />
                                @else
                                    <x-heroicon-o-paper-clip class="w-8 h-8 text-primary-500" />
                                @endif
                            </div>
                        @endif
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-2">
                            {{ $extension }} FILE
                        </p>

                        <div class="flex gap-2 w-full">
                            @if ($fileUrl)
                                <button
                                    onclick="togglePreview('{{ $viewUrl }}', '{{ $isOffice ? 'office' : ($isPdf ? 'pdf' : 'image') }}')"
                                    class="flex-1 py-3  bg-blue-50 hover:bg-blue-100 text-gray-900 dark:bg-gray-900/20 dark:text-white rounded-xl text-base font-semibold transition flex flex-col items-center justify-center gap-0.5 shadow-none border border-transparent hover:shadow focus:outline-none focus:ring-2 focus:ring-primary-300">
                                    <x-heroicon-o-eye class="w-5 h-5 text-blue-700" />
                                </button>
                                <a href="{{ $fileUrl }}" download="{{ $fileName }}"
                                    class="flex-1 py-3 bg-gray-50 hover:bg-gray-100 text-gray-900 dark:bg-gray-900/20 dark:text-white rounded-xl text-base font-semibold transition flex flex-col items-center justify-center gap-0.5 shadow-none border border-transparent hover:shadow focus:outline-none focus:ring-2 focus:ring-primary-300">
                                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-400" />
                                </a>
                                <button wire:click="deleteReport('{{ $report->id }}')"
                                    class="flex-1 py-3 bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400 rounded-xl text-base font-semibold transition flex flex-col items-center justify-center gap-0.5 shadow-none border border-transparent hover:shadow focus:outline-none focus:ring-2 focus:ring-red-300">
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            @else
                                <span class="text-xs text-gray-400 italic">No file attached</span>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Details: Doctor & Appointment --}}
                    <div class="lg:col-span-2 space-y-4">
                        {{-- Doctor Info --}}
                        @if ($report->doctor)
                            <div class="flex items-start gap-3">
                                <img src="{{ storage_url($report->doctor->avatar) }}"
                                    class="w-10 h-10 rounded-full border border-gray-200 shadow-sm object-cover"
                                    alt="Dr Avatar">
                                <div>
                                    <p class="text-xs text-gray-500 font-bold uppercase">Doctor In-Charge</p>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm">Dr.
                                        {{ $report->doctor->user?->name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $report->doctor->departments?->pluck('name')->implode(', ') }}
                                    </p>
                                    <a target="_blank"
                                        href="{{ \App\Filament\Resources\Doctors\DoctorResource::getUrl('view', ['record' => $report->doctor->slug ?? $report->doctor_id]) }}"
                                        class="text-xs text-primary-600 underline hover:underline flex items-center gap-1 mt-1">
                                        View Profile <x-heroicon-o-arrow-right class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- Appointment Info --}}
                        @if ($report->appointment)
                            <div class="flex items-start gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                                <div
                                    class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                    <x-heroicon-o-calendar class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-bold uppercase">Related Appointment</p>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm">
                                        {{ $report->appointment->appointment_date ? \Carbon\Carbon::parse($report->appointment->appointment_date)->format('d M, Y') : 'N/A' }}
                                    </p>
                                    <a target="_blank"
                                        href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $report->appointment->slug ?? $report->appointment_id]) }}"
                                        class="text-xs text-primary-600 underline hover:underline flex items-center gap-1 mt-1">
                                        View Appointment <x-heroicon-o-arrow-right class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        @endif


                        @if (!$report->doctor && !$report->appointment)
                            <p class="text-sm text-gray-400 italic py-4">No associated doctor or appointment.</p>
                        @endif
                    </div>

                    {{-- 3. Description / Results Preview --}}
                    <div class="lg:col-span-1 border-l border-gray-100 dark:border-gray-800 pl-0 lg:pl-6 space-y-3">
                        @if ($report->description)
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Notes</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-3">
                                    {{ $report->description }}
                                </p>
                            </div>
                        @endif

                        @if ($report->results && count($report->results))
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Results</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($report->results, 0, 3) as $key => $val)
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-[10px] border border-gray-200 dark:border-gray-700 text-gray-600">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </span>
                                    @endforeach
                                    @if (count($report->results) > 3)
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 text-[10px] text-gray-500">+{{ count($report->results) - 3 }}
                                            more</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Embedded Preview Modal/Container --}}
    <div id="file-preview-container"
        class="hidden transition-all duration-500 ease-in-out fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden w-full max-w-5xl h-[80vh] flex flex-col">
            <div
                class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-lg">
                    <x-heroicon-o-eye class="w-6 h-6 text-primary-500" />
                    File Preview
                </h3>
                <button onclick="closePreview()"
                    class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition text-gray-500">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>
            <div class="relative flex-1 bg-gray-100 dark:bg-black overflow-hidden flex items-center justify-center">
                <iframe id="preview-frame" class="w-full h-full border-none hidden" src=""></iframe>
                <div id="preview-image-container" class="hidden w-full h-full flex items-center justify-center p-4">
                    <img id="preview-image" src="" class="max-w-full max-h-full object-contain shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePreview(url, type) {
            const container = document.getElementById('file-preview-container');
            const frame = document.getElementById('preview-frame');
            const imgContainer = document.getElementById('preview-image-container');
            const img = document.getElementById('preview-image');

            if (type === 'image') {
                frame.classList.add('hidden');
                frame.src = '';
                imgContainer.classList.remove('hidden');
                img.src = url;
            } else {
                imgContainer.classList.add('hidden');
                img.src = '';
                frame.classList.remove('hidden');
                frame.src = url;
            }

            container.classList.remove('hidden');
        }

        function closePreview() {
            const container = document.getElementById('file-preview-container');
            const frame = document.getElementById('preview-frame');
            const img = document.getElementById('preview-image');

            container.classList.add('hidden');
            frame.src = '';
            img.src = '';
        }

        // Close on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closePreview();
            }
        });

        // Close on click outside (optional, simplified)
        document.getElementById('file-preview-container').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    </script>
</div>
