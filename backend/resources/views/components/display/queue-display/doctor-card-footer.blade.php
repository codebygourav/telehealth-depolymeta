@props([
    'alpine' => false,
    'notice' => null,
    'displayCopyAccessor' => 'displayCopy',
])
@php
    $logo = asset('images/white-logo.png');

    $settingLogo = \App\Models\Setting::getValue('app', 'logo');

    if ($settingLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($settingLogo)) {
        $logo = \Illuminate\Support\Facades\Storage::url($settingLogo);
    }
@endphp
<div class="doctor-card-footer">
    <div class="footer-brand">
        <img src="{{ $logo }}" alt="Deploy Meta Logo">
    </div>
    <div class="footer-alert-pill">
        <img src="{{ asset('images/queue-images/annoucment.png') }}" alt="Deploy Meta Logo">
        @if($alpine)
            <span x-text="{{ $displayCopyAccessor }}.default_notice || 'Please keep your token ready and be seated. Thank you!'"></span>
        @else
            <span>{{ $notice ?: 'Please keep your token ready and be seated. Thank you!' }}</span>
        @endif
</div>
</div>
