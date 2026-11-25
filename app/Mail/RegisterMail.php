<?php

namespace App\Mail;

use App\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RegisterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const VERIFY_ROUTE = 'verifed_register';

    // The mail may sit unread for a while; past this the customer asks for a new one.
    public const LINK_DAYS = 7;

    public $user;

    public function __construct($user, public string $link)
    {
        $this->user = $user;
    }

    /**
     * Signed while the request is still being served: a queue worker would
     * sign it against APP_URL, which need not be the host the customer uses.
     */
    public static function verifyLink(UserModel $user): string
    {
        return URL::temporarySignedRoute(self::VERIFY_ROUTE, now()->addDays(self::LINK_DAYS), ['user' => $user->user_id]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Đăng ký thành công tài khoản Sneaker Square ',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.RegisterMail',
            with: ['link' => $this->link],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
