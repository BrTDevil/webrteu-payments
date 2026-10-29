<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Data\PaymentStatusData;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Exceptions\InvalidSignatureException;

interface Gateway
{
    public function name(): string;

    /**
     * Pornește plata. Rezultatul spune unde trimiți clientul.
     */
    public function createPayment(PaymentRequestData $request): PaymentRedirectData;

    /**
     * Verifică autenticitatea și parsează notificarea, pe corpul brut al cererii.
     *
     * @throws InvalidSignatureException
     */
    public function parseWebhook(Request $request): WebhookData;

    /**
     * Răspunsul HTTP pe care îl așteaptă procesatorul. $webhook e null când semnătura a fost invalidă.
     */
    public function webhookResponse(?WebhookData $webhook, bool $accepted): Response;

    /**
     * Interogare directă, pentru reconciliere și pagina de return.
     */
    public function status(string $providerPaymentId): PaymentStatusData;
}
