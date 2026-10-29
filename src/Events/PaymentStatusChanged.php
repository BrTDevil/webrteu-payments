<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Events;

use Illuminate\Queue\SerializesModels;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Models\Payment;

final class PaymentStatusChanged
{
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public PaymentStatus $from,
        public PaymentStatus $to,
    ) {}
}
