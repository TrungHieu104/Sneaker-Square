<?php

namespace App\Services\Assistant;

use App\Events\AssistantChatChanged;
use App\Services\LiveSocket;

/**
 * Tells the open widget and the staff inbox that a chat changed, once the
 * change is committed, so whoever fetches it on the signal finds it.
 */
class ChatSignal
{
    public function __construct(private readonly LiveSocket $socket) {}

    public function changed(int $conversationId): void
    {
        $this->socket->send(new AssistantChatChanged($conversationId));
    }
}
