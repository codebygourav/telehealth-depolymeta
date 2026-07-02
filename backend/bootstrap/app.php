<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger / reverse-proxy: trust forwarded headers so Livewire signed upload URLs use HTTPS.
        $middleware->trustProxies(at: '*');
        // Redirect unauthenticated users to login for web, return JSON for API
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Don't redirect, let exception handler handle it
            }
            return route('filament.admin.auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for unauthenticated API requests
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid token.',
                ], 401);
            }
        });

        // Convert 403 Forbidden to 404 Not Found for admin routes
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 403 && $request->is('admin/*')) {
                return response()->view('errors.404', [
                    'exception' => $e,
                    'message' => 'You are not authorized for this page',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('admin/*')) {
                return response()->view('errors.404', [
                    'exception' => $e,
                    'message' => 'You are not authorized for this page',
                ], 404);
            }
        });
    })->create();