<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Exceptions;

use RuntimeException;
use WeBRTeu\Payments\Contracts\Gateway;

class PaymentException extends RuntimeException
{
    public static function gatewayNotRegistered(string $gateway): self
    {
        return new self(sprintf(
            'Procesatorul de plăți [%s] nu este înregistrat. Instalează webrteu/%s sau înregistrează-l cu Payments::extend().',
            $gateway,
            $gateway,
        ));
    }

    public static function invalidGateway(string $gateway): self
    {
        return new self(sprintf('Procesatorul [%s] trebuie să implementeze contractul %s.', $gateway, Gateway::class));
    }
}
