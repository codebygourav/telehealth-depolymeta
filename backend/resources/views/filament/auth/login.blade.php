@php
    $appName = setting('app.name', config('app.name', 'DeployMeta'));
    $tagline = setting('app.tagline', 'DeployMeta empowers teams with reliable, secure, and efficient software deployment solutions.');
    $quote = setting('app.admin_login_quote', "We can make up for lost money, but we can't make up for lost time.");
    $quoteAuthor = setting('app.admin_login_quote_author', '-Simon Sinek');
    $logo = app_logo() ?: asset('images/deploymeta.png');
@endphp

<style>
    .fi-simple-main-ctn {
        max-width: min(1080px, calc(100vw - 2rem)) !important;
    }

    .fi-simple-main {
        width: 100%;
    }

    .fi-simple-layout {
        background:
            radial-gradient(circle at top left, rgba(5, 91, 217, 0.09), transparent 32%),
            linear-gradient(180deg, #f7faff 0%, #eef4ff 100%);
    }

    .fi-simple-layout .fi-simple-main {
        box-shadow: none;
        background: transparent;
    }

    .dm-login-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 0.92fr);
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 32px 70px rgba(15, 23, 42, 0.12);
    }

    .dm-login-content {
        padding: 44px 40px 38px;
    }

    .dm-login-copy {
        max-width: 27rem;
    }

    .dm-login-title {
        margin: 0;
        font-size: clamp(2rem, 2.4vw, 3rem);
        line-height: 1.02;
        letter-spacing: -0.05em;
        font-weight: 800;
        color: #111827;
    }

    .dm-login-subtitle {
        margin: 14px 0 0;
        color: #6b7280;
        font-size: 0.98rem;
        line-height: 1.7;
    }

    .dm-login-quote-card {
        margin-top: 28px;
        padding: 22px 22px 18px;
        border-radius: 18px;
        border: 1px solid #dbe5f4;
        background: linear-gradient(180deg, #fbfdff 0%, #f6f9ff 100%);
    }

    .dm-login-quote-mark {
        color: #2563eb;
        font-size: 2.2rem;
        font-weight: 800;
        line-height: 1;
    }

    .dm-login-quote-text {
        margin: 6px 0 0;
        color: #223e73;
        font-size: 1.18rem;
        line-height: 1.6;
        font-style: italic;
        font-weight: 700;
    }

    .dm-login-quote-author {
        margin: 16px 0 0;
        color: #8aa0c8;
        font-size: 0.98rem;
        font-weight: 700;
    }

    .dm-login-form {
        margin-top: 28px;
    }

    .dm-login-form .fi-fo-field-wrp-label span,
    .dm-login-form .fi-input-wrp {
        color: inherit;
    }

    .dm-login-form .fi-btn {
        min-height: 44px;
        border-radius: 12px;
    }

    .dm-login-art {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100%;
        padding: 32px;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 26%),
            linear-gradient(180deg, #eef4ff 0%, #e7eefc 100%);
    }

    .dm-login-art::after {
        content: '';
        position: absolute;
        inset: 18px;
        border-radius: 24px;
        border: 1px solid rgba(37, 99, 235, 0.08);
    }

    .dm-login-art-inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 18px;
        justify-items: center;
        text-align: center;
    }

    .dm-login-art-logo {
        max-width: min(100%, 280px);
        width: 100%;
        height: auto;
        object-fit: contain;
    }

    .dm-login-art-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 38px;
        padding: 0 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.86);
        color: #1d4ed8;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.08);
    }

    .dm-login-art-name {
        margin: 0;
        color: #111827;
        font-size: clamp(2rem, 2.8vw, 3.2rem);
        line-height: 0.95;
        letter-spacing: -0.05em;
        font-weight: 900;
    }

    @media (max-width: 960px) {
        .dm-login-shell {
            grid-template-columns: 1fr;
        }

        .dm-login-art {
            min-height: 240px;
        }
    }

    @media (max-width: 640px) {
        .fi-simple-main-ctn {
            max-width: calc(100vw - 1rem) !important;
        }

        .dm-login-content,
        .dm-login-art {
            padding: 24px 20px;
        }
    }
</style>

<div class="dm-login-shell">
    <div class="dm-login-content">
        <div class="dm-login-copy">
            <h1 class="dm-login-title">{{ $appName }} Support</h1>
            @if(filled($tagline))
                <p class="dm-login-subtitle">{{ $tagline }}</p>
            @endif

            @include('filament.auth.partials.admin-login-quote', [
                'quote' => $quote,
                'author' => $quoteAuthor,
            ])
        </div>

        <div class="dm-login-form">
            {{ $this->content }}
        </div>
    </div>

    <div class="dm-login-art">
        <div class="dm-login-art-inner">
            <div class="dm-login-art-badge">Admin Portal</div>
            <img src="{{ $logo }}" alt="{{ $appName }}" class="dm-login-art-logo">
            <p class="dm-login-art-name">{{ $appName }}</p>
        </div>
    </div>
</div>
