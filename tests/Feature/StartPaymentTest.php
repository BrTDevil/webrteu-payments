<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use WeBRTeu\Payments\Contracts\Gateway;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Enums\RedirectType;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Models\Payment;
use WeBRTeu\Payments\Tests\Fakes\FakeGateway;

it('creates the payment row and stores the provider payment id', function (): void {
    Payments::extend('fake', fn (): FakeGateway => new FakeGateway);
    $order = new class extends Model
    {
        protected $table = 'orders';
    };
    $order->id = 9;

    $redirect = Payments::start('fake', paymentRequest(), payable: $order);

    expect($redirect->url)->toBe('https://pay.example/CMD-1')
        ->and(Payment::query()->sole())
        ->gateway->toBe('fake')
        ->order_id->toBe('CMD-1')
        ->amount->toBe(14170)
        ->currency->toBe('RON')
        ->status->toBe(PaymentStatus::Pending)
        ->provider_payment_id->toBe('PP-CMD-1')
        ->refunded_amount->toBe(0)
        ->payable_type->toBe($order::class)
        ->payable_id->toBe(9);
});

it('works without a payable and without a provider payment id', function (): void {
    $gateway = new class extends FakeGateway
    {
        public function createPayment(PaymentRequestData $request): PaymentRedirectData
        {
            return new PaymentRedirectData(RedirectType::Form, 'https://pay.example/form', ['a' => '1']);
        }
    };
    Payments::extend('form', fn (): Gateway => $gateway);

    Payments::start('form', paymentRequest('CMD-2'));

    expect(Payment::query()->sole())
        ->provider_payment_id->toBeNull()
        ->payable_type->toBeNull()
        ->payable->toBeNull()
        ->getTable()->toBe('payments');
});
