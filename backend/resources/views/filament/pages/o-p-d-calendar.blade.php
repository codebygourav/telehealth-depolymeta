<x-filament-panels::page>
    {{-- Header with View Switcher and Filters --}}
    {{-- <div class="mb-6 space-y-4"> --}}

    <x-ui.page-header>

        {{-- Filters --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- View Mode Buttons --}}
        <div class="flex flex-wrap gap-2">
            <x-filament::button wire:click="changeView('day')" :color="$viewMode === 'day' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'day'"
                class="{{ $viewMode === 'day' ? 'font-semibold' : '' }}">
                Day
            </x-filament::button>
            <x-filament::button wire:click="changeView('week')" :color="$viewMode === 'week' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'week'"
                class="{{ $viewMode === 'week' ? 'font-semibold' : '' }}">
                Week
            </x-filament::button>
            <x-filament::button wire:click="changeView('month')" :color="$viewMode === 'month' ? 'success' : 'gray'" size="sm" :outlined="$viewMode !== 'month'"
                class="{{ $viewMode === 'month' ? 'font-semibold' : '' }}">
                Month
            </x-filament::button>
        </div>
        {{--
    </div> --}}
    </x-ui.page-header>

    {{-- Calendar View --}}
    <x-ui.page-body>
        @if ($viewMode === 'day')
            @include('components.calendar.day-view')
        @elseif ($viewMode === 'week')
            @include('components.calendar.week-view')
        @elseif ($viewMode === 'month')
            @include('components.calendar.month-view')
        @endif
    </x-ui.page-body>
</x-filament-panels::page>
