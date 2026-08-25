<?php

namespace App\Services\Payment;

use App\Models\OrderModel;

interface PaymentGateway
{
    /**
     * The value stored in `order.order_payment` for this gateway.
     */
    public function name(): string;

    /**
     * Whether this gateway recognises the query string as one of its callbacks.
     *
     * @param  array<string, mixed>  $params
     */
    public function handles(array $params): bool;

    /**
     * Builds the URL the customer is sent to in order to pay.
     */
    public function checkoutUrl(OrderModel $order): string;

    /**
     * Verifies a callback's signature and translates it into our own vocabulary.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidPaymentCallbackException when the signature does not match
     */
    public function verify(array $params): PaymentCallback;
}
