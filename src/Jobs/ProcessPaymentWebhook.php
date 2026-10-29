<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Throwable;
use WeBRTeu\Payments\Contracts\ConfirmsWebhooks;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Models\Payment;
use WeBRTeu\Payments\Models\PaymentEvent;
use WeBRTeu\Payments\PaymentManager;

class ProcessPaymentWebhook implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public string $gateway,
        public WebhookData $webhook,
        public int $eventId,
    ) {
        $queue = Config::get('payments.queue');

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(PaymentManager $payments): void
    {
        $event = PaymentEvent::query()->findOrFail($this->eventId);

        if ($event->processed_at !== null) {
            return;
        }

        try {
            $gateway = $payments->gateway($this->gateway);
            $webhook = $gateway instanceof ConfirmsWebhooks ? $gateway->confirm($this->webhook) : $this->webhook;

            $payment = Payment::query()->firstOrCreate([
                'gateway' => $this->gateway,
                'order_id' => $webhook->orderId,
            ], [
                'provider_payment_id' => $webhook->providerPaymentId,
                'amount' => $webhook->amount ?? 0,
                'status' => PaymentStatus::Pending,
            ]);

            $payment->applyStatus($webhook->status, $webhook->amount, $webhook->providerPaymentId, $webhook->raw);

            $event->forceFill([
                'payment_id' => $payment->id,
                'status' => $webhook->status,
                'processed_at' => Carbon::now(),
                'error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $event->forceFill(['error' => $exception->getMessage()])->save();

            throw $exception;
        }
    }
}
