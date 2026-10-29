<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

final readonly class CustomerData
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $phone = null,
        public ?string $city = null,
        public ?string $county = null,
        public ?string $address = null,
        public ?string $postalCode = null,
        public string $countryCode = 'RO',
    ) {}

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
