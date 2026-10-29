<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

final readonly class ItemData
{
    /**
     * @param  int  $unitPrice  în bani, cu TVA
     * @param  int  $vatRate  procent (ex. 19)
     */
    public function __construct(
        public string $name,
        public string $sku,
        public int $unitPrice,
        public int $quantity = 1,
        public int $vatRate = 19,
    ) {}

    public function total(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
