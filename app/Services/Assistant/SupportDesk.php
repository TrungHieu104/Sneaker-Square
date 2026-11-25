<?php

namespace App\Services\Assistant;

use App\Enums\OrderStatus;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantKnowledgeModel;
use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use App\Models\CustomerSegmentModel;
use App\Models\OrderModel;
use App\Services\ShoeSizeMemory;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * What the admin "Trợ lý AI" pages read: the chats waiting for a person, the
 * customer behind one, the questions left unanswered and the month's figures.
 */
class SupportDesk
{
    public const TAB_WAITING = 'cho';

    public const TAB_ALL = 'tat-ca';

    public const WINDOW_DAYS = 30;

    private const PER_PAGE = 20;

    private const SAMPLES = 2;

    public function __construct(
        private readonly ShoeSizeMemory $sizes,
        private readonly KnowledgeBase $knowledge,
    ) {}

    public function waitingCount(): int
    {
        return Conversation::where('status', Conversation::WAITING_STAFF)->count();
    }

    /**
     * Waiting chats first, then the latest; each with the customer's last line.
     *
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function conversations(string $tab): LengthAwarePaginator
    {
        return Conversation::query()
            ->with('user')
            ->select('assistant_conversations.*')
            ->when($tab === self::TAB_WAITING, fn ($q) => $q->whereIn('status', [Conversation::WAITING_STAFF, Conversation::ANSWERED]))
            ->addSelect(['preview' => Message::select('body')
                ->whereColumn('conversation_id', 'assistant_conversations.id')
                ->where('sender', Message::CUSTOMER)
                ->latest('id')
                ->limit(1)])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Conversation::WAITING_STAFF])
            ->orderByDesc('last_message_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function customer(Conversation $conversation): array
    {
        $user = $conversation->user;

        if ($user === null) {
            return ['guest' => true];
        }

        $kept = OrderModel::where('user_id', $user->user_id)->where('order_status', '!=', OrderStatus::Cancelled->value);

        return [
            'guest' => false,
            'user' => $user,
            'segment' => CustomerSegmentModel::find($user->user_id)?->segment?->label(),
            'orders' => (clone $kept)->count(),
            'spent' => (int) (clone $kept)->sum('order_total'),
            'latest' => OrderModel::where('user_id', $user->user_id)->latest('order_id')->first(),
            'size' => $this->sizes->lastPurchasedBy((int) $user->user_id),
        ];
    }

    /**
     * For each staff reply in the chat, what the "save as answer" form starts
     * from: the question it answers and where the answer may apply. The
     * question is the logged one it answers, which carries its own product;
     * otherwise the customer's line just before.
     *
     * A reply already saved is marked so, and offers no form: it is saved once.
     *
     * @return array<int, array{saved: bool, question: string, choices: array<int, array{scope: string, id: ?int, label: string}>}> by message id
     */
    public function savableReplies(Conversation $conversation): array
    {
        $replies = [];
        $line = '';
        $saved = AssistantKnowledgeModel::whereIn('message_id', $conversation->messages->where('sender', Message::STAFF)->pluck('id'))
            ->pluck('message_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($conversation->messages as $message) {
            if ($message->sender === Message::CUSTOMER) {
                $line = $message->body;
            } elseif ($message->sender === Message::STAFF) {
                $asked = Unanswered::answeredBy($message);
                $replies[$message->id] = [
                    'saved' => in_array($message->id, $saved, true),
                    'question' => Str::limit($asked->question ?? $line, 250, ''),
                    'choices' => $this->knowledge->scopeChoices($asked?->product ?? $conversation->product),
                ];
            }
        }

        return $replies;
    }

    /**
     * The last WINDOW_DAYS days.
     *
     * @return array<string, int|null>
     */
    public function stats(): array
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $chats = Conversation::where('created_at', '>=', $since)->count();
        $handed = Conversation::where('created_at', '>=', $since)->whereNotNull('handed_off_at')->count();

