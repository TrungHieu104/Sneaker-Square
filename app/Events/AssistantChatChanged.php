<?php

namespace App\Events;

use App\Models\AssistantConversationModel as Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * A support chat gained a line or changed hands. Only the chat id goes out:
 * the widget and the inbox fetch the chat through their usual routes, so
 * nothing a customer wrote passes through the socket and every read is
 * still checked by those routes.
 *
 * Sent now rather than queued: a staff reply would otherwise wait behind
 * the Claude calls the same worker is making.
 */
class AssistantChatChanged implements ShouldBroadcastNow
{
    public const NAME = 'changed';

    public function __construct(public readonly int $conversationId) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(Conversation::channelFor($this->conversationId)),
            new PrivateChannel(Conversation::INBOX_CHANNEL),
        ];
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array{chat: int}
     */
    public function broadcastWith(): array
    {
        return ['chat' => $this->conversationId];
    }
}
