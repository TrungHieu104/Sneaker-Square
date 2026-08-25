<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderModel as Order;
use App\Models\OrderDetailModel as OrderDetail;
use App\Models\CouponModel as Coupon;
use Illuminate\Mail\Mailables\Attachment;

class ConfirmOrder extends Mailable implements ShouldQueue
{ 
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $order;

    /**
     * The short name used to greet the customer.
     *
     * Taken from the order's own recipient name rather than the logged-in user.
     * This mailable is queued, and it is now also sent from a payment gateway
     * notification, so there is no session to read a name from in either case.
     */
    public $lastName;

    public function __construct(
        $order
    ) {
        $this->order = $order;
        $this->lastName = self::lastNameOf($order->order_name ?? null);
    }

    private static function lastNameOf(?string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY);

        return $parts ? end($parts) : '';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông tin đơn hàng ' . $this->order->order_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $od = OrderDetail::where('order_id', $this->order->order_id)->get();
        $lastName = $this->lastName;

        return new Content(
            view: 'mail.confirmOrder',
            with: [
                'order_code' => $this->order->order_code,
                'order_name' => $this->order->order_name,
                'order_phone' => $this->order->order_phone,
                'order_email' => $this->order->order_email,
                'order_address' => $this->order->order_address,
                'order_local' => $this->order->order_local,
                'order_delivery_fee' => $this->order->order_delivery_fee,
                'order_payment' => $this->order->order_payment,
                'order_payment_status' => $this->order->order_payment_status,
                'order_total' => $this->order->order_total,
                'coupon_id' => $this->order->coupon_id,
                'note_customer' => $this->order->note_customer,
                'od' => $od,
                'lastName' => $lastName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            
        ];
    }
}
