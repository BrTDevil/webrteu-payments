<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Payments\Exceptions\PaymentException;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\PaymentManager;
use WeBRTeu\Payments\PaymentsServiceProvider;
use WeBRTeu\Payments\Tests\Fakes\FakeGateway;

it('registers, caches and lists gateways', function (): void {
    $created = 0;
    Payments::extend('fake', function () use (&$created): FakeGateway {
        $created++;

        return new FakeGateway;
    })->extend('other', fn (): FakeGateway => new FakeGateway('other'));

    expect(Payments::gateway('fake'))->toBe(Payments::gateway('fake'))
        ->and($created)->toBe(1)
        ->and(Payments::available())->toBe(['fake', 'other'])
        ->and(Payments::has('other'))->toBeTrue()
        ->and(Payments::has('netopia'))->toBeFalse();

    Payments::extend('fake', fn (): FakeGateway => new FakeGateway('again'));
    expect(Payments::gateway('fake')->name())->toBe('again');
});

it('uses the default gateway from config', function (): void {
    Config::set('payments.default', 'other');
    Payments::extend('other', fn (): FakeGateway => new FakeGateway('other'));

    expect(Payments::gateway()->name())->toBe('other')
        ->and(Payments::getDefaultGateway())->toBe('other');
});

it('throws a clear exception for a missing gateway', function (): void {
    Payments::gateway('netopia');
})->throws(PaymentException::class, 'Instalează webrteu/netopia');

it('throws when the creator does not return a gateway', function (): void {
    Payments::extend('broken', fn (): stdClass => new stdClass);

    Payments::gateway('broken');
})->throws(PaymentException::class, 'trebuie să implementeze contractul');

it('binds the manager, config and publishable files', function (): void {
    expect(app(PaymentManager::class))->toBe(app('payments'))
        ->and(config('payments.reconcile.older_than_minutes'))->toBe(15)
        ->and(ServiceProvider::pathsToPublish(PaymentsServiceProvider::class, 'payments-config'))->toHaveCount(1)
        ->and(ServiceProvider::pathsToPublish(PaymentsServiceProvider::class, 'payments-views'))->toHaveCount(1)
        ->and(array_values(ServiceProvider::pathsToPublish(PaymentsServiceProvider::class, 'payments-migrations'))[0])->toEndWith('_create_payments_tables.php');
});

it('exposes the fake optional capabilities', function (): void {
    $gateway = new FakeGateway;

    expect($gateway->refund('P1', 100)->providerRefundId)->toBe('R-P1')
        ->and($gateway->capture('P1', 100)->status->value)->toBe('paid')
        ->and($gateway->void('P1')->status->value)->toBe('cancelled');
});
