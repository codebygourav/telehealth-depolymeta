@props(['getState'])

@php
    // $getState should be a closure or value; call if closure, else use as-is
    $value = $getState instanceof \Closure ? $getState() : $getState;
@endphp

@if (!empty($value))
    <div class="inline-flex items-center justify-center rounded-xl bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 border border-gray-200 shadow-sm hover:bg-gray-200 transition"
        title="Additional doctors">
        {{ $value }}
    </div>
@endif
