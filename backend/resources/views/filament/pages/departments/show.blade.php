@props(['record'])

@php
    $department = $record;
    $departmentImage = $department->department_featured ?? null;
    $isTabLayout = $department->is_tab_layout ?? false;
    $doctorCount = $department->doctors_count ?? ($department->doctors?->count() ?? 0);
    $doctors = $department->doctors ?? [];
    $tabs = $department->tabs ?? [];

    // Parse fields
    $addInfo = [];
    if (!empty($department->additional_information)) {
        $addInfo = is_array($department->additional_information)
            ? $department->additional_information
            : json_decode($department->additional_information, true) ?? [];
    }
    $faqs = [];
    if (!empty($department->faqs)) {
        $faqs = is_array($department->faqs) ? $department->faqs : json_decode($department->faqs, true) ?? [];
    }
    $publications = [];
    if (!empty($department->publications)) {
        $publications = is_array($department->publications)
            ? $department->publications
            : json_decode($department->publications, true) ?? [];
    }
@endphp

<div class="w-full mx-auto" x-data="{ viewMode: '{{ $isTabLayout ? 'tab' : 'simple' }}' }">
    <div class="flex flex-col gap-6">
        {{-- Header Section with View Switcher --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                    {{-- View Switcher --}}
                    <div class="flex flex-col items-start lg:items-center lg:justify-center gap-3">
                        <div class="text-sm font-semibold text-gray-700">View Switcher</div>
                        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                            <button type="button" x-on:click="viewMode = 'simple'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                                :class="viewMode === 'simple' ? 'bg-grey text-gray-900 shadow-sm' :
                                    'text-gray-600 hover:text-gray-900'">
                                Simple View
                            </button>
                            <button type="button" x-on:click="viewMode = 'tab'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                                :class="viewMode === 'tab' ? 'bg-primary text-white shadow-sm' :
                                    'text-gray-600 hover:text-gray-900'">
                                Tab Layout
                            </button>
                        </div>
                    </div>

                    {{-- Feature Image --}}
                    @if ($departmentImage)
                        <div class="flex items-center justify-end">
                            <div class="rounded-lg overflow-hidden shadow-md w-full max-w-sm">
                                <img src="{{ Storage::url($departmentImage) }}" alt="{{ $department->name }}"
                                    class="w-full h-48 object-cover">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg p-5 border border-gray-200" x-data="{ expanded: false }">
            <div class="lg:col-span-1">
                <h1 class="text-3xl font-bold text-gray-900">{{ $department->name }}</h1>
                @if ($department->description)
                    @php
                        $descriptionLength = strlen($department->description);
                        $shortDescription = Str::limit($department->description, 150, '');
                        $needsReadMore = $descriptionLength > 150;
                    @endphp
                    <div class="text-gray-600 mt-1">
                        <p x-show="!expanded">{{ $shortDescription }}@if ($needsReadMore)
                                ...
                            @endif
                        </p>
                        <p x-show="expanded" x-cloak>{{ $department->description }}</p>
                        @if ($needsReadMore)
                            <button type="button" x-on:click="expanded = !expanded"
                                class="text-primary hover:text-primary/80 font-medium text-sm mt-2 inline-flex items-center gap-1">
                                <span x-text="expanded ? 'Read Less' : 'Read More'"></span>
                                <x-heroicon-o-chevron-down class="w-4 h-4 transition-transform duration-200"
                                    x-bind:class="{ 'rotate-180': expanded }" />
                            </button>
                        @endif
                    </div>
                @else
                    <p class="text-gray-500 mt-1 italic">No description available.</p>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-lg p-5 border border-gray-200" x-data="{ openDoctorDepartment: false }">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2" :class="{ 'mb-4': openDoctorDepartment }">
                    <h2 class="text-lg font-bold">Doctors in Department</h2>
                    <div class="rounded-lg px-4 py-2 bg-blue-50 text-blue-700 font-semibold">
                        Doctors: {{ $doctorCount }}
                    </div>
                </div>
                <button type="button" x-on:click="openDoctorDepartment = !openDoctorDepartment"
                    class="flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80 transition">
                    <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-200"
                        x-bind:class="{ 'rotate-180': openDoctorDepartment }" />
                </button>
            </div>
            @if ($doctorCount > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6" x-show="openDoctorDepartment"
                    x-transition>
                    @foreach ($doctors as $doctor)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col gap-1">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="text-base font-bold text-gray-900 truncate">
                                        {{ $doctor->user->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">
                                    <span class="font-semibold">Email:</span>
                                    {{ $doctor->user->email ?? 'N/A' }}
                                </div>
                                @if (!empty($doctor->user->phone))
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span class="font-semibold">Phone:</span>
                                        {{ $doctor->user->phone }}
                                    </div>
                                @endif
                                @if (!empty($doctor->bio))
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span class="font-semibold">About:</span>
                                        {{ \Illuminate\Support\Str::limit($doctor->bio, 50) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div
                    class="w-full bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 flex justify-center items-center">
                    No doctors found in this department.
                </div>
            @endif
        </div>

        {{-- Simple View Content --}}
        <div x-show="viewMode === 'simple'" x-transition>
            @if (count($publications) > 0 || count($faqs) > 0 || count($addInfo) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 space-y-4">
                        {{-- About Section --}}


                        {{-- Publications Section --}}
                        @if (count($publications))
                            <div x-data="{ openPublications: true }"
                                class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
                                <button type="button" x-on:click="openPublications = !openPublications"
                                    class="w-full flex items-center justify-between p-5 bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 transition-all duration-200 cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                            <x-heroicon-o-document-text class="w-6 h-6 text-primary" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Publications</h3>
                                    </div>
                                    <x-heroicon-o-chevron-up
                                        class="w-5 h-5 text-gray-500 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': !openPublications }" />
                                </button>
                                <div x-show="openPublications" x-collapse class="px-5 pb-5">
                                    <div class="pt-4 border-t border-gray-100">
                                        <ul class="space-y-3">
                                            @foreach ($publications as $pub)
                                                <li
                                                    class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <span class="text-primary mt-1 font-bold text-lg">•</span>
                                                    <div class="flex-1">
                                                        <div class="font-semibold text-gray-900 text-base">
                                                            {{ $pub['publication_name'] ?? '-' }}</div>
                                                        @if (!empty($pub['publication_description']))
                                                            <div class="text-gray-600 text-sm mt-1">
                                                                {{ $pub['publication_description'] }}</div>
                                                        @endif
                                                        @if (!empty($pub['publication_date']))
                                                            <div class="text-xs text-gray-500 mt-2 font-medium">
                                                                {{ \Carbon\Carbon::parse($pub['publication_date'])->format('Y') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- FAQ Section --}}
                        @if (count($faqs))
                            <div x-data="{ openFaqSection: true }"
                                class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
                                <button type="button" x-on:click="openFaqSection = !openFaqSection"
                                    class="w-full flex items-center justify-between p-5 bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 transition-all duration-200 cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                            <x-heroicon-o-question-mark-circle class="w-6 h-6 text-primary" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">FAQ</h3>
                                    </div>
                                    <x-heroicon-o-chevron-up
                                        class="w-5 h-5 text-gray-500 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': !openFaqSection }" />
                                </button>
                                <div x-show="openFaqSection" x-collapse class="px-5 pb-5">
                                    <div class="pt-4 border-t border-gray-100 space-y-4">
                                        @foreach ($faqs as $index => $faq)
                                            <div
                                                class="p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-primary-200 transition-colors">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center mt-0.5">
                                                        <span
                                                            class="text-primary text-xs font-bold">{{ $index + 1 }}</span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="font-semibold text-gray-900 text-base mb-2">
                                                            {{ $faq['question'] ?? 'FAQ Question' }}</div>
                                                        @if (!empty($faq['answer']))
                                                            <div class="text-gray-700 text-sm leading-relaxed">
                                                                {{ $faq['answer'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Additional Information Section --}}
                        @if (count($addInfo))
                            <div x-data="{ openAdditionalInfo: true }"
                                class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
                                <button type="button" x-on:click="openAdditionalInfo = !openAdditionalInfo"
                                    class="w-full flex items-center justify-between p-5 bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 transition-all duration-200 cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                            <x-heroicon-o-document-text class="w-6 h-6 text-primary" />
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Additional Information</h3>
                                    </div>
                                    <x-heroicon-o-chevron-up
                                        class="w-5 h-5 text-gray-500 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': !openAdditionalInfo }" />
                                </button>
                                <div x-show="openAdditionalInfo" x-collapse class="px-5 pb-5">
                                    <div class="pt-4 border-t border-gray-100 space-y-3">
                                        @foreach ($addInfo as $info)
                                            @if (!empty($info['content']))
                                                <div
                                                    class="p-4 bg-gradient-to-br from-gray-50 to-white rounded-lg border border-gray-200 hover:border-primary-200 hover:shadow-sm transition-all duration-200">
                                                    <p class="text-gray-700 leading-relaxed text-base">
                                                        {{ $info['content'] }}
                                                    </p>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg p-5 border border-gray-200">
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-gray-600 font-medium">No data available for simple view</p>
                            <p class="text-gray-500 text-sm mt-1">There are no publications, FAQs, or additional
                                information to display.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tab Layout View Content --}}
        <div x-show="viewMode === 'tab'" x-transition>
            @if (count($tabs) > 0)
                @php
                    $firstTabSlug = Str::slug($tabs[0]['tab_title'] ?? 'tab', '-');
                @endphp
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        {{-- Tabs Navigation --}}
                        <div x-data="{ activeTab: '{{ $firstTabSlug }}' }" class="space-y-6">
                            <div class="border-b border-gray-200">
                                <nav class="flex gap-6 overflow-x-auto" aria-label="Tabs">
                                    @foreach ($tabs as $tab)
                                        <button type="button"
                                            x-on:click="activeTab = '{{ Str::slug($tab['tab_title'] ?? 'tab', '-') }}'"
                                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                            :class="activeTab === '{{ Str::slug($tab['tab_title'] ?? 'tab', '-') }}' ?
                                                'border-primary text-primary' :
                                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                            {{ $tab['tab_title'] ?? 'Tab' }}
                                        </button>
                                    @endforeach
                                </nav>
                            </div>

                            {{-- Tab Content --}}
                            <div class="py-4">
                                @foreach ($tabs as $tab)
                                    <div x-show="activeTab === '{{ Str::slug($tab['tab_title'] ?? 'tab', '-') }}'"
                                        x-transition>
                                        {!! $tab['tab_content'] ?? '<p class="text-gray-600">No content available for this tab.</p>' !!}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg p-5 border border-gray-200">
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <x-heroicon-o-view-columns class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-gray-600 font-medium">No data available for tab view</p>
                            <p class="text-gray-500 text-sm mt-1">There are no tabs configured for this department.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
