<?php

namespace App\Actions;

use App\Jobs\AnswerCustomerQuestion;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Models\ProductModel;
use App\Services\ShoeSizeMemory;

/**
 * Stores what the customer wrote and queues the bot's answer, unless staff
 * have the chat, in which case the bot stays out of it.
 */
class ReceiveCustomerMessageAction
{
    public function __construct(private readonly ShoeSizeMemory $sizes) {}

    public function execute(Conversation $conversation, string $question, ?ProductModel $page): Message
    {
        $asked = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => Message::CUSTOMER,
            'body' => $question,
            'created_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now(), 'pro_id' => $page?->pro_id ?? $conversation->pro_id]);

        if ($conversation->isWithStaff()) {
            $conversation->update(['status' => Conversation::WAITING_STAFF]);

            return $asked;
        }

        AnswerCustomerQuestion::dispatch($asked->id, $page?->pro_id, $this->sizes->measured());

        return $asked;
    }
}
