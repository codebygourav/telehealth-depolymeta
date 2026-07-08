<div class="max-w-full space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice Prescriptions</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $voiceDraftCount }}</div>
        </div>

        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Saved Medicines</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalCreatedMedicinesCount }}</div>
        </div>

        <div class="min-w-0 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-400/20 dark:bg-amber-400/10">
            <div class="text-sm font-medium leading-5 text-amber-700 dark:text-amber-300">Doctor-Added Medicines</div>
            <div class="mt-2 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ $doctorAddedCount }}</div>
        </div>

        <div class="min-w-0 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-400/20 dark:bg-emerald-400/10">
            <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Stock Medicines</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ $inventoryCount }}</div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
        <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recently Applied Voice Prescriptions</h3>
            </div>

            <div class="max-w-full overflow-x-auto max-h-[380px] overflow-y-auto scrollbar-thin">
                <table class="min-w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Doctor</th>
                            <th class="px-4 py-3 font-semibold">Patient</th>
                            <th class="px-4 py-3 font-semibold">Saved Medicines</th>
                            <th class="px-4 py-3 font-semibold">Applied At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($recentVoiceDrafts as $draft)
                            @php
                                $createdMedicines = collect($draft->submitted_payload['created_medicines'] ?? []);
                                $doctorAddedForDraft = $createdMedicines->where('medicine_source', 'doctor_added')->count();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 align-top text-gray-900 dark:text-white">
                                    {{ $draft->doctor ? 'Dr. ' . trim($draft->doctor->first_name . ' ' . $draft->doctor->last_name) : 'Unknown Doctor' }}
                                </td>
                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ $draft->patient ? trim($draft->patient->first_name . ' ' . $draft->patient->last_name) : 'Unknown Patient' }}
                                </td>
                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ $createdMedicines->count() }}</span>
                                    @if($doctorAddedForDraft > 0)
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20">
                                            {{ $doctorAddedForDraft }} doctor-added
                                        </span>
                                    @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top text-gray-500 dark:text-gray-400">
                                    {{ optional($draft->applied_at ?? $draft->created_at)->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No applied voice prescriptions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Doctor-Added Medicine Names</h3>
            </div>

            <div class="max-w-full overflow-x-auto max-h-[380px] overflow-y-auto scrollbar-thin">
                <table class="min-w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Medicine</th>
                            <th class="px-4 py-3 font-semibold">Used</th>
                            <th class="px-4 py-3 font-semibold">Last Doctor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($doctorAddedMedicines as $medicine)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-gray-900 break-words dark:text-white">
                                    {{ $medicine['medicine_name'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $medicine['count'] }} times
                                </td>
                                <td class="px-4 py-3 text-gray-500 break-words dark:text-gray-400">
                                    {{ $medicine['last_doctor_name'] ?: 'Unknown Doctor' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No doctor-added medicines captured from voice prescriptions.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
