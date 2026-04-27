@php
    $rating = $getRecord()->rating ?? 0;
    $colorClass = match ($rating) {
        5 => 'text-yellow-400',
        4 => 'text-yellow-400',
        3 => 'text-yellow-300',
        2 => 'text-yellow-200',
        1 => 'text-gray-300',
        default => 'text-gray-300',
    };
@endphp

<div class="flex items-center gap-1">
    @for ($i = 1; $i <= 5; $i++)
        @if ($i <= $rating)
            <x-heroicon-s-star class="w-6 h-6 {{ $colorClass }}" />
        @else
            <x-heroicon-o-star class="w-6 h-6 text-gray-300" />
        @endif
    @endfor
    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
        ({{ $rating }}/5)
    </span>
</div>
