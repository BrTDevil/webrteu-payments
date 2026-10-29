<?php

declare(strict_types=1);

use WeBRTeu\Payments\Data\CustomerData;
use WeBRTeu\Payments\Data\ItemData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Exceptions\PaymentException;
use WeBRTeu\Payments\Exceptions\UnsupportedOperationException;

it('validates the payment request', function (string $orderId, int $amount): void {
    new PaymentRequestData($orderId, $amount, 'x', new CustomerData('a@b.ro', 'A', 'B'), 'https://x');
})->with([['', 100], ['CMD', 0], ['CMD', -1]])->throws(InvalidArgumentException::class);

it('exposes item totals and the customer name', function (): void {
    expect((new ItemData('Tricou', 'TR-1', 4999, 2))->total())->toBe(9998)
        ->and((new CustomerData('a@b.ro', 'Ion', 'Popescu'))->fullName())->toBe('Ion Popescu')
        ->and(paymentRequest()->currency)->toBe('RON');
});

it('replaces the status of a webhook keeping the rest', function (): void {
    $webhook = new WebhookData('E1', 'CMD-1', PaymentStatus::Pending, 'P1', 100, ['a' => 1]);

    expect($webhook->withStatus(PaymentStatus::Paid))
        ->status->toBe(PaymentStatus::Paid)
        ->eventId->toBe('E1')
        ->amount->toBe(100)
        ->providerPaymentId->toBe('P1')
        ->and($webhook->withStatus(PaymentStatus::Paid, 200, 'P2'))
        ->amount->toBe(200)
        ->providerPaymentId->toBe('P2');
});

it('describes unsupported operations', function (): void {
    expect(UnsupportedOperationException::for('fake', 'refund'))
        ->toBeInstanceOf(PaymentException::class)
        ->getMessage()->toBe('Procesatorul [fake] nu suportă operația [refund].');
});
