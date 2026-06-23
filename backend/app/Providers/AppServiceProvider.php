<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Listeners\LogPushNotificationStatus;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Storage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // ── HTTPS Detection ──────────────────────────────────────────────────
        // Must run before ANY URL generation so Livewire's signed temporary
        // upload URLs use the correct scheme (critical for Hostinger/reverse-proxy).
        $forwardedProto = strtolower((string) request()->header('x-forwarded-proto'));
        $serverHttps    = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $xForwardedSsl  = strtolower((string) request()->header('x-forwarded-ssl'));

        $isHttps = request()->isSecure()
            || str_contains($forwardedProto, 'https')
            || $serverHttps === 'on'
            || $xForwardedSsl === 'on'
            || str_starts_with((string) config('app.url'), 'https://');

        if ($isHttps) {
            URL::forceScheme('https');
        }

        // ── Fix Public Disk URL ───────────────────────────────────────────────
        // Always correct the public disk URL so storage links work on live.
        // Do NOT guard with runningInConsole() — queue workers need this too.
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($isHttps) {
            $appUrl = preg_replace('#^http://#', 'https://', $appUrl);
        }

        config([
            'filesystems.disks.public.url' => $appUrl . '/storage',
        ]);

        Blade::component('ui.page-header', 'ui-page-header');
        Blade::component('ui.page-body', 'ui-page-body');
        RateLimiter::for('verify-payment', function (Request $request) {
            return Limit::perMinute(1)->by(
                $request->input('appointment_id')
                    ?? $request->ip()
            );
        });

        Event::subscribe(LogPushNotificationStatus::class);
        
        Relation::morphMap([
            'Doctor' => Doctor::class,
            'Patient' => Patient::class,
        ]);

        Table::configureUsing(function (Table $table): void {
            $table
                ->modifyQueryUsing(function ($query) {
                    $model = $query->getModel();

                    if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                        return $query;
                    }

                    return $query->whereNull($model->getQualifiedDeletedAtColumn());
                })
                ->filtersLayout(FiltersLayout::AboveContent)
                ->filtersFormColumns(6)
                ->deferFilters(false)
                ->extraAttributes([
                    'class' => 'custom-pagination custom-resource-table',
                ], merge: true);
        });
    }
}
