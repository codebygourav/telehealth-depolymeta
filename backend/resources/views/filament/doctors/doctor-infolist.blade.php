@php
    $doctor = $entry->getRecord();
    $fullName = trim(
        $doctor?->first_name . ' ' . $doctor?->last_name !== ' '
            ? $doctor?->first_name . ' ' . $doctor?->last_name
            : $doctor?->user?->name ?? '—',
    );

    // Normalization Helpers
    $normalizeArray = function ($data) {
        if (is_array($data)) {
            return $data;
        }
        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }
        return [];
    };

    $education = $normalizeArray($doctor->education_info);
    $certifications = $normalizeArray($doctor->certifications_info ?? $doctor->certification_entries);
    $awards = $normalizeArray($doctor->awards_info);
    $experience = $normalizeArray($doctor->professional_experience_info);
    $fellowships = $normalizeArray($doctor->fellowships_info);
    $specializations = $normalizeArray($doctor->specializations_info);
    $keyProcedures = $normalizeArray($doctor->key_procedures_info);
    $expertise = $normalizeArray($doctor->expertise_info);
    $languages = $normalizeArray($doctor->languages_known);
    $socialLinks = $normalizeArray($doctor->social_links);
    $departments = $doctor->departments ?? collect();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="flex flex-col gap-6 pb-8">

        {{-- 1. HERO PROFILE SECTION --}}
        <div
            class="relative bg-white dark:bg-gray-900 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800">
            <!-- Background Cover -->
            <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-r from-primary/80 to-primary/40"></div>

            <div class="relative pt-12 px-6 sm:px-8 pb-8 flex flex-col md:flex-row gap-6 items-start md:items-center">

                <!-- Avatar -->
                <div class="shrink-0 relative">
                    <div
                        class="w-28 h-28 rounded-full border-4 border-white dark:border-gray-900 overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        @if ($doctor?->avatar)
                            <img src="{{ storage_url($doctor->avatar) }}" alt="{{ $fullName }}"
                                class="w-full h-full object-cover">
                        @else
                            <x-heroicon-o-user class="w-16 h-16 text-gray-400" />
                        @endif
                    </div>
                    @if ($doctor?->status?->value === 'active' || $doctor?->status === 'active')
                        <span
                            class="absolute bottom-2 right-2 w-4 h-4 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full flex items-center justify-center"
                            title="Active">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        </span>
                    @endif
                </div>

                <!-- Main Details -->
                <div class="flex-1 space-y-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            {{ $fullName }}</h1>
                        @if ($doctor->qualification || !empty($education) || $departments->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                @if ($departments->isNotEmpty())
                                    <span
                                        class="text-xs font-bold text-primary px-0     py-0.5 bg-primary/10 rounded-md">
                                        {{ $departments->pluck('name')->join(', ') }}
                                    </span>
                                @endif
                                <span class="text-base font-medium text-gray-600 dark:text-gray-300">
                                    {{ $doctor->qualification ?: collect($education)->pluck('degree')->filter()->implode(', ') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($doctor->years_experience)
                            <span
                                class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-full font-medium">
                                <x-heroicon-o-briefcase class="w-4 h-4 text-primary" />
                                {{ $doctor->years_experience }} Years Exp.
                            </span>
                        @endif
                        @if ($doctor->medical_license_number)
                            <span
                                class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-full font-medium">
                                <x-heroicon-o-identification class="w-4 h-4 text-primary" />
                                License: {{ $doctor->medical_license_number }}
                            </span>
                        @endif
                        @if (!empty($languages))
                            <span
                                class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-full font-medium">
                                <x-heroicon-o-language class="w-4 h-4 text-primary" />
                                {{ implode(', ', $languages) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Action / Contact Fast -->
                <div class="flex flex-col gap-2 w-full md:w-auto">
                    @if ($doctor?->user?->phone)
                        <a href="tel:{{ $doctor->user->phone }}"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <x-heroicon-o-phone class="w-4 h-4 text-primary" />
                            <span
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $doctor->user->phone }}</span>
                        </a>
                    @endif
                    @if ($doctor?->user?->email)
                        <a href="mailto:{{ $doctor->user->email }}"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition text-wrap break-all">
                            <x-heroicon-o-envelope class="w-4 h-4 text-primary" />
                            <span
                                class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate max-w-[200px]">{{ $doctor->user->email }}</span>
                        </a>
                    @endif
                </div>

            </div>
        </div>

        {{-- 2. BIO & DESCRIPTION --}}
        @if ($doctor->bio || $doctor->description)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-primary" />
                    About Doctor
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 space-y-3">
                    @if ($doctor->bio)
                        <div class="leading-relaxed text-base prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                            @if (strip_tags($doctor->bio) !== $doctor->bio)
                                {!! $doctor->bio !!}
                            @else
                                {{ $doctor->bio }}
                            @endif
                        </div>
                    @endif
                    @if ($doctor->description)
                        <div class="text-sm opacity-90 prose dark:prose-invert max-w-none">
                            @if (strip_tags($doctor->description) !== $doctor->description)
                                {!! $doctor->description !!}
                            @else
                                {!! nl2br(e($doctor->description)) !!}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 3. MAIN CONTENT (Flex Column replacing empty-space-prone Grid) --}}
        <div class="flex flex-col lg:flex-row gap-6 items-start">

            {{-- LEFT COLUMN (Personal, Location, Links) - 1/3 width --}}
            <div class="w-full lg:w-1/3 flex flex-col gap-6">

                {{-- Personal Details --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                    <h3
                        class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">
                        Personal Details</h3>
                    <ul class="space-y-3">
                        @if ($doctor->dob)
                            <li class="flex items-center justify-between">
                                <span class="text-gray-500 text-sm flex items-center gap-2"><x-heroicon-o-cake
                                        class="w-4 h-4" /> DOB</span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($doctor->dob)->format('d M, Y') }}</span>
                            </li>
                        @endif
                        @if ($doctor->gender)
                            <li class="flex items-center justify-between">
                                <span class="text-gray-500 text-sm flex items-center gap-2"><x-heroicon-o-user
                                        class="w-4 h-4" /> Gender</span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $doctor->gender->value ?? $doctor->gender }}</span>
                            </li>
                        @endif
                        @if ($doctor->blood_group)
                            <li class="flex items-center justify-between">
                                <span class="text-gray-500 text-sm flex items-center gap-2"><x-heroicon-o-heart
                                        class="w-4 h-4" /> Blood Group</span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-white uppercase">{{ $doctor->blood_group }}</span>
                            </li>
                        @endif
                        @if ($doctor->marital_status)
                            <li class="flex items-center justify-between">
                                <span class="text-gray-500 text-sm flex items-center gap-2"><x-heroicon-o-users
                                        class="w-4 h-4" /> Marital Status</span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $doctor->marital_status }}</span>
                            </li>
                        @endif
                    </ul>
                </div>

                {{-- Location / Address --}}
                @if ($doctor->address_line1 || $doctor->city || $doctor->state)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3
                            class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">
                            Location</h3>
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 p-1.5 bg-primary/10 rounded-lg">
                                <x-heroicon-o-map-pin class="w-4 h-4 text-primary" />
                            </div>
                            <div class="flex-1 text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                                @if ($doctor->address_line1)
                                    <p>{{ $doctor->address_line1 }}</p>
                                @endif
                                @if ($doctor->address_line2)
                                    <p>{{ $doctor->address_line2 }}</p>
                                @endif
                                <p class="mt-1 font-medium">
                                    {{ collect([$doctor->city, $doctor->state, $doctor->pincode, $doctor->country])->filter()->implode(', ') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Social Links --}}
                @if (!empty($socialLinks))
                    @php
                        $platformIcons = [
                            'twitter' => 'heroicon-o-at-symbol',
                            'facebook' => 'heroicon-o-globe-alt',
                            'instagram' => 'heroicon-o-camera',
                            'linkedin' => 'heroicon-o-briefcase',
                            'website' => 'heroicon-o-globe-alt',
                            'youtube' => 'heroicon-o-play',
                        ];
                    @endphp
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3
                            class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">
                            Social Links</h3>
                        <div class="space-y-2">
                            @foreach ($socialLinks as $platform => $url)
                                @if (!is_numeric($platform) && $url)
                                    <a href="{{ $url }}" target="_blank"
                                        class="flex items-center justify-between p-2 rounded-xl border border-gray-100 hover:border-primary/30 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800 transition group">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                                <x-dynamic-component :component="$platformIcons[strtolower($platform)] ?? 'heroicon-o-link'" class="w-4 h-4" />
                                            </div>
                                            <span
                                                class="text-sm font-medium text-gray-800 dark:text-gray-200 capitalize">{{ $platform }}</span>
                                        </div>
                                        <x-heroicon-o-arrow-top-right-on-square
                                            class="w-4 h-4 text-gray-400 group-hover:text-primary" />
                                    </a>
                                @endif
                                @if (is_numeric($platform) && is_array($url))
                                    @php
                                        $pName = key($url);
                                        $pUrl = current($url);
                                    @endphp
                                    @if ($pUrl)
                                        <a href="{{ $pUrl }}" target="_blank"
                                            class="flex items-center justify-between p-2 rounded-xl border border-gray-100 hover:border-primary/30 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800 transition group">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                                    <x-dynamic-component :component="$platformIcons[strtolower($pName)] ?? 'heroicon-o-link'" class="w-4 h-4" />
                                                </div>
                                                <span
                                                    class="text-sm font-medium text-gray-800 dark:text-gray-200 capitalize">{{ $pName }}</span>
                                            </div>
                                            <x-heroicon-o-arrow-top-right-on-square
                                                class="w-3 h-3 text-gray-400 group-hover:text-primary" />
                                        </a>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN (Professional Data) - 2/3 width --}}
            <div class="w-full lg:w-2/3 flex flex-col gap-6">

                {{-- Experience & Education Stack --}}
                @if (!empty($experience) || !empty($education))
                    <div class="flex flex-col gap-6">
                        @if (!empty($experience))
                            <div
                                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                                <h3
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                                    <x-heroicon-o-briefcase class="w-5 h-5 text-primary" /> Experience
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach ($experience as $exp)
                                        <div
                                            class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 hover:border-primary/30 transition">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                {{ !empty($exp['position']) ? $exp['position'] : (!empty($exp['association']) ? $exp['association'] : (!empty($exp['name']) ? $exp['name'] : '—')) }}
                                            </h4>
                                            @if (!empty($exp['hospital']) || !empty($exp['institution']) || !empty($exp['organization']))
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-1">
                                                    {{ $exp['hospital'] ?? ($exp['organization'] ?? ($exp['institution'] ?? '')) }}
                                                </p>
                                            @endif
                                            @if (!empty($exp['description']))
                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 leading-relaxed">
                                                    {{ $exp['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($education))
                            <div
                                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                                <h3
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                                    <x-heroicon-o-academic-cap class="w-5 h-5 text-primary" /> Education
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach ($education as $edu)
                                        <div
                                            class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 hover:border-primary/30 transition">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                {{ $edu['degree'] ?? '—' }}</h4>
                                            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium mt-1">
                                                {{ $edu['institution'] ?? '—' }}</p>
                                            @if (!empty($edu['completion_year']))
                                                <div
                                                    class="flex items-center gap-1.5 mt-2 text-xs text-gray-500 bg-white dark:bg-gray-900 w-fit px-2 py-1 rounded-md border border-gray-200 dark:border-gray-700">
                                                    <x-heroicon-o-calendar class="w-4 h-4" />
                                                    <span>Completion Year: {{ $edu['completion_year'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Capabilities Grid (Specializations, Procedures, Expertise) --}}
                @if (
                    !empty($specializations) || !empty($doctor->specializations_info) ||
                    !empty($keyProcedures) || !empty($doctor->key_procedures_info) ||
                    !empty($expertise) || !empty($doctor->expertise_info)
                )
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <div class="flex flex-col gap-5">

                            @if (!empty($specializations) || !empty($doctor->specializations_info))
                                <div>
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <x-heroicon-o-star class="w-4 h-4 text-yellow-500" /> Specializations
                                    </h3>
                                    @if (!empty($specializations) && is_array($specializations) && count($specializations) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($specializations as $spec)
                                                <span
                                                    class="px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200">
                                                    {{ is_array($spec) ? $spec['name'] ?? ($spec['title'] ?? '') : $spec }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                                            @if (strip_tags($doctor->specializations_info) !== $doctor->specializations_info)
                                                {!! $doctor->specializations_info !!}
                                            @else
                                                {{ $doctor->specializations_info }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if (!empty($expertise) || !empty($doctor->expertise_info))
                                @if (!empty($specializations) || !empty($doctor->specializations_info))
                                    <div class="border-t border-gray-100 dark:border-gray-800 pt-5"></div>
                                @endif
                                <div>
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <x-heroicon-o-check-badge class="w-4 h-4 text-blue-500" /> Area of Expertise
                                    </h3>
                                    @if (!empty($expertise) && is_array($expertise) && count($expertise) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($expertise as $exp)
                                                <span
                                                    class="px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800 rounded-lg text-sm font-medium">
                                                    {{ is_array($exp) ? $exp['name'] ?? ($exp['title'] ?? '') : $exp }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                                            @if (strip_tags($doctor->expertise_info) !== $doctor->expertise_info)
                                                {!! $doctor->expertise_info !!}
                                            @else
                                                {{ $doctor->expertise_info }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if (!empty($keyProcedures) || !empty($doctor->key_procedures_info))
                                @if (
                                    !empty($specializations) || !empty($doctor->specializations_info) ||
                                    !empty($expertise) || !empty($doctor->expertise_info)
                                )
                                    <div class="border-t border-gray-100 dark:border-gray-800 pt-5"></div>
                                @endif
                                <div>
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <x-heroicon-o-sparkles class="w-4 h-4 text-purple-500" /> Key Procedures
                                    </h3>
                                    @if (!empty($keyProcedures) && is_array($keyProcedures) && count($keyProcedures) > 0)
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach ($keyProcedures as $proc)
                                                <div
                                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                                                    <x-heroicon-o-check-circle class="w-4 h-4 text-purple-500 shrink-0" />
                                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                        {{ is_array($proc) ? $proc['name'] ?? ($proc['title'] ?? '') : $proc }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                                            @if (strip_tags($doctor->key_procedures_info) !== $doctor->key_procedures_info)
                                                {!! $doctor->key_procedures_info !!}
                                            @else
                                                {{ $doctor->key_procedures_info }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                @endif

                {{-- Accordions --}}
                @if (
                    !empty($certifications) ||
                        !empty($awards) ||
                        !empty($fellowships) ||
                        $doctor->special_interests ||
                        $doctor->availability_info ||
                        $doctor->memberships_info)
                    <div class="flex flex-col gap-4">

                        {{-- Certifications --}}
                        @if (!empty($certifications))
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                                x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="p-1.5 bg-green-50 text-green-600 rounded-md">
                                            <x-heroicon-o-shield-check class="w-4 h-4" />
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Certifications &
                                            Licenses</h3>
                                    </div>
                                    <x-heroicon-o-chevron-down
                                        class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': open }" />
                                </button>
                                <div x-show="open" x-collapse x-cloak>
                                    <div class="p-4 pt-0 border-t border-gray-100 dark:border-gray-800">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                            @foreach ($certifications as $cert)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                                                    <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                        {{ $cert['name'] ?? '—' }}</h4>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        {{ $cert['organization'] ?? '—' }}</p>
                                                    @if (!empty($cert['description']))
                                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 leading-relaxed">
                                                            {{ $cert['description'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Awards --}}
                        @if (!empty($awards))
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                                x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="p-1.5 bg-yellow-50 text-yellow-600 rounded-md"><x-heroicon-o-trophy
                                                class="w-4 h-4" /></div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Awards & Honors
                                        </h3>
                                    </div>
                                    <x-heroicon-o-chevron-down
                                        class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': open }" />
                                </button>
                                <div x-show="open" x-collapse x-cloak>
                                    <div class="p-4 pt-0 border-t border-gray-100 dark:border-gray-800">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                            @foreach ($awards as $award)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                                                    <div class="mt-0.5"><x-heroicon-o-sparkles
                                                            class="w-4 h-4 text-yellow-500" /></div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                            {{ $award['title'] ?? '—' }}</h4>
                                                        @if (!empty($award['year']))
                                                            <p class="text-[11px] text-gray-400 mt-0.5">
                                                                {{ $award['year'] }}</p>
                                                        @endif
                                                        @if (!empty($award['description']))
                                                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 leading-relaxed">
                                                                {{ $award['description'] }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Fellowships --}}
                        @if (!empty($fellowships))
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                                x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="p-1.5 bg-blue-50 text-blue-600 rounded-md">
                                            <x-heroicon-o-academic-cap class="w-4 h-4" />
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Fellowships</h3>
                                    </div>
                                    <x-heroicon-o-chevron-down
                                        class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': open }" />
                                </button>
                                <div x-show="open" x-collapse x-cloak>
                                    <div class="p-4 pt-0 border-t border-gray-100 dark:border-gray-800">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                            @foreach ($fellowships as $fellowship)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                                                    <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                        {{ $fellowship['title'] ?? '—' }}</h4>
                                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                        {{ $fellowship['institution'] ?? '—' }}</p>
                                                    @if (!empty($fellowship['year_started']))
                                                        <p class="text-[11px] text-gray-400 mt-1">
                                                            {{ $fellowship['year_started'] }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Other Notes --}}
                        @if ($doctor->special_interests || $doctor->availability_info || $doctor->memberships_info)
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                                x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="p-1.5 bg-purple-50 text-purple-600 rounded-md">
                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Other Notes</h3>
                                    </div>
                                    <x-heroicon-o-chevron-down
                                        class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                        x-bind:class="{ 'rotate-180': open }" />
                                </button>
                                <div x-show="open" x-collapse x-cloak>
                                    <div class="p-4 pt-0 border-t border-gray-100 dark:border-gray-800">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                                            @if ($doctor->special_interests)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                                                    <div class="mt-0.5"><x-heroicon-o-star
                                                            class="w-4 h-4 text-purple-500" /></div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                            Special Interests</h4>
                                                        <div
                                                            class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-0.5 prose dark:prose-invert max-w-none">
                                                            @if (strip_tags($doctor->special_interests) !== $doctor->special_interests)
                                                                {!! $doctor->special_interests !!}
                                                            @else
                                                                {{ $doctor->special_interests }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($doctor->memberships_info)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                                                    <div class="mt-0.5"><x-heroicon-o-user-group
                                                            class="w-4 h-4 text-purple-500" /></div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                            Memberships</h4>
                                                        <div
                                                            class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-0.5 prose dark:prose-invert max-w-none">
                                                            @if (strip_tags($doctor->memberships_info) !== $doctor->memberships_info)
                                                                {!! $doctor->memberships_info !!}
                                                            @else
                                                                {{ $doctor->memberships_info }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($doctor->availability_info)
                                                <div
                                                    class="p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                                                    <div class="mt-0.5"><x-heroicon-o-clock
                                                            class="w-4 h-4 text-purple-500" /></div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                            Availability Notes</h4>
                                                        <div
                                                            class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-0.5 prose dark:prose-invert max-w-none">
                                                            @if (strip_tags($doctor->availability_info) !== $doctor->availability_info)
                                                                {!! $doctor->availability_info !!}
                                                            @else
                                                                {{ $doctor->availability_info }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

            </div>
        </div>

        <!-- Weekly Schedule Wrapper is below this -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg p-8">

            <!-- Section Header -->
            <div class="flex items-center gap-3 mb-10">
                <x-heroicon-o-calendar-days class="w-7 h-7 text-primary" />
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                    Weekly Availability Schedule
                </h2>
            </div>

            <!-- Tabs -->
            <div x-data="{ tab: '{{ strtolower(now()->format('l')) }}' }" x-cloak>
                <div class="flex justify-center w-full">
                    <div
                        class="flex gap-2 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 rounded-xl p-2 overflow-x-auto">
                        @php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $today = strtolower(now()->format('l'));

                            $availabilityByDay = collect($doctor?->availabilities ?? [])
                                ->filter(fn($slot) => (bool) ($slot['is_available'] ?? true))
                                ->groupBy(function ($slot) {
                                    if (!empty($slot['day_of_week'])) {
                                        return strtolower($slot['day_of_week']);
                                    }
                                    if (!empty($slot['date'])) {
                                        return strtolower(\Carbon\Carbon::parse($slot['date'])->format('l'));
                                    }
                                    if (!empty($slot['recurring_start_date'])) {
                                        return strtolower(
                                            \Carbon\Carbon::parse($slot['recurring_start_date'])->format('l'),
                                        );
                                    }
                                    return 'unknown';
                                });
                        @endphp

                        @foreach ($days as $day)
                            @php
                                $dayLower = strtolower($day);
                                $slotCount = $availabilityByDay->get($dayLower, collect())->count();
                            @endphp

                            <button type="button" x-on:click="tab = '{{ $dayLower }}'"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 min-w-[110px] justify-center
                                       focus:outline-none focus:ring-2 focus:ring-primary/50 relative whitespace-nowrap"
                                :class="tab === '{{ $dayLower }}' ?
                                    'bg-primary text-white shadow-md scale-[1.02]' :
                                    'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-primary/5 hover:border-primary/30'">

                                <x-heroicon-o-calendar class="w-4 h-4"
                                    x-bind:class="tab === '{{ $dayLower }}' ? 'text-white' : 'text-primary'" />

                                <span>{{ $day }}</span>

                                @if ($slotCount > 0)
                                    <span class="text-xs px-1.5 py-0.5 rounded-xl font-semibold"
                                        x-bind:class="tab === '{{ $dayLower }}' ? 'bg-white/20 text-white' :
                                            'bg-primary/10 text-primary'">
                                        {{ $slotCount }}
                                    </span>
                                @endif

                                @if ($dayLower === $today)
                                    <div class="absolute -top-1 -right-1">
                                        <span class="flex h-3 w-3">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-xl opacity-75"
                                                x-bind:class="tab === '{{ $dayLower }}' ? 'bg-yellow-400' : 'bg-primary'"></span>
                                            <span class="relative inline-flex rounded-xl h-3 w-3"
                                                x-bind:class="tab === '{{ $dayLower }}' ? 'bg-yellow-400' : 'bg-primary'"></span>
                                        </span>
                                    </div>
                                @endif
                            </button>
                        @endforeach

                    </div>
                </div>

                <!-- Day Content -->
                @foreach ($days as $day)
                    @php
                        $dayLower = strtolower($day);
                        $slots = $availabilityByDay->get($dayLower, collect())->sortBy('start_time')->values();
                    @endphp

                    <div x-show="tab === '{{ strtolower($day) }}'" x-transition class="mt-2">

                        @if ($slots->isEmpty())
                            <div
                                class="flex items-center justify-center py-16 text-center rounded-lg border border-gray-200 bg-gray-50">
                                <div>
                                    <x-heroicon-o-calendar
                                        class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
                                    <p class="text-gray-500 dark:text-gray-300 mt-4 font-semibold text-lg">
                                        No availability for <span class="font-bold">{{ $day }}</span>
                                    </p>
                                </div>
                            </div>
                        @else
                            <!-- Slots Grid -->
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 border border-gray-200 rounded-lg p-2">

                                @foreach ($slots as $slot)
                                    @php
                                        $type = strtolower($slot['consultation_type']);
                                        $isRecurring = $slot['is_recurring'];
                                    @endphp

                                    <div
                                        class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900
                                                rounded-2xl border border-gray-200 dark:border-gray-700
                                                shadow-sm hover:shadow-2xl hover:border-primary/30 dark:hover:border-primary/50
                                                transition-all duration-500 overflow-hidden">

                                        <!-- Top Accent Bar -->
                                        <div class="absolute top-0 left-0 right-0 h-1 bg-primary">
                                        </div>

                                        <!-- Decorative Background Element -->
                                        <div
                                            class="absolute -right-2 -bottom-4 w-24 h-24 opacity-5 group-hover:opacity-10 transition-opacity duration-500">
                                            @if ($type === 'video')
                                                <x-heroicon-o-video-camera class="w-full h-full text-emerald-600" />
                                            @elseif($type === 'in-person')
                                                <x-heroicon-o-building-office-2
                                                    class="w-full h-full text-orange-600" />
                                            @else
                                                <x-heroicon-o-calendar-days class="w-full h-full text-blue-600" />
                                            @endif
                                        </div>

                                        <div class="relative py-6 px-3 space-y-4">

                                            <!-- Header: Time Badge -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-100 group-hover:bg-primary/20 transition-colors duration-300">
                                                        <x-heroicon-o-clock class="w-6 h-6 text-primary" />
                                                    </div>
                                                    <div>
                                                        <p
                                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                            Time Slot</p>
                                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ \Carbon\Carbon::parse($slot['start_time'])->format('g:i A') }}
                                                            <span class="text-primary mx-1">→</span>
                                                            {{ \Carbon\Carbon::parse($slot['end_time'])->format('g:i A') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Consultation Type Badge -->
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold @if ($type === 'video') text-blue-800 bg-blue-100 @elseif($type === 'in-person') text-primary-800 bg-primary-100 @else text-gray-800 bg-gray-100 @endif">
                                                    @if ($type === 'video')
                                                        <x-heroicon-o-video-camera class="w-4 h-4" />
                                                    @elseif($type === 'in-person')
                                                        <x-heroicon-o-building-office-2 class="w-4 h-4" />
                                                    @else
                                                        <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                                                    @endif
                                                    {{ $slot['consultation_type'] === 'video' ? 'Video' : ($slot['consultation_type'] === 'in-person' ? 'In person' : ucfirst($slot['consultation_type'] ?? 'Any')) }}
                                                </span>
                                            </div>

                                            <!-- Divider -->
                                            <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                            <!-- Date Information -->
                                            <div class="space-y-3">
                                                @if ($isRecurring)
                                                    <!-- Recurring Date Range -->
                                                    <div
                                                        class="flex items-start gap-3 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200">
                                                        <x-heroicon-o-arrow-path
                                                            class="w-5 h-5 text-primary dark:text-primary mt-0.5 flex-shrink-0" />
                                                        <div class="flex-1">
                                                            <p
                                                                class="text-xs font-medium text-primary uppercase tracking-wide mb-1">
                                                                Recurring Schedule</p>
                                                            <p
                                                                class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                {{ \Carbon\Carbon::parse($slot['recurring_start_date'])->format('d M Y') }}
                                                                <span class="text-primary mx-2">→</span>
                                                                {{ \Carbon\Carbon::parse($slot['recurring_end_date'])->format('d M Y') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @else
                                                    <!-- One-time Date -->

                                                    <div
                                                        class="flex items-start gap-3 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200">
                                                        <x-heroicon-o-calendar
                                                            class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                                Date</p>
                                                            <p
                                                                class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                {{ $slot['date'] ? \Carbon\Carbon::parse($slot['date'])->format('D, d M Y') : '—' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Footer Stats -->
                                            <div
                                                class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                                <!-- Capacity -->
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10">
                                                        <x-heroicon-o-user-group class="w-5 h-5 text-primary" />
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Capacity
                                                        </p>
                                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ $slot['capacity'] }}
                                                            <span
                                                                class="text-xs font-normal text-gray-500">patients</span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Availability Type -->
                                                <div
                                                    class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800">
                                                    @if ($isRecurring)
                                                        <x-heroicon-o-arrow-path class="w-4 h-4 text-primary" />
                                                        <span
                                                            class="text-xs font-medium text-primary ">Recurring</span>
                                                    @else
                                                        <x-heroicon-o-calendar
                                                            class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                                        <span
                                                            class="text-xs font-medium text-gray-600 dark:text-gray-400">One-time</span>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        @endif

                    </div>
                @endforeach
            </div>
        </div>
    </div>

</x-dynamic-component>
