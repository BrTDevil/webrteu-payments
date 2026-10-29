<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Jobs\ProcessPaymentWebhook;
use WeBRTeu\Payments\Models\PaymentEvent;
use WeBRTeu\Payments\Tests\Fakes\FakeGateway;

beforeEach(function (): void {
    Queue::fake();
    Payments::extend('fake', fn (): FakeGateway => new FakeGateway);
});

/**
 * @param  array<string, string>  $headers
 */
function postWebhook(string $body, ?string $signature = null, string $gateway = 'fake', array $headers = []): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FAKE_SIGNATURE' => $signature ?? FakeGateway::sign($body)];

    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return test()->call('POST', '/webhooks/payments/'.$gateway, [], [], [], $server, $body);
}

function webhookBody(string $eventId = 'E1', string $status = 'paid', int $amount = 14170): string
{
    return (string) json_encode(['event_id' => $eventId, 'order_id' => 'CMD-1', 'status' => $status, 'amount' => $amount]);
}

it('accepts a valid signature, stores the event and queues the job', function (): void {
    postWebhook(webhookBody(), headers: ['Authorization' => 'Bearer secret', 'Cookie' => 'a=b', 'X-Trace' => 't1'])
        ->assertOk()
        ->assertExactJson(['accepted' => true, 'event' => 'E1']);

    Queue::assertPushed(ProcessPaymentWebhook::class, fn (ProcessPaymentWebhook $job): bool => $job->gateway === 'fake'
        && $job->webhook->eventId === 'E1'
        && $job->webhook->status === PaymentStatus::Paid);

    $event = PaymentEvent::query()->sole();

    expect($event->signature_valid)->toBeTrue()
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->raw_body)->toBe(webhookBody())
        ->and($event->headers)->toHaveKey('x-trace')
        ->not->toHaveKey('authorization')
        ->not->toHaveKey('cookie');
});

it('rejects an invalid signature with the gateway response and stores the attempt', function (): void {
    Log::spy();

    postWebhook(webhookBody(), 'wrong')
        ->assertStatus(400)
        ->assertExactJson(['accepted' => false, 'event' => null]);

    Queue::assertNothingPushed();
    $event = PaymentEvent::query()->sole();

    expect($event->signature_valid)->toBeFalse()
        ->and($event->event_id)->toStartWith('invalid-')
        ->and($event->processed_at)->toBeNull();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => $message === 'payments.invalid_signature');
});

it('does not queue a second job for a duplicate notification', function (): void {
    postWebhook(webhookBody())->assertOk();
    postWebhook(webhookBody())->assertOk()->assertJson(['accepted' => true]);

    Queue::assertPushed(ProcessPaymentWebhook::class, 1);
    expect(PaymentEvent::query()->count())->toBe(1);
});

it('returns 404 for an unknown gateway', function (): void {
    postWebhook(webhookBody(), gateway: 'nope')->assertNotFound();
});

it('does not require a CSRF token', function (): void {
    expect(app('router')->getRoutes()->getByName('payments.webhook')?->gatherMiddleware())->not->toContain('web');

    postWebhook(webhookBody())->assertOk();
});

it('does not store the raw body when disabled', function (): void {
    Config::set('payments.store_raw_body', false);

    postWebhook(webhookBody())->assertOk();

    expect(PaymentEvent::query()->sole()->raw_body)->toBeNull();
});
