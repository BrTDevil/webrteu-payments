<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Tests\Fakes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WeBRTeu\Payments\Contracts\Capturable;
use WeBRTeu\Payments\Contracts\Gateway;
use WeBRTeu\Payments\Contracts\Refundable;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Data\PaymentStatusData;
use WeBRTeu\Payments\Data\RefundData;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Enums\RedirectType;
use WeBRTeu\Payments\Exceptions\InvalidSignatureException;

/**
 * Semnătura: header-ul X-Fake-Signature egal cu hash_hmac('sha256', body, 'secret').
 */
class FakeGateway implements Capturable, Gateway, Refundable
{
    /**
     * @var array<string, PaymentStatusData|\Throwable>
     */
    public array $statuses = [];

    /**
     * @var list<string>
     */
    public array $statusCalls = [];

    public function __construct(private readonly string $name = 'fake') {}

    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, 'secret');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function createPayment(PaymentRequestData $request): PaymentRedirectData
    {
        return new PaymentRedirectData(RedirectType::Redirect, 'https://pay.example/'.$request->orderId, [], 'PP-'.$request->orderId);
    }

    public function parseWebhook(Request $request): WebhookData
    {
        $body = $request->getContent();

        if (! hash_equals(self::sign($body), (string) $request->header('X-Fake-Signature'))) {
            throw new InvalidSignatureException('Semnătură invalidă.');
        }

        /** @var array{event_id: string, order_id: string, status: string, amount?: int, provider_payment_id?: string} $data */
        $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

        return new WebhookData(
            eventId: $data['event_id'],
            orderId: $data['order_id'],
            status: PaymentStatus::from($data['status']),
            providerPaymentId: $data['provider_payment_id'] ?? null,
            amount: $data['amount'] ?? null,
            raw: $data,
        );
    }

    public function webhookResponse(?WebhookData $webhook, bool $accepted): Response
    {
        return new JsonResponse(['accepted' => $accepted, 'event' => $webhook?->eventId], $accepted ? 200 : 400);
    }

    public function status(string $providerPaymentId): PaymentStatusData
    {
        $this->statusCalls[] = $providerPaymentId;
        $status = $this->statuses[$providerPaymentId] ?? new PaymentStatusData($providerPaymentId, PaymentStatus::Pending);

        if ($status instanceof \Throwable) {
            throw $status;
        }

        return $status;
    }

    public function refund(string $providerPaymentId, int $amount): RefundData
    {
        return new RefundData($amount, PaymentStatus::Refunded, 'R-'.$providerPaymentId);
    }

    public function capture(string $providerPaymentId, int $amount): PaymentStatusData
    {
        return new PaymentStatusData($providerPaymentId, PaymentStatus::Paid, $providerPaymentId, $amount);
    }

    public function void(string $providerPaymentId): PaymentStatusData
    {
        return new PaymentStatusData($providerPaymentId, PaymentStatus::Cancelled, $providerPaymentId);
    }
}
