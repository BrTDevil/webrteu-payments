<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

use WeBRTeu\Payments\Enums\PaymentStatus;

final readonly class PaymentStatusData
{
    /**
     * @param  int|null  $amount  în bani
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $orderId,
        public PaymentStatus $status,
        public ?string $providerPaymentId = null,
        public ?int $amount = null,
        public array $raw = [],
    ) {}
}
