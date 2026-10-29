<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Events\PaymentStatusChanged;
use WeBRTeu\Payments\Exceptions\PaymentException;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Jobs\ProcessPaymentWebhook;
use WeBRTeu\Payments\Models\Payment;
use WeBRTeu\Payments\Models\PaymentEvent;
use WeBRTeu\Payments\Tests\Fakes\ConfirmingFakeGateway;
use WeBRTeu\Payments\Tests\Fakes\FakeGateway;

function processWebhook(PaymentStatus $status, ?int $amount = 14170, string $eventId = 'E1', ?string $providerId = null): PaymentEvent
{
    $event = PaymentEvent::query()->create(['gateway' => 'fake', 'event_id' => $eventId, 'signature_valid' => true]);

    Bus::dispatchSync(new ProcessPaymentWebhook('fake', new WebhookData($eventId, 'CMD-1', $status, $providerId, $amount, ['s' => $status->value]), $event->id));

    return $event->refresh();
}

beforeEach(function (): void {
    Event::fake([PaymentStatusChanged::class]);
    Payments::extend('fake', fn (): FakeGateway => new FakeGateway);
});

it('moves Pending to Paid, sets paid_at and emits the event once', function (): void {
    payment();

    $event = processWebhook(PaymentStatus::Paid, providerId: 'P1');
    processWebhook(PaymentStatus::Paid, eventId: 'E2');

    $payment = Payment::query()->sole();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->provider_payment_id)->toBe('P1')
        ->and($payment->raw)->toBe(['s' => 'paid'])
        ->and($event->processed_at)->not->toBeNull()
        ->and($event->payment?->is($payment))->toBeTrue()
        ->and($payment->events()->count())->toBe(2);

    Event::assertDispatchedTimes(PaymentStatusChanged::class, 1);
    Event::assertDispatched(fn (PaymentStatusChanged $e): bool => $e->from === PaymentStatus::Pending && $e->to === PaymentStatus::Paid);
});

it('ignores Paid to Pending', function (): void {
    payment(status: PaymentStatus::Paid);

    processWebhook(PaymentStatus::Pending);

    expect(Payment::query()->sole()->status)->toBe(PaymentStatus::Paid);
    Event::assertNotDispatched(PaymentStatusChanged::class);
});

it('does not mark as Paid when the amount differs', function (): void {
    Log::spy();
    payment();

    $event = processWebhook(PaymentStatus::Paid, 100);

    expect(Payment::query()->sole()->status)->toBe(PaymentStatus::Pending)
        ->and($event->processed_at)->not->toBeNull();
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => $message === 'payments.amount_mismatch');
    Event::assertNotDispatched(PaymentStatusChanged::class);
});

it('uses confirm() when the gateway implements ConfirmsWebhooks', function (): void {
    $gateway = new ConfirmingFakeGateway;
    Payments::extend('fake', fn (): ConfirmingFakeGateway => $gateway);
    payment();

    processWebhook(PaymentStatus::Pending);

    expect($gateway->confirmations)->toBe(1)
        ->and(Payment::query()->sole()->status)->toBe(PaymentStatus::Paid);
});

it('creates the payment when it does not exist', function (): void {
    processWebhook(PaymentStatus::Paid, 5000);

    expect(Payment::query()->sole())
        ->order_id->toBe('CMD-1')
        ->amount->toBe(5000)
        ->status->toBe(PaymentStatus::Paid);
});

it('tracks refunds', function (): void {
    payment(status: PaymentStatus::Paid);

    processWebhook(PaymentStatus::PartiallyRefunded, 4000);
    expect(Payment::query()->sole()->refunded_amount)->toBe(4000);

    processWebhook(PaymentStatus::Refunded, null, 'E2');
    expect(Payment::query()->sole())
        ->refunded_amount->toBe(14170)
        ->status->toBe(PaymentStatus::Refunded);
});

it('keeps the previous refunded amount when a partial refund has no amount', function (): void {
    payment(status: PaymentStatus::Paid);

    processWebhook(PaymentStatus::PartiallyRefunded, null);

    expect(Payment::query()->sole()->refunded_amount)->toBe(0);
});

it('skips events that were already processed', function (): void {
    $payment = payment();
    $event = PaymentEvent::query()->create(['gateway' => 'fake', 'event_id' => 'E1', 'signature_valid' => true, 'processed_at' => now()]);

    Bus::dispatchSync(new ProcessPaymentWebhook('fake', new WebhookData('E1', 'CMD-1', PaymentStatus::Paid), $event->id));

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('records the error and rethrows', function (): void {
    $event = PaymentEvent::query()->create(['gateway' => 'missing', 'event_id' => 'E1', 'signature_valid' => true]);

    expect(fn () => Bus::dispatchSync(new ProcessPaymentWebhook('missing', new WebhookData('E1', 'CMD-1', PaymentStatus::Paid), $event->id)))
        ->toThrow(PaymentException::class);

    expect($event->refresh()->error)->toContain('missing')
        ->and($event->processed_at)->toBeNull();
});

it('uses the configured queue', function (?string $queue, ?string $expected): void {
    Config::set('payments.queue', $queue);

    expect((new ProcessPaymentWebhook('fake', new WebhookData('E1', 'CMD-1', PaymentStatus::Paid), 1))->queue)->toBe($expected);
})->with([['payments', 'payments'], [null, null], ['', null]]);
