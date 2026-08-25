<?php

namespace App\Services;

use App\Mail\ConfirmOrder;
use App\Models\OrderModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the order confirmation.
 *
 * The controller used to do this in a method that read the order code out of
 * $_GET and addressed the mail to whoever happened to be logged in. That
 * method was also exposed on its own public route, so hitting it with someone
 * else's order code mailed you their order. Both the address and the order now
 * come from the order record itself.
 */
class OrderMailer
{
    public function sendConfirmation(OrderModel $order): void
    {
        $recipient = $order->order_email ?: $order->User?->email;

        if (! $recipient) {
            Log::warning('No address to send the order confirmation to', [
                'order_code' => $order->order_code,
            ]);

            return;
        }

        Mail::to($recipient)->send(new ConfirmOrder($order));
    }
}
