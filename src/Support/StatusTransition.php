<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Support;

use WeBRTeu\Payments\Enums\PaymentStatus;

/**
 * Statusul unei plăți nu coboară niciodată: notificările pot veni în altă ordine decât s-au produs.
 */
final class StatusTransition
{
    public static function allowed(PaymentStatus $from, PaymentStatus $to): bool
    {
        if ($from === $to || $to === PaymentStatus::Unknown) {
            return false;
        }

        return match ($from) {
            PaymentStatus::Pending, PaymentStatus::Unknown => true,
            PaymentStatus::Authorized => in_array($to, [PaymentStatus::Paid, PaymentStatus::Cancelled, PaymentStatus::Failed], true),
            PaymentStatus::Failed => $to === PaymentStatus::Paid,
            PaymentStatus::Paid => in_array($to, [PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded], true),
            PaymentStatus::PartiallyRefunded => $to === PaymentStatus::Refunded,
            PaymentStatus::Refunded, PaymentStatus::Cancelled => false,
        };
    }
}
