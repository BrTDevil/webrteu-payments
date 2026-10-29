<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Tests\Fakes;

use WeBRTeu\Payments\Contracts\ConfirmsWebhooks;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\PaymentStatus;

/**
 * Ca Revolut / BT iPay: notificarea e doar un semnal, statusul real vine din confirm().
 */
class ConfirmingFakeGateway extends FakeGateway implements ConfirmsWebhooks
{
    public int $confirmations = 0;

    public PaymentStatus $confirmedStatus = PaymentStatus::Paid;

    public function confirm(WebhookData $webhook): WebhookData
    {
        $this->confirmations++;

        return $webhook->withStatus($this->confirmedStatus);
    }
}
