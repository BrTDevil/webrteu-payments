<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Unknown = 'unknown';

    public function isFinal(): bool
    {
        return in_array($this, [self::Refunded, self::Cancelled], true);
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded, self::Refunded], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'În așteptare',
            self::Authorized => 'Autorizată',
            self::Paid => 'Plătită',
            self::Failed => 'Eșuată',
            self::Cancelled => 'Anulată',
            self::PartiallyRefunded => 'Rambursată parțial',
            self::Refunded => 'Rambursată',
            self::Unknown => 'Status necunoscut',
        };
    }
}
