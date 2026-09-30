<?php

namespace App\Services\Payment;

use RuntimeException;

/**
 * The customer asked to pay, or to pay differently, and the order no longer
 * allows it. The message is written for the customer.
 */
class PaymentNotAllowed extends RuntimeException {}
