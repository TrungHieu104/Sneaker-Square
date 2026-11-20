<?php

namespace App\Listeners;

use App\Models\UserModel;
use App\Services\Assistant\ConversationFinder;
use Illuminate\Auth\Events\Login;

/**
 * A guest who chatted with the assistant and then logs in keeps the chat,
 * and from then on the assistant may look up their orders in it.
 */
class ClaimAssistantConversations
{
    public function __construct(private readonly ConversationFinder $conversations) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof UserModel) {
            $this->conversations->claimFor($event->user);
        }
    }
}
