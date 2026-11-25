<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use App\Models\UserModel;
use App\Services\UserEventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The chat of whoever is asking: by account when logged in, by the
 * visitor cookie otherwise. The id never comes from the browser, so nobody
 * can open another person's chat by changing a number.
 */
class ConversationFinder
{
    public function __construct(private readonly UserEventLog $visitors) {}

    // How far along with staff a chat is, for merging two open ones.
    private const STAFF_RANK = [Conversation::BOT => 0, Conversation::ANSWERED => 1, Conversation::WAITING_STAFF => 2];

    /**
     * The chat the widget shows: the latest, closed or not, so a staff answer
     * and the closing notice stay readable after the chat ends.
     */
    public function current(): ?Conversation
    {
        return $this->mine()?->latest('id')->first();
    }

    /**
     * What the widget has not drawn yet. Lines are numbered across chats, so
     * a chat closed since then still delivers its closing notice ahead of the
     * next chat's lines, and the customer sees where one ended. Opened afresh,
     * the widget shows the current chat alone.
     *
     * @return Collection<int, Message>
     */
    public function messagesAfter(?Conversation $current, int $after): Collection
    {
        $mine = $this->mine();

        if ($current === null || $mine === null) {
            return new Collection;
        }

        if ($after === 0) {
            return $current->messages()->get();
        }

        return Message::whereIn('conversation_id', $mine->select('id'))->where('id', '>', $after)->orderBy('id')->get();
    }

    /**
     * The open chat a new question goes into; a closed one is never reopened.
     *
     * @return Conversation|null null for a crawler, which has no visitor id
     */
    public function currentOrStart(): ?Conversation
    {
        $visitor = $this->visitors->visitorId();
        $user = Auth::guard('web')->user();

        if ($visitor === null && ! $user instanceof UserModel) {
            return null;
        }

        return $this->mine()?->where('status', '!=', Conversation::CLOSED)->latest('id')->first() ?? Conversation::create([
            'user_id' => $user instanceof UserModel ? $user->user_id : null,
            'session_id' => $visitor,
            'status' => Conversation::BOT,
            'last_message_at' => now(),
        ]);
    }

    /**
     * The customer has the panel open: a staff answer reaches them there and
     * needs no email. Not a change to the chat, so updated_at stays.
     */
    public function markSeen(Conversation $conversation): void
    {
        $conversation->timestamps = false;
        $conversation->update(['customer_seen_at' => now()]);
        $conversation->timestamps = true;
    }

    /**
     * A guest's chats become the account's when they log in on the same browser.
     */
    public function claimFor(UserModel $user): void
    {
        $visitor = $this->visitors->visitorId();

        if ($visitor === null) {
            return;
        }

        $guest = Conversation::where('session_id', $visitor)->whereNull('user_id')
            ->where('status', '!=', Conversation::CLOSED)->latest('id')->first();
        $own = Conversation::where('user_id', $user->user_id)
            ->where('status', '!=', Conversation::CLOSED)->latest('id')->first();

        if ($guest !== null && $own !== null) {
            $this->merge($guest, $own);
        }

        Conversation::where('session_id', $visitor)->whereNull('user_id')->update(['user_id' => $user->user_id]);
    }

    /**
     * @return Builder<Conversation>|null
     */
    private function mine(): ?Builder
    {
        $user = Auth::guard('web')->user();

        if ($user instanceof UserModel) {
            return Conversation::where('user_id', $user->user_id);
        }

        $visitor = $this->visitors->visitorId();

        return $visitor === null ? null : Conversation::where('session_id', $visitor)->whereNull('user_id');
    }

    /**
     * The widget shows one chat, so two open ones would hide the other's
     * staff answers: the guest's lines join the account's chat, which keeps
     * whichever state is further along with staff.
     */
    private function merge(Conversation $guest, Conversation $own): void
    {
        DB::transaction(function () use ($guest, $own) {
            Message::where('conversation_id', $guest->id)->update(['conversation_id' => $own->id]);
            Unanswered::where('conversation_id', $guest->id)->update(['conversation_id' => $own->id]);

            $own->update([
                'status' => self::STAFF_RANK[$guest->status] > self::STAFF_RANK[$own->status] ? $guest->status : $own->status,
                'handed_off_at' => $own->handed_off_at ?? $guest->handed_off_at,
                'pro_id' => $guest->pro_id ?? $own->pro_id,
                'last_message_at' => collect([$own->last_message_at, $guest->last_message_at])->filter()->max(),
            ]);
            $guest->delete();
        });
    }
}
