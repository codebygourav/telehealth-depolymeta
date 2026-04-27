@php
    $doctorAddedMedicines = \App\Models\DoctorAddedMedicine::with(['doctor.user'])
        ->withCount('prescriptions')
        ->latest()
        ->get();
@endphp

<div class="space-y-4">
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3 font-semibold">Medicine Name</th>
                    <th class="px-4 py-3 font-semibold">Added By Doctor</th>
                    <th class="px-4 py-3 font-semibold">Prescriptions Used</th>
                    <th class="px-4 py-3 font-semibold">Added Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($doctorAddedMedicines as $medicine)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                            {{ $medicine->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $medicine->doctor?->user?->name ?? 'Unknown Doctor' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                {{ $medicine->prescriptions_count }} Prescriptions
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $medicine->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No doctor added medicines found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
