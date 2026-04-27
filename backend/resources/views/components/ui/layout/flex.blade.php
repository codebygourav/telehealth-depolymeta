@props([
    'direction' => 'row', // row, col
    'align' => 'center', // start, center, end
    'justify' => 'start', // start, center, end, between, around, evenly
    'gap' => '2',
    'wrap' => false,
    'class' => ''
])

@php
$alignments = [
    'start' => 'items-start',
    'center' => 'items-center',
    'end' => 'items-end',
];

$justifications = [
    'start' => 'justify-start',
    'center' => 'justify-center',
    'end' => 'justify-end',
    'between' => 'justify-between',
    'around' => 'justify-around',
    'evenly' => 'justify-evenly',
];

$wrapClass = $wrap ? 'flex-wrap' : 'flex-nowrap';
@endphp

<div {{ $attributes->merge([
    'class' =>
        "flex flex-{$direction} {$alignments[$align]} {$justifications[$justify]} gap-{$gap} {$wrapClass} {$class}"
]) }}>
    {{ $slot }}
</div>
