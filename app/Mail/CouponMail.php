<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\Models\CouponModel;
class CouponMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $userEmail;
    public $cou;

    public function __construct($email, $cou)
    {
        $this->userEmail = $email;
        $this->cou = $cou;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ưu đãi đặc biệt - Món quà đặc biệt dành riêng cho bạn từ Sneaker Square',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // $cou = 1;
        $info = Auth::user()->name;
        $nameParts = explode(' ', $info);

        $lastName = end($nameParts);
        return new Content(
            view: 'mail.couponMail',
            with: [
                // 'coupon_name' => $this->cou->coupon_name,
                // 'coupon_start' => $this->couponId->coupon_start,
                // 'coupon_end' => $this->couponId->coupon_end,
                // 'coupon_value' => $this->couponId->coupon_value,
                // 'coupon_condition' => $this->couponId->coupon_condition,
                // 'cou'=> $cou,
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
        return [];
    }
}
