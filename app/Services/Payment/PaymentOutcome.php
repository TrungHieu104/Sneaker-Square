<?php

namespace App\Services\Payment;

/**
 * What a payment gateway told us about an order.
 *
 * Kept separate from the gateways' own numeric result codes so the rest of the
 * application never has to know that MoMo says 1006 and VNPay says 24 for the
 * same thing.
 */
enum PaymentOutcome: string
{
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Pending = 'pending';
}
