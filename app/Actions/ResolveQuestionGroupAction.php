<?php

namespace App\Actions;

use App\Models\AssistantKnowledgeModel;
use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use App\Services\Assistant\KnowledgeBase;
use Illuminate\Support\Facades\DB;

/**
 * Closes a group of questions the assistant answered badly: either staff
 * write the answer once, and the assistant may quote it from then on, or
 * they set the group aside.
 */
class ResolveQuestionGroupAction
{
    public function __construct(private readonly KnowledgeBase $knowledge) {}

    /**
     * @return AssistantKnowledgeModel|null null when the group was answered or set aside meanwhile
     */
    public function answer(int $groupId, string $question, string $answer, string $scopeType, ?int $scopeId, int $authorId): ?AssistantKnowledgeModel
    {
        return DB::transaction(function () use ($groupId, $question, $answer, $scopeType, $scopeId, $authorId) {
            // Locked, so two staff answering the same group save one answer, not two.
            if (! Unanswered::where('group_id', $groupId)->whereNull('resolved_at')->lockForUpdate()->exists()) {
                return null;
            }

            $entry = $this->knowledge->addAnswer($question, $answer, $scopeType, $scopeId, $authorId);
            $this->close($groupId, Unanswered::ANSWERED, $entry->id);

            return $entry;
        });
    }

    /**
     * Staff saved their reply in a chat as an answer: the group of the
     * question that reply answers is answered by it, and only that group.
     *
     * @return AssistantKnowledgeModel|null null when this reply was saved already
     */
    public function answerFromChat(Message $reply, string $question, string $answer, string $scopeType, ?int $scopeId, int $authorId): ?AssistantKnowledgeModel
    {
        return DB::transaction(function () use ($reply, $question, $answer, $scopeType, $scopeId, $authorId) {
            // Locked, so the same reply saved from two tabs is kept once.
            Message::whereKey($reply->id)->lockForUpdate()->first();

            if (AssistantKnowledgeModel::where('message_id', $reply->id)->exists()) {
                return null;
            }

            $entry = $this->knowledge->addAnswer($question, $answer, $scopeType, $scopeId, $authorId, $reply->id);
            $asked = Unanswered::answeredBy($reply);

            if ($asked !== null) {
                $this->close((int) ($asked->group_id ?? $asked->id), Unanswered::ANSWERED, $entry->id);
            }

            return $entry;
        });
    }

    public function dismiss(int $groupId): void
    {
        $this->close($groupId, Unanswered::DISMISSED, null);
    }

    private function close(int $groupId, string $resolution, ?int $knowledgeId): void
    {
        Unanswered::where('group_id', $groupId)->whereNull('resolved_at')->update([
            'resolution' => $resolution,
            'resolved_at' => now(),
            'knowledge_id' => $knowledgeId,
        ]);
    }
}
