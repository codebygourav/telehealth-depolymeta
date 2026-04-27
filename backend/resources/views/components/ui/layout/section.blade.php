@props(['title' => null, 'class' => ''])

<section {{ $attributes->merge(['class' => "p-6 border rounded-xl bg-gray-50 {$class}"]) }}>
    @if($title)
        <h3 class="text-lg font-semibold mb-4">{{ $title }}</h3>
    @endif

    {{ $slot }}
</section>
