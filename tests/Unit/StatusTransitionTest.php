<?php

declare(strict_types=1);

use WeBRTeu\Payments\Enums\PaymentStatus as S;
use WeBRTeu\Payments\Support\StatusTransition;

/**
 * Tabelul complet: [din, în, permis].
 *
 * @return list<array{S, S, bool}>
 */
function transitions(): array
{
    $allowed = [
        'pending' => ['authorized', 'paid', 'failed', 'cancelled', 'partially_refunded', 'refunded'],
        'unknown' => ['pending', 'authorized', 'paid', 'failed', 'cancelled', 'partially_refunded', 'refunded'],
        'authorized' => ['paid', 'cancelled', 'failed'],
        'failed' => ['paid'],
        'paid' => ['partially_refunded', 'refunded'],
        'partially_refunded' => ['refunded'],
        'refunded' => [],
        'cancelled' => [],
    ];

    $rows = [];

    foreach (S::cases() as $from) {
        foreach (S::cases() as $to) {
            $rows[] = [$from, $to, in_array($to->value, $allowed[$from->value], true)];
        }
    }

    return $rows;
}

it('allows only forward transitions', function (S $from, S $to, bool $allowed): void {
    expect(StatusTransition::allowed($from, $to))->toBe($allowed);
})->with(transitions());

it('describes the final and successful statuses', function (): void {
    expect(array_values(array_filter(S::cases(), fn (S $s): bool => $s->isFinal())))->toBe([S::Cancelled, S::Refunded])
        ->and(array_values(array_filter(S::cases(), fn (S $s): bool => $s->isSuccessful())))->toBe([S::Paid, S::PartiallyRefunded, S::Refunded]);
});

it('has a Romanian label for every status', function (S $status): void {
    expect($status->label())->not->toBeEmpty();
})->with(S::cases());
