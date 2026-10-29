<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Contracts;

use WeBRTeu\Payments\Data\RefundData;

interface Refundable
{
    /**
     * @param  int  $amount  în bani
     */
    public function refund(string $providerPaymentId, int $amount): RefundData;
}
