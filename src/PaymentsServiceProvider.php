<?php

declare(strict_types=1);

namespace WeBRTeu\Payments;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Payments\Commands\ReconcilePaymentsCommand;

class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payments.php', 'payments');

        $this->app->singleton(PaymentManager::class, fn (Container $app): PaymentManager => new PaymentManager($app));
        $this->app->alias(PaymentManager::class, 'payments');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payments');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'payments');

        if (Config::boolean('payments.route.enabled', true)) {
            Route::prefix(Config::string('payments.route.prefix', 'webhooks/payments'))
                ->middleware(Config::array('payments.route.middleware', []))
                ->group(__DIR__.'/../routes/webhooks.php');
        }

        $this->publishes([
            __DIR__.'/../config/payments.php' => $this->app->configPath('payments.php'),
        ], 'payments-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_payments_tables.php.stub' => $this->app->databasePath('migrations/'.date('Y_m_d_His').'_create_payments_tables.php'),
        ], 'payments-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/payments'),
        ], 'payments-views');

        $this->commands([ReconcilePaymentsCommand::class]);
    }
}
