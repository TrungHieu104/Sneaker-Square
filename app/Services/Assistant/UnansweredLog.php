<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantTopic;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use Illuminate\Support\Collection;

/**
 * Keeps the questions the assistant answered badly (no data, or the customer
 * asked for a person straight after the answer), grouped with an open
 * question that asks the same thing, so staff write one answer per group.
 * This is how the assistant's saved answers grow.
 */
class UnansweredLog
{
    // Enough for the questions waiting on staff at any one time; older open groups stay as they are.
    private const GROUPS_SHOWN = 40;

    // Set in a bot message's meta when Claude wrote it, as opposed to a fixed sentence.
    public const BY_MODEL = 'by_model';

    private const PROMPT = "Bạn gom các câu hỏi khách gửi cho shop giày Sneaker Square.\n"
        ."Tin nhắn có một CÂU HỎI MỚI và danh sách CÂU HỎI ĐANG MỞ, mỗi câu có số ở đầu.\n"
        ."Trả về số của câu đang mở hỏi cùng một điều với câu hỏi mới, tức là nhân viên chỉ cần viết một câu trả lời cho cả hai.\n"
        ."Hai câu chỉ khác nhau ở địa điểm, tên sản phẩm, size hay con số vẫn là cùng một điều.\n"
        .'Nếu không câu nào hỏi cùng điều đó, trả về 0. Câu cùng chủ đề nhưng hỏi điều khác thì không tính.';

    private const SCHEMA = [
        'type' => 'object',
        'properties' => ['same_as' => ['type' => 'integer']],
        'required' => ['same_as'],
        'additionalProperties' => false,
    ];

    public function __construct(private readonly ClaudeClient $claude) {}

    public function record(
        Message $question,
        AssistantIntent $intent,
        ?int $productId,
        ?AssistantTopic $topic = null,
        string $reason = Unanswered::NO_DATA,
    ): Unanswered {
        $row = Unanswered::create([
            'conversation_id' => $question->conversation_id,
            'message_id' => $question->id,
            'question' => mb_substr($question->body, 0, 500),
            'intent' => $intent->value,
            'reason' => $reason,
            'topic' => $topic?->value,
            'pro_id' => $productId,
            'created_at' => now(),
        ]);

        // Its own group first, so a failure while grouping never leaves it out of the list.
        $row->update(['group_id' => $row->id]);
        $same = $this->sameAs($row);

        if ($same !== null) {
            $row->update(['group_id' => $same]);
        }

        return $row;
    }

    /**
     * The customer asked for a person right after an answer Claude wrote:
     * that answer did not do, so its question joins the list.
     */
    public function recordUnhappy(Conversation $conversation): void
    {
        $answer = Message::where('conversation_id', $conversation->id)
            ->whereIn('sender', [Message::BOT, Message::STAFF])
            ->latest('id')
            ->first();

        if ($answer === null || $answer->sender !== Message::BOT || ($answer->meta[self::BY_MODEL] ?? false) !== true) {
            return;
        }

        $question = Message::where('conversation_id', $conversation->id)
            ->where('sender', Message::CUSTOMER)
            ->where('id', '<', $answer->id)
            ->latest('id')
            ->first();

        if ($question === null || Unanswered::where('message_id', $question->id)->exists()) {
            return;
        }

        $this->record(
            $question,
            AssistantIntent::tryFrom((string) $answer->intent) ?? AssistantIntent::Policy,
            $conversation->pro_id,
            AssistantTopic::tryFrom((string) ($answer->meta['topic'] ?? '')),
            Unanswered::UNHAPPY,
        );
    }

    private function sameAs(Unanswered $row): ?int
    {
        /** @var Collection<int, string> $open */
        $open = Unanswered::whereNull('resolved_at')
            ->whereColumn('id', 'group_id')
            ->where('id', '!=', $row->id)
            ->latest('id')
            ->limit(self::GROUPS_SHOWN)
            ->pluck('question', 'id');

        if ($open->isEmpty()) {
            return null;
        }

        $list = $open->map(fn (string $question, int $id) => $id.'. '.$this->masked($question))->implode("\n");

        try {
            $answer = $this->claude->ask(self::PROMPT, "CÂU HỎI MỚI:\n".$this->masked($row->question)."\n\nCÂU HỎI ĐANG MỞ:\n".$list, self::SCHEMA, retries: 0);
        } catch (AssistantUnavailable $e) {
            report($e);

            return null;
        }

        $id = (int) ($answer->data['same_as'] ?? 0);

        // Staff may have answered or set that group aside while Claude was reading.
        return $open->has($id) && Unanswered::whereKey($id)->whereNull('resolved_at')->exists() ? $id : null;
    }

    /**
     * These are other customers' questions: their order codes go too, not
     * only the phone numbers and emails, since none is needed to tell two
     * questions apart.
     */
    private function masked(string $question): string
    {
        return (string) preg_replace('/\+?\d[\d .-]{7,}\d/', TextMatch::NUMBER_MASK, TextMatch::withoutContacts($question));
    }
}
