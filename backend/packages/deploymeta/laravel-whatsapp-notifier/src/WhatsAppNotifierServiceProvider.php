<?php

namespace Deploymeta\WhatsAppNotifier;

use Deploymeta\WhatsAppNotifier\Client\MetaCloudWhatsAppClient;
use Illuminate\Support\ServiceProvider;

class WhatsAppNotifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/whatsapp-notifier.php', 'whatsapp-notifier');

        $this->app->singleton(MetaCloudWhatsAppClient::class, function () {
            return new MetaCloudWhatsAppClient();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ((bool) config('whatsapp-notifier.webhook.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        $this->publishes([
            __DIR__ . '/../config/whatsapp-notifier.php' => config_path('whatsapp-notifier.php'),
        ], 'whatsapp-notifier-config');
    }
}
