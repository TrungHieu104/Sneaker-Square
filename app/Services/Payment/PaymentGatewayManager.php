<?php

namespace App\Services\Payment;

/**
 * Picks the gateway a request belongs to.
 *
 * Callbacks all arrive on the same URL, so which gateway sent one has to be
 * worked out from the parameters. Every gateway then verifies its own
 * signature — no callback is acted on until one of them says it is genuine.
 */
class PaymentGatewayManager
{
    /**
     * @var array<int, PaymentGateway>
     */
    private array $gateways;

    public function __construct(VnPayGateway $vnpay, MoMoGateway $momo)
    {
        $this->gateways = [$vnpay, $momo];
    }

    /**
     * The gateway matching the value stored in `order.order_payment`.
     */
    public function byName(string $name): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->name() === $name) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Verifies a callback with whichever gateway recognises it.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidPaymentCallbackException when no gateway claims the request
     *                                         or the signature does not match
     */
    public function verify(array $params): PaymentCallback
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->handles($params)) {
                return $gateway->verify($params);
            }
        }

        throw new InvalidPaymentCallbackException('Không nhận diện được cổng thanh toán của phản hồi này.');
    }

    /**
     * Whether the request looks like a gateway callback at all.
     *
     * A customer who simply lands on the return URL with no parameters is not
     * an attack; they just get sent back to the shop.
     *
     * @param  array<string, mixed>  $params
     */
    public function looksLikeCallback(array $params): bool
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->handles($params)) {
                return true;
            }
        }

        return false;
    }
}
