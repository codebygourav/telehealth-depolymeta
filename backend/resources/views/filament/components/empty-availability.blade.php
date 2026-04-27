@props(['state' => []])

@php
    $day = is_array($state) ? $state['day'] ?? 'this day' : 'this day';
@endphp

<div class="flex flex-col items-center justify-center py-12 px-6 text-center">
    <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
        <x-heroicon-o-calendar-days class="w-8 h-8 text-gray-400" />
    </div>
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
        No Slots Available
    </h3>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        No consultation slots scheduled for {{ $day }}
    </p>
</div>
