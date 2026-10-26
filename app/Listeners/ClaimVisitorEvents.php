<?php

namespace App\Listeners;

use App\Models\UserModel;
use App\Services\UserEventLog;
use Illuminate\Auth\Events\Login;

/**
 * A guest who logs in has been this account all along: what they browsed
 * before the login is joined to it, whichever way they logged in.
 */
class ClaimVisitorEvents
{
    public function __construct(private readonly UserEventLog $events) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof UserModel) {
            $this->events->claimFor($event->user);
        }
    }
}
