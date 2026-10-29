<?php

declare(strict_types=1);

use WeBRTeu\Payments\Support\Money;

it('converts between bani and lei', function (): void {
    expect(Money::toDecimalString(14170))->toBe('141.70')
        ->and(Money::toDecimalString(5))->toBe('0.05')
        ->and(Money::toDecimalString(-250))->toBe('-2.50')
        ->and(Money::toFloat(14170))->toBe(141.7)
        ->and(Money::fromDecimal('141,7'))->toBe(14170)
        ->and(Money::fromDecimal('1.005'))->toBe(101)
        ->and(Money::fromDecimal('1.234,56'))->toBe(123456)
        ->and(Money::fromDecimal('1,234.56'))->toBe(123456)
        ->and(Money::fromDecimal('-2.5'))->toBe(-250)
        ->and(Money::fromDecimal(3))->toBe(300);
});

it('rejects invalid input', function (): void {
    Money::fromDecimal('12 lei');
})->throws(InvalidArgumentException::class);
