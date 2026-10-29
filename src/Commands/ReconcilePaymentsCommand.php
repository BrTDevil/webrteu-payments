<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Models\Payment;
use WeBRTeu\Payments\PaymentManager;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile
        {--gateway= : Doar plățile acestui procesator}
        {--older-than= : Doar plățile mai vechi de atâtea minute (implicit din config)}';

    protected $description = 'Verifică direct la procesator plățile rămase în așteptare (webhook-uri pierdute)';

    public function handle(PaymentManager $payments): int
    {
        $checked = 0;
        $changed = 0;
        $failed = 0;

        foreach ($this->query()->get() as $payment) {
            if (! $payments->has($payment->gateway)) {
                $this->warn("Procesatorul [{$payment->gateway}] nu este înregistrat; sar peste plata {$payment->order_id}.");
                $failed++;

                continue;
            }

            try {
                $checked++;
                $status = $payments->gateway($payment->gateway)->status($payment->provider_payment_id ?? $payment->order_id);

                if ($payment->applyStatus($status->status, $status->amount, $status->providerPaymentId, $status->raw)) {
                    $changed++;
                }
            } catch (Throwable $exception) {
                $failed++;

                Log::error('payments.reconcile_failed', [
                    'gateway' => $payment->gateway,
                    'order_id' => $payment->order_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Verificate: {$checked}, schimbate: {$changed}, erori: {$failed}.");

        return self::SUCCESS;
    }

    /**
     * @return Builder<Payment>
     */
    private function query(): Builder
    {
        $gateway = $this->option('gateway');
        $olderThan = $this->option('older-than');
        $minutes = is_numeric($olderThan) ? (int) $olderThan : Config::integer('payments.reconcile.older_than_minutes', 15);

        return Payment::query()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Authorized->value])
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->where('created_at', '>=', now()->subHours(Config::integer('payments.reconcile.max_age_hours', 48)))
            ->when(is_string($gateway) && $gateway !== '', fn (Builder $query): Builder => $query->where('gateway', $gateway))
            ->orderBy('id');
    }
}
