@php
    $headingCopy = method_exists($this, 'getAuthHeadingCopy') ? $this->getAuthHeadingCopy() : __('Sign in');
    $subheadingCopy = method_exists($this, 'getAuthSubheadingCopy') ? $this->getAuthSubheadingCopy() : null;
@endphp

<div class="w-full">
    <div class="mb-6 text-center">
        <div class="mb-3 flex justify-center">
            <img src="{{ asset('images/deploymeta.png') }}" alt="{{ config('app.name') }}" class="h-17">
        </div>

        @if ($subheadingCopy)
            <p class="mt-2 text-sm text-slate-500">{!! $subheadingCopy !!}</p>
        @endif
    </div>

    <div class="space-y-6">
        {{ $this->content }}
    </div>
</div>
