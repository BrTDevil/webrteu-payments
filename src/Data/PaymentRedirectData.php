<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Data;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use WeBRTeu\Payments\Enums\RedirectType;

final readonly class PaymentRedirectData implements Responsable
{
    /**
     * @param  array<string, string|int>  $fields  câmpurile ascunse pentru RedirectType::Form
     */
    public function __construct(
        public RedirectType $type,
        public string $url,
        public array $fields = [],
        public ?string $providerPaymentId = null,
    ) {}

    /**
     * Redirect → GET pe URL-ul procesatorului; Form → pagină cu formular POST trimis automat.
     */
    public function toResponse($request = null): RedirectResponse|Response
    {
        if ($this->type === RedirectType::Redirect) {
            return new RedirectResponse($this->url);
        }

        return new Response(View::file(View::getFinder()->find('payments::redirect'), ['redirect' => $this])->render());
    }
}
