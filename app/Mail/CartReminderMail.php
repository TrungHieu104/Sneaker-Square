<?php

namespace App\Mail;

use App\Models\CartReminderModel;
use App\Services\CartReminders\CartLines;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class CartReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CartReminderModel $reminder) {}

    public function envelope(): Envelope
    {
        $coupon = $this->reminder->coupon;

        return new Envelope(
            subject: $coupon
                ? 'Tặng riêng bạn mã giảm '.$coupon->coupon_value.'% cho giỏ hàng đang chờ'
                : 'Bạn còn sản phẩm trong giỏ hàng tại Sneaker Square',
        );
    }

    /**
     * Mail clients show their own unsubscribe button when this is present.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.route('cart.reminder.unsubscribe', $this->reminder->token).'>',
        ]);
    }

    /**
     * The lines are priced when the email is built, not when the cart was
     * left: the email must not promise yesterday's price.
     */
    public function content(): Content
    {
        $lines = app(CartLines::class)->sellable($this->reminder->lines);

        return new Content(
            view: 'mail.cartReminder',
            with: [
                'name' => $this->reminder->user?->name,
                'lines' => $lines,
                'total' => app(CartLines::class)->value($lines),
                'coupon' => $this->reminder->coupon,
                'restoreUrl' => route('cart.restore', $this->reminder->token),
                'unsubscribeUrl' => route('cart.reminder.unsubscribe', $this->reminder->token),
                'pixelUrl' => route('cart.reminder.opened', $this->reminder->token),
            ],
        );
    }
}
