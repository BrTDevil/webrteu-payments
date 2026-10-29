<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Support;

use InvalidArgumentException;

/**
 * Conversii între bani (int) și lei (zecimal), fără aritmetică pe float.
 */
final class Money
{
    public static function toDecimalString(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absolute = abs($amount);

        return sprintf('%s%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }

    public static function toFloat(int $amount): float
    {
        return (float) self::toDecimalString($amount);
    }

    /**
     * Acceptă „141.70”, „141,7”, „1.234,56”, „1,234.56”, „-2.5” sau un întreg (lei).
     * A treia zecimală se rotunjește „half up”.
     */
    public static function fromDecimal(string|int $value): int
    {
        if (is_int($value)) {
            return $value * 100;
        }

        $normalized = self::normalize($value);

        if (preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $normalized, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Suma [%s] nu este o valoare zecimală validă.', $value));
        }

        $decimals = str_pad($matches[3] ?? '', 3, '0');
        $amount = ((int) $matches[2]) * 100 + (int) substr($decimals, 0, 2);

        if ((int) $decimals[2] >= 5) {
            $amount++;
        }

        return $matches[1] === '-' ? -$amount : $amount;
    }

    private static function normalize(string $value): string
    {
        $value = str_replace([' ', "\u{00A0}"], '', trim($value));
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';

            return str_replace([$thousandsSeparator, ','], ['', '.'], $value);
        }

        return str_replace(',', '.', $value);
    }
}
