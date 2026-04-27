@props([
    'class' => '',
])

<div
    data-slot="page-header"
    {{ $attributes->merge([
        'class' => "flex items-center justify-between gap-2 bg-white p-4 rounded-lg shadow" . $class,
    ]) }}
>
    {{ $slot }}
</div>
