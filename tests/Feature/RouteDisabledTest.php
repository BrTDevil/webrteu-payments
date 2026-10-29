<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use WeBRTeu\Payments\PaymentsServiceProvider;

it('does not register the webhook route when disabled', function (): void {
    Route::setRoutes(new RouteCollection);
    Config::set('payments.route.enabled', false);

    (new PaymentsServiceProvider($this->app))->boot();

    expect(Route::getRoutes()->getByName('payments.webhook'))->toBeNull();
});
