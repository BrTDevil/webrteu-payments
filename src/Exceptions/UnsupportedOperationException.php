<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Exceptions;

class UnsupportedOperationException extends PaymentException
{
    public static function for(string $gateway, string $operation): self
    {
        return new self(sprintf('Procesatorul [%s] nu suportă operația [%s].', $gateway, $operation));
    }
}
