<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\HandOffToStaffAction;
use App\Actions\ReceiveCustomerMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AssistantMessageRequest;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Services\Assistant\ConversationFinder;
use App\Services\Assistant\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The storefront chat widget's three calls: read the chat, ask a question,
 * ask for a person. Answers come from the queue, so the widget polls while
 * `thinking` is true. The page itself may be served from the shared cache,
 * so everything personal arrives through here.
 */
class AssistantController extends Controller
{
    public function __construct(
        private readonly ConversationFinder $conversations,
        private readonly KnowledgeBase $knowledge,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $conversation = $this->conversations->current();

        if ($conversation !== null && $request->boolean('mo')) {
            $this->conversations->markSeen($conversation);
        }

        return $this->state($conversation, $request->integer('sau'));
    }

    public function send(AssistantMessageRequest $request, ReceiveCustomerMessageAction $receive): JsonResponse
    {
        $conversation = $this->conversations->currentOrStart();
        abort_if($conversation === null, 403);

        $page = $request->filled('pro_id')
            ? ProductModel::where('pro_hidden', 1)->find($request->integer('pro_id'))
            : null;

        $receive->execute($conversation, (string) $request->validated('message'), $page);

        return $this->state($conversation->refresh(), $request->integer('sau'));
    }

    public function handOff(Request $request, HandOffToStaffAction $handOff): JsonResponse
    {
        $conversation = $this->conversations->currentOrStart();
        abort_if($conversation === null, 403);

        $handOff->execute($conversation, customerAsked: true);

        return $this->state($conversation->refresh(), $request->integer('sau'));
    }

    private function state(?Conversation $conversation, int $after): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $name = $user instanceof UserModel ? trim((string) $user->name) : '';
        $messages = $this->conversations->messagesAfter($conversation, $after);
        // The widget polls every few seconds; most polls carry no source to link.
        $pages = $messages->contains(fn (AssistantMessageModel $message) => ($message->meta['sources'] ?? []) !== [])
            ? $this->knowledge->pages()
            : [];

        return response()->json([
            'greeting' => 'Chào '.($name !== '' ? last(explode(' ', $name)) : 'bạn').', mình có thể giúp gì cho bạn?',
            'loggedIn' => $user instanceof UserModel,
            'status' => $conversation->status ?? Conversation::BOT,
            'thinking' => $conversation !== null && $this->botOwesReply($conversation),
            'messages' => $messages->map(fn (AssistantMessageModel $message) => $message->toWidget($pages))->values(),
        ]);
    }

    /**
     * The customer spoke last and the bot still has the chat: an answer is
     * on its way from the queue.
     */
    private function botOwesReply(Conversation $conversation): bool
    {
        return $conversation->status === Conversation::BOT
            && $conversation->messages()->reorder('id', 'desc')->value('sender') === AssistantMessageModel::CUSTOMER;
    }
}
