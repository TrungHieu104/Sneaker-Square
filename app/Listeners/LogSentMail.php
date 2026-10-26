<?php

namespace App\Listeners;

use App\Logging\LogChannel;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * One line per email that left the shop, so "I never got the email" can be
 * answered by looking.
 */
class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();

        Log::channel(LogChannel::MAIL)->info('Mail sent', [
            'mailable' => $event->data['__laravel_mailable'] ?? null,
            'to' => $message instanceof Email
                ? array_map(fn (Address $address) => $address->getAddress(), $message->getTo())
                : [],
            'subject' => $message instanceof Email ? $message->getSubject() : null,
        ]);
    }
}
