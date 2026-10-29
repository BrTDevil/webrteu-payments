<?php

declare(strict_types=1);

use WeBRTeu\Payments\Support\Redactor;

it('hides secrets recursively, whatever the key casing', function (): void {
    expect(Redactor::redact([
        'username' => 'magazin',
        'password' => 'p1',
        'Password' => 'p2',
        'apiKey' => 'k',
        'api_key' => 'k2',
        'Authorization' => 'Bearer x',
        'nested' => ['token' => 't', 'client_secret' => 's', 'amount' => 100],
        'list' => [['secret' => 'x']],
        'pin' => '1234',
    ], ['pin']))->toBe([
        'username' => 'magazin',
        'password' => '***',
        'Password' => '***',
        'apiKey' => '***',
        'api_key' => '***',
        'Authorization' => '***',
        'nested' => ['token' => '***', 'client_secret' => '***', 'amount' => 100],
        'list' => [['secret' => '***']],
        'pin' => '***',
    ]);
});
