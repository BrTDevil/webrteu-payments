<?php

declare(strict_types=1);

use WeBRTeu\Payments\Support\LengthPrefixedHmac;

const HMAC_KEY = '00112233445566778899AABBCCDDEEFF';

it('builds the signed string with length prefixes and a dash for empty values', function (): void {
    expect(LengthPrefixedHmac::data(['141.70', 'RON', '', 'CMD-1', null, 0]))->toBe('6141.703RON-5CMD-1-10');
});

it('counts diacritics as bytes', function (): void {
    expect(LengthPrefixedHmac::data(['Brașov']))->toBe('7Brașov');
});

it('signs with MD5 and SHA1 using the binary key', function (string $algo): void {
    $values = ['141.70', 'RON', 'CMD-1', 'Comandă', ''];
    $expected = strtoupper(hash_hmac($algo, '6141.703RON5CMD-18Comandă-', (string) hex2bin(HMAC_KEY)));

    expect(LengthPrefixedHmac::sign($values, HMAC_KEY, $algo))->toBe($expected)
        ->and(strlen($expected))->toBe($algo === 'md5' ? 32 : 40);
})->with(['md5', 'sha1']);

it('verifies signatures case-insensitively', function (): void {
    $values = ['100.00', 'RON'];
    $signature = LengthPrefixedHmac::sign($values, HMAC_KEY, 'md5');

    expect(LengthPrefixedHmac::verify($values, HMAC_KEY, 'md5', strtolower($signature)))->toBeTrue()
        ->and(LengthPrefixedHmac::verify($values, HMAC_KEY, 'md5', $signature))->toBeTrue()
        ->and(LengthPrefixedHmac::verify(['100.01', 'RON'], HMAC_KEY, 'md5', $signature))->toBeFalse();
});

it('rejects keys that are not hexadecimal', function (string $key): void {
    LengthPrefixedHmac::sign(['1'], $key, 'md5');
})->with(['xyz1', 'ABC'])->throws(InvalidArgumentException::class);
