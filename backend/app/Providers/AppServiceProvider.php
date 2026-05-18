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
        URL::forceScheme('https');
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
    }
}
