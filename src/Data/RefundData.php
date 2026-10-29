<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

use WeBRTeu\Payments\Enums\PaymentStatus;

final readonly class RefundData
{
    /**
     * @param  int  $amount  în bani
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $amount,
        public PaymentStatus $status,
        public ?string $providerRefundId = null,
        public array $raw = [],
    ) {}
}
