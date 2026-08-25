<?php

namespace App\Services\Payment;

/**
 * A callback from a payment gateway whose signature has already been verified.
 *
 * Only a gateway may build one of these, and only after checking the HMAC, so
 * holding an instance is itself the proof that the data is authentic. That is
 * why every field is readonly.
 */
final class PaymentCallback
{
    public function __construct(
        public readonly string $gateway,
        public readonly string $orderCode,
        public readonly ?int $amount,
        public readonly PaymentOutcome $outcome,
        public readonly ?string $reference = null,
        public readonly ?string $message = null,
    ) {
    }
}
