<?php

declare(strict_types=1);

namespace WeBRTeu\Payments;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Payments\Contracts\Gateway;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Exceptions\PaymentException;
use WeBRTeu\Payments\Models\Payment;

class PaymentManager
{
    /**
     * @var array<string, Closure(Container): mixed>
     */
    private array $creators = [];

    /**
     * @var array<string, Gateway>
     */
    private array $gateways = [];

    public function __construct(private readonly Container $container) {}

    public function gateway(?string $name = null): Gateway
    {
        $name ??= $this->getDefaultGateway();

        return $this->gateways[$name] ??= $this->resolve($name);
    }

    /**
     * Înregistrează un procesator; apelat din service provider-ul fiecărui pachet de plată.
     *
     * @param  Closure(Container): mixed  $creator
     */
    public function extend(string $name, Closure $creator): static
    {
        $this->creators[$name] = $creator;
        unset($this->gateways[$name]);

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->creators[$name]);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys($this->creators);
    }

    public function getDefaultGateway(): string
    {
        return Config::string('payments.default', 'netopia');
    }

    /**
     * Creează rândul `payments` (Pending), pornește plata și întoarce unde trimiți clientul.
     */
    public function start(string $gateway, PaymentRequestData $request, ?Model $payable = null): PaymentRedirectData
    {
        $driver = $this->gateway($gateway);

        $payment = new Payment([
            'gateway' => $driver->name(),
            'order_id' => $request->orderId,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => PaymentStatus::Pending,
        ]);

        if ($payable instanceof Model) {
            $payment->payable()->associate($payable);
        }

        $payment->save();

        $redirect = $driver->createPayment($request);

        if ($redirect->providerPaymentId !== null) {
            $payment->forceFill(['provider_payment_id' => $redirect->providerPaymentId])->save();
        }

        return $redirect;
    }

    private function resolve(string $name): Gateway
    {
        if (! isset($this->creators[$name])) {
            throw PaymentException::gatewayNotRegistered($name);
        }

        $gateway = ($this->creators[$name])($this->container);

        if (! $gateway instanceof Gateway) {
            throw PaymentException::invalidGateway($name);
        }

        return $gateway;
    }
}
