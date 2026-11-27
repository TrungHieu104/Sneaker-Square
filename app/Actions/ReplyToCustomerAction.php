<?php

namespace App\Actions;

use App\Enums\AssistantIntent;
use App\Mail\AssistantReplyMail;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Models\UserModel;
use Illuminate\Support\Facades\Mail;

/**
 * A staff member answers in a chat. The widget shows the answer on its next
 * poll. It also goes to a logged-in customer's email when their widget has
 * not checked in for a while, or when it is the first answer to a chat
 * handed over outside working hours, as the hand-off notice promised.
 */
class ReplyToCustomerAction
{
    // The open widget checks in every few seconds; this long without one means the customer is gone.
    public const AWAY_AFTER_MINUTES = 2;

    /**
     * @return Message|null null when the chat was closed meanwhile: the customer has moved on to a new one
     */
    public function execute(Conversation $conversation, UserModel $staff, string $body): ?Message
    {
        $open = Conversation::whereKey($conversation->id)
            ->where('status', '!=', Conversation::CLOSED)
            ->update(['status' => Conversation::ANSWERED, 'last_message_at' => now()]);

        if ($open === 0) {
            return null;
        }

        $conversation->refresh();
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => Message::STAFF,
            'body' => $body,
            'staff_id' => $staff->user_id,
            'created_at' => now(),
        ]);

        $customer = $conversation->user;

        if ($customer?->email && ($this->away($conversation) || $this->firstAnswerAfterHours($conversation, $message))) {
            Mail::to($customer->email)->queue(new AssistantReplyMail($message));
        }

        return $message;
    }

    private function away(Conversation $conversation): bool
    {
        $seen = $conversation->customer_seen_at;

        return $seen === null || $seen->lt(now()->subMinutes(self::AWAY_AFTER_MINUTES));
    }

    /**
     * The hand-off notice promised an out-of-hours answer by email; once that
     * first answer is sent, the customer is back and the away rule decides.
     * Judged on the latest hand-off: `handed_off_at` keeps only the first.
     */
    private function firstAnswerAfterHours(Conversation $conversation, Message $reply): bool
    {
        $notice = Message::where('conversation_id', $conversation->id)
            ->where('sender', Message::SYSTEM)
            ->where('intent', AssistantIntent::Handoff->value)
            ->where('id', '<', $reply->id)
            ->latest('id')
            ->first();
        $handedOff = $notice?->created_at ?? $conversation->handed_off_at;

        if ($handedOff === null) {
            return false;
        }

        $hour = (int) $handedOff->format('G');

        return ($hour < HandOffToStaffAction::OPENS_AT || $hour >= HandOffToStaffAction::CLOSES_AT)
            && ! Message::where('conversation_id', $conversation->id)
                ->where('sender', Message::STAFF)
                ->where('id', '<', $reply->id)
                ->where('created_at', '>=', $handedOff)
                ->exists();
    }
}
