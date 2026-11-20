<?php

namespace App\Actions;

use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;

/**
 * Ends a chat with staff. The customer's next question starts a new chat
 * that the bot answers, so a chat left open would keep the bot silent.
 */
class CloseConversationAction
{
    // Long enough for a customer to come back to the answer the next day.
    public const IDLE_HOURS = 24;

    /**
     * @return bool false when the chat was already closed
     */
    public function execute(Conversation $conversation): bool
    {
        $closed = Conversation::whereKey($conversation->id)
            ->where('status', '!=', Conversation::CLOSED)
            ->update(['status' => Conversation::CLOSED]);

        if ($closed === 0) {
            return false;
        }

        $conversation->refresh();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => Message::SYSTEM,
            'body' => 'Nhân viên đã kết thúc cuộc trò chuyện. Bạn cứ hỏi tiếp, trợ lý sẽ trả lời.',
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * Closes the chats staff answered that nobody has written in since.
     *
     * @return int chats closed
     */
    public function closeIdle(): int
    {
        $idle = Conversation::where('status', Conversation::ANSWERED)
            ->where('last_message_at', '<', now()->subHours(self::IDLE_HOURS))
            ->get();

        return $idle->filter(fn (Conversation $conversation) => $this->execute($conversation))->count();
    }
}
