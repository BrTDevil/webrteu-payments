<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Contracts;

use WeBRTeu\Payments\Data\PaymentStatusData;

/**
 * Pre-autorizare + capturare.
 */
interface Capturable
{
    /**
     * @param  int  $amount  în bani
     */
    public function capture(string $providerPaymentId, int $amount): PaymentStatusData;

    public function void(string $providerPaymentId): PaymentStatusData;
}
