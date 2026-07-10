<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\ApiAllowedIp;

class VerifyWordpressApi
{
    public function handle(Request $request, Closure $next)
    {
        // Only bypass in automated testing; enforce checks in all other environments (including local)
        if (app()->environment('testing')) {
            return $next($request);
        }

        $allowedIps = trim(env('WP_ALLOWED_IPS', ''));
        $clientIp = $request->ip();
        // Check env allowlist first
        if ($allowedIps !== '') {
            $ips = array_map('trim', explode(',', $allowedIps));
            if (in_array($clientIp, $ips, true)) {
                Log::info('WordPress API access allowed by env allowlist', ['ip' => $clientIp, 'path' => $request->path()]);
                return $next($request);
            }
        }

        // Then check the DB allowed IPs table (recommended)
        try {
            $dbIps = ApiAllowedIp::where('active', true)->pluck('ip')->toArray();
            if (in_array($clientIp, $dbIps, true)) {
                Log::info('WordPress API access allowed by DB allowlist', ['ip' => $clientIp, 'path' => $request->path()]);
                return $next($request);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to check DB allowlist for WordPress API', ['error' => $e->getMessage()]);
        }

        // Check header secret or bearer token
        $secretHeader = $request->header('X-TELEHEALTH-SECRET') ?? $request->bearerToken();

        $expected = env('WP_TELEHEALTH_SECRET');
        // fallback to settings table if available
        try {
            if (! $expected) {
                $expected = Setting::getValue('wordpress_api_setting', 'wordpress_api_secret');
            }
        } catch (\Throwable $e) {
            // ignore if settings not accessible
        }

        if (! $expected || ! $secretHeader) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! hash_equals((string) $expected, (string) $secretHeader)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
