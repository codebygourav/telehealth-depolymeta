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

        return $request->session()->get($this->sessionKey()) === $fingerprint
            || $request->cookie($this->cookieKey()) === $fingerprint;
    }

    public function authenticate(Request $request, array $display, string $password): bool
    {
        $expected = (string) ($display['password'] ?? '');

        if ($expected !== '' && ! hash_equals($expected, $password)) {
            return false;
        }

        $fingerprint = $this->fingerprint($display);

        $request->session()->put($this->sessionKey(), $fingerprint);
        Cookie::queue(cookie()->make($this->cookieKey(), $fingerprint, 60 * 24 * 7));

        return true;
    }

    public function forget(Request $request): void
    {
        $request->session()->forget($this->sessionKey());
        Cookie::queue(Cookie::forget($this->cookieKey()));
    }

    public function fingerprint(array $display): string
    {
        return sha1((string) ($display['password'] ?? ''));
    }

    public function sessionKey(): string
    {
        return 'opd-token.display.auth';
    }

    public function cookieKey(): string
    {
        return 'opd_token_display_auth';
    }
}
