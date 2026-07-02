@props([
    'alpine' => false,
    'notice' => null,
])

<div class="doctor-card-footer">
    <div class="footer-brand">
        <span style="opacity: 0.6; font-size: 13px;">Powered by</span>
        <img src="{{ asset('images/deploymeta.png') }}" alt="Deploy Meta Logo">
    </div>
    <div class="footer-alert-pill">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
        </svg>
        @if($alpine)
            <span x-text="displayCopy.default_notice || 'Please keep your token ready and be seated. Thank you!'"></span>
        @else
            <span>{{ $notice ?: 'Please keep your token ready and be seated. Thank you!' }}</span>
        @endif
    </div>
</div>
