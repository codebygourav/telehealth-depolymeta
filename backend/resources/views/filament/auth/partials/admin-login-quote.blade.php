@props([
    'quote' => '',
    'author' => '',
])

@if(filled($quote))
    <div class="dm-login-quote-card">
        <div class="dm-login-quote-mark">"</div>
        <p class="dm-login-quote-text">{{ $quote }}</p>
        @if(filled($author))
            <p class="dm-login-quote-author">- {{ ltrim((string) $author, "- \t\n\r\0\x0B") }}</p>
        @endif
    </div>
@endif
