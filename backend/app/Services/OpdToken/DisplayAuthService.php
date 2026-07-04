<?php

namespace App\Services\OpdToken;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class DisplayAuthService
{
    public function isAuthenticated(Request $request, array $display): bool
    {
        $password = (string) ($display['password'] ?? '');

        if ($password === '') {
            return true;
        }

        $fingerprint = $this->fingerprint($display);

        return $request->session()->get($this->sessionKey($display)) === $fingerprint
            || $request->cookie($this->cookieKey($display)) === $fingerprint;
    }

    public function authenticate(Request $request, array $display, string $password): bool
    {
        $expected = (string) ($display['password'] ?? '');

        if ($expected !== '' && ! hash_equals($expected, $password)) {
            return false;
        }

        $fingerprint = $this->fingerprint($display);

        $request->session()->put($this->sessionKey($display), $fingerprint);
        Cookie::queue(cookie()->make($this->cookieKey($display), $fingerprint, 60 * 24 * 7));

        return true;
    }

    public function forget(Request $request, array $display): void
    {
        $request->session()->forget($this->sessionKey($display));
        Cookie::queue(Cookie::forget($this->cookieKey($display)));
    }

    public function fingerprint(array $display): string
    {
        return sha1((string) ($display['screen_slug'] ?? 'global') . '|' . (string) ($display['password'] ?? ''));
    }

    public function sessionKey(array $display): string
    {
        return 'opd-token.display.auth.' . substr(sha1((string) ($display['screen_slug'] ?? 'global')), 0, 12);
    }

    public function cookieKey(array $display): string
    {
        return 'opd_token_display_auth_' . substr(sha1((string) ($display['screen_slug'] ?? 'global')), 0, 12);
    }
}
