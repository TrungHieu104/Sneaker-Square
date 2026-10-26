<?php

namespace App\Listeners;

use App\Logging\LogChannel;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

/**
 * A queued email that ran out of attempts. The exception itself still goes
 * to laravel.log; this puts the failure next to the emails that did go out.
 */
class LogFailedMail
{
    public function handle(JobFailed $event): void
    {
        if (($event->job->payload()['data']['commandName'] ?? null) !== SendQueuedMailable::class) {
            return;
        }

        Log::channel(LogChannel::MAIL)->error('Mail failed', [
            'mailable' => $event->job->resolveName(),
            'connection' => $event->connectionName,
            'message' => $event->exception->getMessage(),
        ]);
    }
}
