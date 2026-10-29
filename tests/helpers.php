<?php

declare(strict_types=1);

use WeBRTeu\Payments\Data\CustomerData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Models\Payment;

function paymentRequest(string $orderId = 'CMD-1', int $amount = 14170): PaymentRequestData
{
    return new PaymentRequestData(
        orderId: $orderId,
        amount: $amount,
        description: 'Comanda '.$orderId,
        customer: new CustomerData('ion@example.com', 'Ion', 'Popescu'),
        returnUrl: 'https://shop.example/return',
    );
}

function payment(string $orderId = 'CMD-1', PaymentStatus $status = PaymentStatus::Pending, int $amount = 14170, string $gateway = 'fake'): Payment
{
    return Payment::query()->create([
        'gateway' => $gateway,
        'order_id' => $orderId,
        'amount' => $amount,
        'status' => $status,
    ]);
}
