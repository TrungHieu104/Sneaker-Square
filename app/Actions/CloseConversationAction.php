<?php

namespace App\Actions;

use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Services\ShopSettings;

/**
 * Ends a chat with staff. The customer's next question starts a new chat
 * that the bot answers, so a chat left open would keep the bot silent.
 */
class CloseConversationAction
{
    public function __construct(private readonly ShopSettings $settings) {}

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
            ->where('last_message_at', '<', now()->subHours($this->settings->assistantIdleHours()))
            ->get();

        return $idle->filter(fn (Conversation $conversation) => $this->execute($conversation))->count();
    }
}
