@props([
    'alpine' => false,
    'notice' => null,
    'displayCopyAccessor' => 'displayCopy',
])

<div class="doctor-card-footer">
    <div class="footer-brand">
        <img src="{{ asset('images/queue-images/powered_by_logo.jpg') }}" alt="Deploy Meta Logo">
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
