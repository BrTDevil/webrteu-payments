<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Payments\Enums\PaymentStatus;

/**
 * @property int $id
 * @property string $gateway
 * @property string $event_id
 * @property int|null $payment_id
 * @property PaymentStatus|null $status
 * @property bool $signature_valid
 * @property string|null $raw_body
 * @property array<string, mixed>|null $headers
 * @property Carbon|null $processed_at
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PaymentEvent extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return Config::string('payments.tables.payment_events', 'payment_events');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'signature_valid' => 'boolean',
            'headers' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
