<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use WeBRTeu\Payments\Exceptions\InvalidSignatureException;
use WeBRTeu\Payments\Jobs\ProcessPaymentWebhook;
use WeBRTeu\Payments\Models\PaymentEvent;
use WeBRTeu\Payments\PaymentManager;

class WebhookController
{
    /**
     * Header-e care nu se salvează niciodată.
     */
    private const HIDDEN_HEADERS = ['authorization', 'cookie', 'php-auth-pw', 'php-auth-user'];

    public function __invoke(Request $request, string $gateway, PaymentManager $payments): Response
    {
        if (! $payments->has($gateway)) {
            throw new NotFoundHttpException;
        }

        $driver = $payments->gateway($gateway);

        try {
            $webhook = $driver->parseWebhook($request);
        } catch (InvalidSignatureException $exception) {
            Log::warning('payments.invalid_signature', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            PaymentEvent::query()->create([
                'gateway' => $gateway,
                'event_id' => 'invalid-'.Str::uuid()->toString(),
                'signature_valid' => false,
                'raw_body' => $this->rawBody($request),
                'headers' => $this->headers($request),
            ]);

            return $driver->webhookResponse(null, false);
        }

        $event = PaymentEvent::query()->firstOrCreate([
            'gateway' => $gateway,
            'event_id' => $webhook->eventId,
        ], [
            'status' => $webhook->status,
            'signature_valid' => true,
            'raw_body' => $this->rawBody($request),
            'headers' => $this->headers($request),
        ]);

        if ($event->wasRecentlyCreated) {
            Bus::dispatch(new ProcessPaymentWebhook($gateway, $webhook, $event->id));
        }

        return $driver->webhookResponse($webhook, true);
    }

    private function rawBody(Request $request): ?string
    {
        return Config::boolean('payments.store_raw_body', true) ? $request->getContent() : null;
    }

    /**
     * @return array<string, list<string|null>>
     */
    private function headers(Request $request): array
    {
        return array_diff_key($request->headers->all(), array_flip(self::HIDDEN_HEADERS));
    }
}
