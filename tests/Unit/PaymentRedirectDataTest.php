<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Enums\RedirectType;

it('turns a redirect into a RedirectResponse', function (): void {
    $response = (new PaymentRedirectData(RedirectType::Redirect, 'https://pay.example/abc'))->toResponse();

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('https://pay.example/abc');
});

it('turns a form into an auto-submitting page with escaped fields', function (): void {
    $redirect = new PaymentRedirectData(RedirectType::Form, 'https://pay.example/form?a=1&b=2', [
        'amount' => '141.70',
        'order' => 'CMD-1',
        'evil' => '"><script>alert(1)</script>',
        'count' => 3,
    ]);

    $response = $redirect->toResponse(request());
    $html = (string) $response->getContent();

    expect($response)->toBeInstanceOf(Response::class)
        ->and($html)->toContain('action="https://pay.example/form?a=1&amp;b=2"')
        ->toContain('name="amount" value="141.70"')
        ->toContain('name="order" value="CMD-1"')
        ->toContain('name="count" value="3"')
        ->toContain('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"')
        ->not->toContain('"><script>alert(1)</script>')
        ->toContain('Continuă spre plată')
        ->toContain('.submit()');
});
