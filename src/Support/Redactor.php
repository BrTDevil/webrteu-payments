<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Support;

/**
 * Ascunde secretele dintr-un payload înainte de orice Log::.
 */
final class Redactor
{
    /**
     * Chei comparate fără diferență între majuscule/minuscule și fără „_” / „-”.
     */
    private const KEYS = ['password', 'secret', 'apikey', 'token', 'authorization', 'accesstoken', 'clientsecret', 'signature', 'key'];

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  list<string>  $extraKeys
     * @return array<array-key, mixed>
     */
    public static function redact(array $payload, array $extraKeys = []): array
    {
        $keys = array_merge(self::KEYS, array_map(self::normalize(...), $extraKeys));

        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(self::normalize($key), $keys, true)) {
                $payload[$key] = '***';
            } elseif (is_array($value)) {
                $payload[$key] = self::redact($value, $extraKeys);
            }
        }

        return $payload;
    }

    private static function normalize(string $key): string
    {
        return strtolower(str_replace(['_', '-'], '', $key));
    }
}
