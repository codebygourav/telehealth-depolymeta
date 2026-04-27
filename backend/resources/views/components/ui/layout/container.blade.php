@props([
    'size' => 'lg', // sm, md, lg, xl, full
    'class' => ''
])

@php
$sizes = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-2xl',
    'lg' => 'max-w-4xl',
    'xl' => 'max-w-7xl',
    'full' => 'max-w-full',
];
@endphp

<div {{ $attributes->merge([
    'class' => "{$sizes[$size]} mx-auto px-4 {$class}"
]) }}>
    {{ $slot }}
</div>
