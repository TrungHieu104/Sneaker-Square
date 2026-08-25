<?php

namespace App\Services\Payment;

use RuntimeException;

/**
 * Thrown when a payment callback cannot be trusted.
 *
 * Covers a missing or mismatched signature, an unknown order, and an amount
 * that differs from what the server stored — anything that means the request
 * did not really come from the gateway, or does not describe the order we
 * think it does.
 */
class InvalidPaymentCallbackException extends RuntimeException
{
}
