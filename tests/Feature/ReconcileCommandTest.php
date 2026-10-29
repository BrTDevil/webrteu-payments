<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Payments\Data\PaymentStatusData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Events\PaymentStatusChanged;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Models\Payment;
use WeBRTeu\Payments\Tests\Fakes\FakeGateway;

function aged(Payment $payment, int $minutes): Payment
{
    $payment->forceFill(['created_at' => now()->subMinutes($minutes)])->save();

    return $payment;
}

beforeEach(function (): void {
    Event::fake([PaymentStatusChanged::class]);
    $this->gateway = new FakeGateway;
    Payments::extend('fake', fn (): FakeGateway => $this->gateway);
});

it('calls status() for old pending payments and applies the transition', function (): void {
    $payment = aged(payment('CMD-1'), 20);
    $payment->forceFill(['provider_payment_id' => 'P1'])->save();
    aged(payment('CMD-2', PaymentStatus::Authorized), 30);
    $this->gateway->statuses['P1'] = new PaymentStatusData('CMD-1', PaymentStatus::Paid, 'P1', 14170);

    $this->artisan('payments:reconcile')
        ->expectsOutput('Verificate: 2, schimbate: 1, erori: 0.')
        ->assertSuccessful();

    expect($this->gateway->statusCalls)->toBe(['P1', 'CMD-2'])
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Paid);
    Event::assertDispatchedTimes(PaymentStatusChanged::class, 1);
});

it('skips recent, too old and final payments', function (): void {
    aged(payment('RECENT'), 5);
    aged(payment('OLD'), 49 * 60);
    aged(payment('PAID', PaymentStatus::Paid), 20);

    $this->artisan('payments:reconcile')->expectsOutput('Verificate: 0, schimbate: 0, erori: 0.');

    expect($this->gateway->statusCalls)->toBe([]);
});

it('honours --older-than and --gateway', function (): void {
    aged(payment('CMD-1'), 5);
    aged(payment('CMD-2', gateway: 'other'), 5);

    $this->artisan('payments:reconcile', ['--older-than' => 1, '--gateway' => 'fake'])
        ->expectsOutput('Verificate: 1, schimbate: 0, erori: 0.');

    expect($this->gateway->statusCalls)->toBe(['CMD-1']);
});

it('logs failures and unknown gateways without stopping', function (): void {
    Log::spy();
    aged(payment('CMD-1'), 20);
    aged(payment('CMD-2', gateway: 'missing'), 20);
    aged(payment('CMD-3'), 20);
    $this->gateway->statuses['CMD-1'] = new RuntimeException('API down');
    $this->gateway->statuses['CMD-3'] = new PaymentStatusData('CMD-3', PaymentStatus::Failed);

    $this->artisan('payments:reconcile')
        ->expectsOutputToContain('Procesatorul [missing] nu este înregistrat')
        ->expectsOutput('Verificate: 2, schimbate: 1, erori: 2.');

    Log::shouldHaveReceived('error')->once()->with('payments.reconcile_failed', [
        'gateway' => 'fake',
        'order_id' => 'CMD-1',
        'error' => 'API down',
    ]);
});
