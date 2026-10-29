<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Facades;

use Illuminate\Support\Facades\Facade;
use WeBRTeu\Payments\PaymentManager;

/**
 * @method static \WeBRTeu\Payments\Contracts\Gateway gateway(?string $name = null)
 * @method static PaymentManager extend(string $name, \Closure $creator)
 * @method static bool has(string $name)
 * @method static list<string> available()
 * @method static string getDefaultGateway()
 * @method static \WeBRTeu\Payments\Data\PaymentRedirectData start(string $gateway, \WeBRTeu\Payments\Data\PaymentRequestData $request, ?\Illuminate\Database\Eloquent\Model $payable = null)
 *
 * @see PaymentManager
 */
class Payments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
