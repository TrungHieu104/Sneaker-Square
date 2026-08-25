<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\CouponModel;
class CouponMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $cou;

    /**
     * The mail previously also carried the sending administrator's e-mail address
     * and display name. Neither was ever rendered by the template, and shipping an
     * admin's details inside a customer e-mail is worth avoiding, so both are gone.
     * The recipient is chosen by the caller through Mail::to().
     */
    public function __construct(CouponModel $cou)
    {
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
        // The template reads $cou, which is passed automatically as a public property.
        return new Content(view: 'mail.couponMail');
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
