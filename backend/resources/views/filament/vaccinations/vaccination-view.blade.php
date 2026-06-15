@php
    $record = $getState();
    $record->loadMissing('faqs');
@endphp

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-primary-50/50 to-transparent dark:from-primary-950/20">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-600 dark:bg-primary-500 flex items-center justify-center shadow-lg shadow-primary-500/10">
                        <x-heroicon-o-shield-check class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->name }}</h2>
                        <div class="flex items-center gap-2 mt-1">
                            @if($record->short_name)
                                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md">
                                    {{ $record->short_name }}
                                </span>
                            @endif
                            @if($record->disease_for)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Protects against: <strong class="text-gray-700 dark:text-gray-300">{{ $record->disease_for }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    @if($record->is_active)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-500"></span>
                            Active Vaccine
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Content (2/3 width) -->
        <div class="md:col-span-2 space-y-6">
            <!-- Medical Notes & Instructions -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-document-text class="w-5 h-5 text-gray-500" />
                        <span>Medical Profile & Info</span>
                    </div>
                </x-slot>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Description</h4>
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                            {{ $record->description ?: 'No description provided.' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Dosage Information</h4>
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800 min-h-[5rem]">
                                {{ $record->dosage_information ?: 'Standard dosage guidelines apply.' }}
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Side Effects</h4>
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800 min-h-[5rem]">
                                {{ $record->side_effects ?: 'Mild soreness at site, slight fever.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- FAQs -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-question-mark-circle class="w-5 h-5 text-gray-500" />
                        <span>Frequently Asked Questions ({{ $record->faqs->count() }})</span>
                    </div>
                </x-slot>

                @if($record->faqs->isNotEmpty())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($record->faqs as $faq)
                            <div class="py-4 first:pt-0 last:pb-0">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-start gap-2">
                                    <span class="text-primary-600 dark:text-primary-400 font-extrabold">Q:</span>
                                    <span>{{ $faq->question }}</span>
                                </h4>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 pl-6 leading-relaxed">
                                    {{ $faq->answer }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-sm text-gray-400 dark:text-gray-500 italic bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                        No specific FAQs added for this vaccine.
                    </div>
                @endif
            </x-filament::section>
        </div>

        <!-- Sidebar Content (1/3 width) -->
        <div class="space-y-6">
            <!-- Warnings & Contraindications -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-amber-500" />
                        <span>Safety Warnings</span>
                    </div>
                </x-slot>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-500">Contraindications</h4>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 leading-relaxed bg-amber-50/30 dark:bg-amber-950/10 p-3 rounded-lg border border-amber-100/50 dark:border-amber-900/20">
                            {{ $record->contraindications ?: 'None listed. Check prior allergy history.' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Precautions</h4>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                            {{ $record->precautions ?: 'Check patient vitals and temp before administration.' }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</div>
