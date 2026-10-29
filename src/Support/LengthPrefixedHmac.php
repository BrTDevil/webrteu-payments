<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Support;

use InvalidArgumentException;

/**
 * Semnătura HMAC cu valori prefixate de lungime, folosită de EuPlatesc (MD5) și LibraPay (SHA1):
 * fiecare valoare devine `strlen(valoare).valoare`, iar o valoare goală devine „-”.
 */
final class LengthPrefixedHmac
{
    /**
     * @param  array<array-key, string|int|float|null>  $values
     */
    public static function sign(array $values, string $hexKey, string $algo): string
    {
        return strtoupper(hash_hmac($algo, self::data($values), self::key($hexKey)));
    }

    /**
     * @param  array<array-key, string|int|float|null>  $values
     */
    public static function verify(array $values, string $hexKey, string $algo, string $received): bool
    {
        return hash_equals(self::sign($values, $hexKey, $algo), strtoupper($received));
    }

    /**
     * Șirul care se semnează, util pentru depanare (nu conține cheia).
     *
     * @param  array<array-key, string|int|float|null>  $values
     */
    public static function data(array $values): string
    {
        $data = '';

        foreach ($values as $value) {
            $value = (string) $value;
            $data .= $value === '' ? '-' : strlen($value).$value;
        }

        return $data;
    }

    private static function key(string $hexKey): string
    {
        $key = strlen($hexKey) % 2 === 0 && ctype_xdigit($hexKey) ? hex2bin($hexKey) : false;

        if ($key === false) {
            throw new InvalidArgumentException('Cheia de semnare trebuie să fie un șir hexazecimal valid.');
        }

        return $key;
    }
}