        return [
            'questions' => Message::where('sender', Message::CUSTOMER)->where('created_at', '>=', $since)->count(),
            'chats' => $chats,
            'bot_share' => $chats > 0 ? (int) round(($chats - $handed) * 100 / $chats) : null,
            'handed' => $handed,
            'median_wait' => $this->medianWaitMinutes($since),
            'open_groups' => Unanswered::whereNull('resolved_at')->distinct()->count('group_id'),
            'open_questions' => Unanswered::whereNull('resolved_at')->count(),
        ];
    }

    /**
     * Open groups, most asked first: the first question, a few others like
     * it, how often and when last, and for a question the customer was not
     * happy with, what the bot had said.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function groups(): Collection
    {
        $counts = Unanswered::whereNull('resolved_at')
            ->toBase()
            ->selectRaw('group_id, COUNT(*) as asks, MAX(created_at) as last_at')
            ->groupBy('group_id')
            ->orderByDesc('asks')
            ->orderByDesc('last_at')
            ->get();

        $ids = $counts->pluck('group_id')->all();
        $leads = Unanswered::with('product.getCate')->whereIn('id', $ids)->get()->keyBy('id');
        $botSaid = $this->botAnswers($leads);
        $others = Unanswered::whereIn('group_id', $ids)
            ->whereNull('resolved_at')
            ->whereColumn('id', '!=', 'group_id')
            ->latest('id')
            ->get(['group_id', 'question'])
            ->groupBy('group_id');

        return $counts->map(function ($row) use ($leads, $botSaid, $others) {
            $shown = ($others[$row->group_id] ?? collect())->pluck('question')->unique()->take(self::SAMPLES)->values();

            return [
                'id' => (int) $row->group_id,
                'lead' => $leads[$row->group_id] ?? null,
                'asks' => (int) $row->asks,
                'last_at' => $row->last_at,
                'others' => $shown,
                'more' => max(0, (int) $row->asks - 1 - $shown->count()),
                'bot_answer' => $botSaid[$row->group_id] ?? null,
                'choices' => $this->knowledge->scopeChoices($leads->get($row->group_id)?->product),
            ];
        })->filter(fn (array $group) => $group['lead'] !== null)->values();
    }

    /**
     * The bot's reply to each lead question a customer was unhappy with,
     * keyed by lead id: the first bot line after the question.
     *
     * @param  Collection<int, Unanswered>  $leads
     * @return array<int, string>
     */
    private function botAnswers(Collection $leads): array
    {
        $unhappy = $leads->where('reason', Unanswered::UNHAPPY);

        if ($unhappy->isEmpty()) {
            return [];
        }

        $replies = Message::whereIn('conversation_id', $unhappy->pluck('conversation_id'))
            ->where('sender', Message::BOT)
            ->orderBy('id')
            ->get(['id', 'conversation_id', 'body'])
            ->groupBy('conversation_id');

        return $unhappy->mapWithKeys(fn (Unanswered $lead) => [
            $lead->id => $replies->get($lead->conversation_id)?->first(fn (Message $reply) => $reply->id > $lead->message_id)?->body,
        ])->filter()->all();
    }

    private function medianWaitMinutes(DateTimeInterface $since): ?int
    {
        $waits = Conversation::where('handed_off_at', '>=', $since)
            ->select(['id', 'handed_off_at'])
            ->addSelect(['first_reply_at' => Message::selectRaw('MIN(created_at)')
                ->whereColumn('conversation_id', 'assistant_conversations.id')
                ->where('sender', Message::STAFF)
                ->whereColumn('created_at', '>=', 'assistant_conversations.handed_off_at')])
            ->get()
            ->filter(fn (Conversation $conversation) => $conversation->getAttribute('first_reply_at') !== null && $conversation->handed_off_at !== null)
            ->map(fn (Conversation $conversation) => (int) round($conversation->handed_off_at->diffInSeconds(Carbon::parse((string) $conversation->getAttribute('first_reply_at'))) / 60))
            ->sort()
            ->values();

        return $waits->isEmpty() ? null : (int) $waits->median();
    }
}
