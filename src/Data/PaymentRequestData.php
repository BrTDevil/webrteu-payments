<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

use InvalidArgumentException;

final readonly class PaymentRequestData
{
    /**
     * @param  string  $orderId  unic per încercare de plată
     * @param  int  $amount  în bani
     * @param  list<ItemData>  $items
     */
    public function __construct(
        public string $orderId,
        public int $amount,
        public string $description,
        public CustomerData $customer,
        public string $returnUrl,
        public string $currency = 'RON',
        public array $items = [],
        public ?string $ip = null,
        public string $locale = 'ro',
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Suma plății trebuie să fie mai mare decât 0.');
        }

        if ($orderId === '') {
            throw new InvalidArgumentException('Identificatorul comenzii este obligatoriu.');
        }
    }
}
