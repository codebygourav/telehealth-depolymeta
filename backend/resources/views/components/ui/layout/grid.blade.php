@props([
    'cols' => 3,
    'gap' => 4,
    'md' => null,
    'lg' => null,
    'class' => ''
])

@php
$mdCols = $md ? "md:grid-cols-{$md}" : '';
$lgCols = $lg ? "lg:grid-cols-{$lg}" : '';
@endphp

<div {{ $attributes->merge([
    'class' =>
        "grid grid-cols-{$cols} {$mdCols} {$lgCols} gap-{$gap} {$class}"
]) }}>
    {{ $slot }}
</div>
