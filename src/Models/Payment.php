<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Events\PaymentStatusChanged;
use WeBRTeu\Payments\Support\StatusTransition;

/**
 * @property int $id
 * @property string $gateway
 * @property string $order_id
 * @property string|null $provider_payment_id
 * @property string|null $payable_type
 * @property int|string|null $payable_id
 * @property int $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property int $refunded_amount
 * @property array<array-key, mixed>|null $raw
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Payment extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'currency' => 'RON',
        'refunded_amount' => 0,
    ];

    public function getTable(): string
    {
        return Config::string('payments.tables.payments', 'payments');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'refunded_amount' => 'integer',
            'raw' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payment_id');
    }

    /**
     * Aplică un status primit de la procesator (webhook sau reconciliere), doar dacă tranziția e permisă
     * și suma corespunde. Emite PaymentStatusChanged la schimbare.
     *
     * @param  int|null  $amount  suma raportată de procesator, în bani
     * @param  array<array-key, mixed>  $raw
     */
    public function applyStatus(PaymentStatus $to, ?int $amount = null, ?string $providerPaymentId = null, array $raw = []): bool
    {
        if ($providerPaymentId !== null && $this->provider_payment_id === null) {
            $this->provider_payment_id = $providerPaymentId;
        }

        if ($this->amountMismatch($to, $amount)) {
            Log::warning('payments.amount_mismatch', [
                'gateway' => $this->gateway,
                'order_id' => $this->order_id,
                'expected' => $this->amount,
                'received' => $amount,
                'status' => $to->value,
            ]);

            $this->save();

            return false;
        }

        $from = $this->status;

        if (! StatusTransition::allowed($from, $to)) {
            $this->save();

            return false;
        }

        $this->status = $to;
        $this->raw = $raw === [] ? $this->raw : $raw;

        if ($to === PaymentStatus::Paid && $this->paid_at === null) {
            $this->paid_at = Carbon::now();
        }

        if ($to === PaymentStatus::Refunded) {
            $this->refunded_amount = $this->amount;
        }

        if ($to === PaymentStatus::PartiallyRefunded && $amount !== null) {
            $this->refunded_amount = $amount;
        }

        $this->save();

        Event::dispatch(new PaymentStatusChanged($this, $from, $to));

        return true;
    }

    private function amountMismatch(PaymentStatus $to, ?int $amount): bool
    {
        return in_array($to, [PaymentStatus::Paid, PaymentStatus::Authorized], true)
            && $amount !== null
            && $amount !== $this->amount;
    }
}
