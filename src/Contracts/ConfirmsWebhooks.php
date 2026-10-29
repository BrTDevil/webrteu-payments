<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Contracts;

use WeBRTeu\Payments\Data\WebhookData;

/**
 * Pentru procesatorii la care notificarea e doar un semnal (Revolut, BT iPay):
 * statusul real se cere prin API înainte de procesare.
 */
interface ConfirmsWebhooks
{
    public function confirm(WebhookData $webhook): WebhookData;
}
