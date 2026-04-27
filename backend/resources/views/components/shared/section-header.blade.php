@props([
    'type' => 'calendar', // 'calendar' | 'schedule' | 'appointments'
    'title' => null,
    'subtitle' => null,
    'count' => null,
    'countLabel' => null,
    'showNavigation' => false,
    'navigationPrevious' => null,
    'navigationNext' => null,
    'navigationLabel' => null,
    'sticky' => false,
])

@php
    // Dynamic configuration based on type
    $config = [
        'calendar' => [
            'icon' => 'heroicon-o-calendar',
            'title' => $title ?? 'Calendar',
            'showNav' => true,
        ],
        'schedule' => [
            'icon' => 'heroicon-o-calendar',
            'title' => $title ?? 'Doctor OPD Schedules',
            'showNav' => false,
        ],
        'appointments' => [
            'icon' => 'heroicon-o-calendar-days',
            'title' => $title ?? 'Patient Appointments',
            'showNav' => false,
        ],
    ];

    $currentConfig = $config[$type] ?? $config['calendar'];
    $displayTitle = $title ?? $currentConfig['title'];
    $displayIcon = $currentConfig['icon'];
    $showNav = $showNavigation ?? $currentConfig['showNav'];

    // Padding: calendar header -> px-4 py-3.5; others -> p-4
    $paddingClasses = $type === 'calendar' ? 'px-3 !py-3.5 bg-primary' : 'p-3';

    // Container classes
    $containerClasses = trim(
        $paddingClasses .
            ' rounded-xl border shadow
' .
            ($sticky ? 'mb-4' : ''),
    );
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    <div class="flex items-center justify-between">
        @if ($showNav && $navigationPrevious && $navigationNext)
            {{-- Navigation Mode (Calendar) --}}
            <x-filament::button wire:click="{{ $navigationPrevious }}" color="white" size="sm" class="stroke-width"
                icon="heroicon-o-chevron-left">
                Previous
            </x-filament::button>

            <h2 class="text-sm font-bold text-white">
                {{ $navigationLabel ?? now()->format('F Y') }}
            </h2>

            <x-filament::button wire:click="{{ $navigationNext }}" color="white" size="sm" class="stroke-width"
                icon-position="after" icon="heroicon-o-chevron-right">
                Next
            </x-filament::button>
        @else
            {{-- Standard Mode (Schedule/Appointments) --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-white">
                    @svg($displayIcon, 'w-3.5 h-3.5 text-primary')
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">
                        {{ $displayTitle }}
                    </h2>
                    @if ($subtitle)
                        <p class="text-xs text-white">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            </div>

            @if ($count !== null && $count > 0)
                <span class="px-2.5 py-0.5 rounded-xl bg-white text-primary text-xs font-semibold">
                    {{ $count }} {{ Str::plural($countLabel ?? 'Item', $count) }}
                </span>
            @endif
        @endif
    </div>
</div>
