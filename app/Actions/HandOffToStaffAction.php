<?php

namespace App\Actions;

use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Services\Assistant\UnansweredLog;

/**
 * The chat leaves the bot and waits in the admin inbox, because the customer
 * asked for a person or Claude flagged that they need one. A chat staff
 * already answered goes back to waiting; asking while it waits changes nothing.
 */
class HandOffToStaffAction
{
    public const OPENS_AT = 8;

    public const CLOSES_AT = 21;

    public function __construct(private readonly UnansweredLog $unanswered) {}

    /**
     * @param  bool  $customerAsked  true when the customer asked, which marks the bot's last answer as one that did not do
     * @return Message|null the notice, or null when the chat was already waiting or closed
     */
    public function execute(Conversation $conversation, bool $customerAsked = false): ?Message
    {
        if ($customerAsked && $conversation->status === Conversation::BOT) {
            $this->unanswered->recordUnhappy($conversation);
        }

        // Guarded in the query, so a double click or a job racing the button moves the chat once.
        $moved = Conversation::whereKey($conversation->id)->where('status', Conversation::BOT)->update([
            'status' => Conversation::WAITING_STAFF,
            'handed_off_at' => now(),
            'last_message_at' => now(),
        ]) ?: Conversation::whereKey($conversation->id)->where('status', Conversation::ANSWERED)->update([
            'status' => Conversation::WAITING_STAFF,
            'last_message_at' => now(),
        ]);

        $conversation->refresh();

        if ($moved === 0) {
            return null;
        }

        return Message::create([
            'conversation_id' => $conversation->id,
            'sender' => Message::SYSTEM,
            'body' => $this->notice($conversation),
            'created_at' => now(),
        ]);
    }

    private function notice(Conversation $conversation): string
    {
        $hours = 'Giờ làm việc '.self::OPENS_AT.':00–'.self::CLOSES_AT.':00';
        $email = $conversation->user?->email;

        if ($email === null || $email === '') {
            return 'Đã chuyển cho nhân viên. '.$hours.', nhân viên sẽ trả lời ngay trong khung chat này.'
                .' Bạn chưa đăng nhập, nên hãy giữ trang này mở hoặc để lại số điện thoại để nhân viên gọi lại.';
        }

        $hour = (int) now()->format('G');

        return $hour >= self::OPENS_AT && $hour < self::CLOSES_AT
            ? 'Đã chuyển cho nhân viên. '.$hours.', nhân viên sẽ trả lời ngay trong khung chat này. Ngoài giờ, câu trả lời được gửi về email '.$this->masked($email).'.'
            : 'Đã chuyển cho nhân viên. Bây giờ đã ngoài giờ làm việc ('.self::OPENS_AT.':00–'.self::CLOSES_AT.':00), câu trả lời sẽ được gửi về email '.$this->masked($email).'.';
    }

    private function masked(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 3).'…@'.$domain;
    }
}
