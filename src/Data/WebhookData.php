<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

use WeBRTeu\Payments\Enums\PaymentStatus;

final readonly class WebhookData
{
    /**
     * @param  string  $eventId  unic per notificare, pentru idempotență
     * @param  int|null  $amount  în bani
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $eventId,
        public string $orderId,
        public PaymentStatus $status,
        public ?string $providerPaymentId = null,
        public ?int $amount = null,
        public array $raw = [],
    ) {}

    public function withStatus(PaymentStatus $status, ?int $amount = null, ?string $providerPaymentId = null): self
    {
        return new self(
            $this->eventId,
            $this->orderId,
            $status,
            $providerPaymentId ?? $this->providerPaymentId,
            $amount ?? $this->amount,
            $this->raw,
        );
    }
}
