<?php

namespace App\Http\Controllers\Backend;

use App\Actions\CloseConversationAction;
use App\Actions\ReplyToCustomerAction;
use App\Actions\ResolveQuestionGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AssistantAnswerRequest;
use App\Http\Requests\Backend\AssistantReplyRequest;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantUnansweredModel;
use App\Models\UserModel;
use App\Services\Assistant\SupportDesk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * The staff side of the support assistant: the chats customers handed over,
 * and the questions the assistant had no data for.
 */
class AssistantAdminController extends Controller
{
    private const ICON = 'iconMessage';

    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request, SupportDesk $desk): View
    {
        $tab = $request->input('tab') === SupportDesk::TAB_ALL ? SupportDesk::TAB_ALL : SupportDesk::TAB_WAITING;
        $conversations = $desk->conversations($tab);
        $selected = $request->filled('chat')
            ? Conversation::with(['user', 'product', 'messages.staff'])->find($request->integer('chat'))
            : $conversations->getCollection()->first()?->load(['product', 'messages.staff']);

        return view('backend.pages.assistant.inbox', [
            'tab' => $tab,
            'conversations' => $conversations,
            'selected' => $selected,
            'customer' => $selected ? $desk->customer($selected) : null,
            'replies' => $selected ? $desk->savableReplies($selected) : [],
            'waiting' => $desk->waitingCount(),
            'openGroups' => $desk->stats()['open_groups'],
        ]);
    }

    public function reply(AssistantReplyRequest $request, ReplyToCustomerAction $reply, int $conversation_id): RedirectResponse
    {
        $conversation = Conversation::findOrFail($conversation_id);
        $staff = $request->user();
        abort_unless($staff instanceof UserModel, 403);

        if ($reply->execute($conversation, $staff, (string) $request->validated('body')) === null) {
            return $this->alreadyClosed($conversation, 'Cuộc chat này đã được đóng nên tin nhắn chưa được gửi. Khách hỏi tiếp sẽ mở cuộc chat mới.');
        }

        return redirect()->route('assistant_admin.index', ['chat' => $conversation->id]);
    }

    public function close(CloseConversationAction $close, int $conversation_id): RedirectResponse
    {
        $conversation = Conversation::findOrFail($conversation_id);

        if (! $close->execute($conversation)) {
            return $this->alreadyClosed($conversation, 'Cuộc chat này đã được đóng trước đó.');
        }

        return redirect()->route('assistant_admin.index')->with('message', 'Đã đóng cuộc chat.');
    }

    public function unanswered(SupportDesk $desk): View
    {
        return view('backend.pages.assistant.unanswered', [
            'groups' => $desk->groups(),
            'stats' => $desk->stats(),
            'waiting' => $desk->waitingCount(),
        ]);
    }

    public function answer(AssistantAnswerRequest $request, ResolveQuestionGroupAction $resolve, int $group_id): RedirectResponse
    {
        $lead = AssistantUnansweredModel::findOrFail($group_id);

        $entry = $resolve->answer(
            (int) ($lead->group_id ?? $lead->id),
            (string) $request->validated('question'),
            (string) $request->validated('answer'),
            (string) $request->validated('scope'),
            $request->filled('scope_id') ? $request->integer('scope_id') : null,
            (int) $request->user()?->user_id,
        );

        if ($entry === null) {
            Session::flash(self::ICON, 'warning');

            return redirect()->route('assistant_admin.unanswered')->with('message', 'Nhóm câu hỏi này đã được xử lý trước đó, câu trả lời không được lưu thêm.');
        }

        Session::flash(self::ICON, 'success');

        return redirect()->route('assistant_admin.unanswered')->with('message', 'Đã lưu câu trả lời. Trợ lý dùng được ngay từ câu hỏi tiếp theo.');
    }

    public function dismiss(ResolveQuestionGroupAction $resolve, int $group_id): RedirectResponse
    {
        $lead = AssistantUnansweredModel::findOrFail($group_id);
        $resolve->dismiss((int) ($lead->group_id ?? $lead->id));
        Session::flash(self::ICON, 'success');

        return redirect()->route('assistant_admin.unanswered')->with('message', 'Đã bỏ qua nhóm câu hỏi.');
    }

    /**
     * Another staff member, or the idle rule, closed it while this page was open.
     */
    private function alreadyClosed(Conversation $conversation, string $message): RedirectResponse
    {
        Session::flash(self::ICON, 'warning');

        return redirect()->route('assistant_admin.index', ['tab' => SupportDesk::TAB_ALL, 'chat' => $conversation->id])
            ->with('message', $message);
    }
}
