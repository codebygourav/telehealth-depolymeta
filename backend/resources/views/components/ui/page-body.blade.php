@props([
    'class' => '',
])

<div
    data-slot="page-body"
    {{ $attributes->merge([
        'class' =>
            "rounded-lg p-4 gap-2 shadow bg-white " . $class,
    ]) }}
>
    {{ $slot }}
</div>
