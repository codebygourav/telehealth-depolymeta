<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-semibold text-amber-900">Video Consultation Links</h3>
            <p class="mt-1 text-sm text-amber-800">
                Manage Whereby video consultation links for video appointments.
                Use <strong>Generate Link</strong> to create a room if it is missing. Make sure
                <code class="rounded bg-amber-100 px-1">WHEREBY_API_KEY</code> is set in Settings &gt; Third Party API.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                wire:click="setScope('today')"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $scope === 'today' ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
            >
                Today
            </button>
            <button
                wire:click="setScope('upcoming')"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $scope === 'upcoming' ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
            >
                Upcoming
            </button>
            <button
                wire:click="setScope('all')"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $scope === 'all' ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
            >
                All
            </button>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
