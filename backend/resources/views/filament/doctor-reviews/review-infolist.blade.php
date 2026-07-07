<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $review = $entry->getRecord();
        $isFake = $review->review_type === 'fake';

        // Fetch patient directly using ID to ensure all fields are loaded
        $patient = null;
        if (!$isFake && $review->patient_id) {
            $patient = $review->patient;
        }

        $fakerPatient = $isFake ? $review->fakerPatient : null;
        $doctor = $review->doctor;

        // Get patient name
        $patientName = null;
        if ($isFake && $fakerPatient) {
            $patientName = $fakerPatient->name;
        } else {
            $patientName = $review->patient_name;
        }

        // Get age
        $patientAge = null;
        if ($isFake && $fakerPatient) {
            $patientAge = $fakerPatient->age;
        } elseif ($patient && $patient->date_of_birth) {
            $patientAge = \Carbon\Carbon::parse($patient->date_of_birth)->age;
        }

        // Get address
        $patientAddress = null;
        if ($isFake && $fakerPatient) {
            $patientAddress = $fakerPatient->address;
        } elseif ($patient) {
            $patientAddress = $patient->address;
        }
    @endphp

    <div class="space-y-6">
        {{-- Header Card with Review Title and Rating --}}
        <div
            class="bg-gradient-to-r {{ $isFake ? 'from-primary-50 to-pink-50 border-primary-200' : 'from-primary-50 to-primary-100 border-primary-200' }} rounded-xl p-6 border shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $review->title }}</h1>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold {{ $isFake ? 'bg-primary-100 text-primary-800' : 'bg-purple-100 text-purple-800' }}">
                            @if ($isFake)
                                <x-heroicon-o-sparkles class="w-4 h-4 mr-1" />
                                Fake Review
                            @else
                                <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                                Original Review
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-4 flex-wrap">
                        @if ($patientName)
                            <div class="flex items-center gap-2 text-gray-700">
                                <x-heroicon-o-user
                                    class="w-5 h-5 {{ $isFake ? 'text-primary-600' : 'text-primary' }}" />
                                <span class="font-medium">{{ $patientName }}</span>
                            </div>
                        @endif
                        @if ($doctor)
                            <div class="flex items-center gap-2 text-gray-700">
                                <x-heroicon-o-user-circle
                                    class="w-5 h-5 {{ $isFake ? 'text-primary-600' : 'text-primary' }}" />
                                <span class="font-medium">{{ $doctor->first_name }} {{ $doctor->last_name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1">
                        @php
                            $rating = $review->rating ?? 0;
                            $colorClass = match ($rating) {
                                5 => 'text-yellow-400',
                                4 => 'text-yellow-400',
                                3 => 'text-yellow-300',
                                2 => 'text-yellow-200',
                                1 => 'text-gray-300',
                                default => 'text-gray-300',
                            };
                        @endphp
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $rating)
                                <x-heroicon-s-star class="w-6 h-6 {{ $colorClass }}" />
                            @else
                                <x-heroicon-o-star class="w-6 h-6 text-gray-300" />
                            @endif
                        @endfor
                        <span class="ml-2 text-sm font-semibold text-gray-700">
                            ({{ $rating }}/5)
                        </span>
                    </div>
                    <div class="flex gap-2">
                        @if ($review->is_active)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold bg-green-100 text-green-800">
                                <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                                Active
                            </span>
                        @endif
                        @if ($review->is_featured)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold bg-yellow-100 text-yellow-800">
                                <x-heroicon-o-star class="w-4 h-4 mr-1" />
                                Featured
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Patient/Faker Information --}}
            <div class="lg:col-span-1">
                <div
                    class="bg-white rounded-xl border {{ $isFake ? 'border-primary-200' : 'border-gray-200' }} shadow-sm overflow-hidden">
                    <div
                        class="{{ $isFake ? 'bg-gradient-to-r from-primary-50 to-pink-50 border-primary-200' : 'bg-gray-50 border-gray-200' }} px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <x-heroicon-o-user-circle
                                class="w-5 h-5 {{ $isFake ? 'text-primary-600' : 'text-primary' }}" />
                            {{ $isFake ? 'Fake Patient Information' : 'Patient Information' }}
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Patient/Faker Image --}}
                        <div class="flex justify-center">
                            @php
                                $avatarUrl = storage_url($review->patient_image) ?? asset('images/default-avatar.png');
                            @endphp
                            <div class="relative">
                                <img src="{{ $avatarUrl }}" alt="{{ $isFake ? 'Fake Patient' : 'Patient' }}"
                                    class="w-32 h-32 rounded-xl border-4 {{ $isFake ? 'border-primary-100' : 'border-gray-100' }} object-cover shadow-md">

                            </div>
                        </div>

                        {{-- Patient/Faker Details --}}
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                    {{ $isFake ? 'Fake Patient Name' : 'Patient Name' }}
                                </p>
                                <p class="text-lg font-bold text-gray-900">{{ $patientName ?? '—' }}</p>
                            </div>

                            @if ($patientName || $patientAge || $patientAddress)
                                <div class="space-y-3">
                                    @if ($patientName)
                                        <div
                                            class="flex items-center gap-3 p-0 mb-3 {{ $isFake ? 'bg-primary-50' : 'bg-gray-50' }} rounded-xl">
                                            <div
                                                class="flex-shrink-0 w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                                                <x-heroicon-o-link class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">
                                                    {{ $isFake ? 'Fake Patient Name' : 'Patient Name' }}</p>
                                                <p class="text-sm font-semibold text-gray-900">
                                                    {{ $patientName }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($patientAge)
                                        <div
                                            class="flex items-center gap-3 p-0 mb-3 {{ $isFake ? 'bg-primary-50' : 'bg-gray-50' }} rounded-xl">
                                            <div
                                                class="flex-shrink-0 w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                                                <x-heroicon-o-calendar class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">Age</p>
                                                <p class="text-sm font-semibold text-gray-900">
                                                    {{ $patientAge }} years
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($patientAddress)
                                        <div
                                            class="flex items-center gap-3 p-0 {{ $isFake ? 'bg-primary-50' : 'bg-gray-50' }} rounded-xl">
                                            <div
                                                class="flex-shrink-0 w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                                                <x-heroicon-o-map-pin class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">Address</p>
                                                <p class="text-sm font-semibold text-gray-900">{{ $patientAddress }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Review Content --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <x-heroicon-o-document-text class="w-5 h-5 text-primary" />
                            Review Details
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Review Content --}}
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Review Content</p>
                            <div class="prose max-w-none">
                                <p class="text-gray-700 leading-relaxed">
                                    {{ strip_tags($review->content) }}</p>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-200"></div>

                        {{-- Additional Info --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if ($doctor)
                                <div class="p-4 bg-gray-50 rounded-xl">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Doctor</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $doctor->first_name }} {{ $doctor->last_name }}
                                    </p>
                                </div>
                            @endif

                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Rating</p>
                                <div class="flex items-center gap-1">
                                    @php
                                        $rating = $review->rating ?? 0;
                                        $colorClass = match ($rating) {
                                            5 => 'text-yellow-400',
                                            4 => 'text-yellow-400',
                                            3 => 'text-yellow-300',
                                            2 => 'text-yellow-200',
                                            1 => 'text-gray-300',
                                            default => 'text-gray-300',
                                        };
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $rating)
                                            <x-heroicon-s-star class="w-5 h-5 {{ $colorClass }}" />
                                        @else
                                            <x-heroicon-o-star class="w-5 h-5 text-gray-300" />
                                        @endif
                                    @endfor
                                    <span class="ml-2 text-xs font-semibold text-gray-700">
                                        ({{ $rating }}/5)
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Appointment Info (Only for Original Reviews) --}}
                        @if (!$isFake && $review->appointment)
                            <div class="border-t border-gray-200 pt-6">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Linked
                                    Appointment Details</p>
                                <div class="bg-primary-50/50 rounded-xl border border-primary-100 p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary shrink-0">
                                                <x-heroicon-o-calendar-days class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">Date & Time</p>
                                                <p class="text-sm font-semibold text-gray-900">
                                                    {{ $review->appointment->appointment_date ? $review->appointment->appointment_date->format('D, M d, Y') : 'N/A' }}
                                                    at {{ $review->appointment->appointment_time ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary shrink-0">
                                                <x-heroicon-o-clock class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">Status</p>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                    {{ $review->appointment->status instanceof \App\Enums\AppointmentStatus ? $review->appointment->status->label() : ($review->appointment->status->value ?? (string) $review->appointment->status) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary shrink-0">
                                                <x-heroicon-o-video-camera class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500">Consultation Type</p>
                                                <p class="text-sm font-semibold text-gray-900 capitalize text-primary">
                                                    {{ $review->appointment->consultation_type ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</x-dynamic-component>
