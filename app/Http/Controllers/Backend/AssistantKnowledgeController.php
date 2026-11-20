<?php

namespace App\Http\Controllers\Backend;

use App\Actions\ResolveQuestionGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AssistantAnswerRequest;
use App\Http\Requests\Backend\AssistantPublishRequest;
use App\Models\AssistantKnowledgeModel;
use App\Models\AssistantMessageModel as Message;
use App\Models\FaqGroupModel;
use App\Services\Assistant\KnowledgeBase;
use App\Services\Assistant\SupportDesk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * The answers staff saved for the assistant to quote: listed, corrected,
 * removed, moved onto the public FAQ page, or saved straight from a reply in a chat.
 */
class AssistantKnowledgeController extends Controller
{
    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(KnowledgeBase $knowledge, SupportDesk $desk): View
    {
        return view('backend.pages.assistant.answers', [
            'answers' => $knowledge->answers(),
            'faqGroups' => FaqGroupModel::orderBy('group_position')->orderBy('group_id')->get(),
            'waiting' => $desk->waitingCount(),
            'openGroups' => $desk->stats()['open_groups'],
        ]);
    }

    public function update(AssistantAnswerRequest $request, KnowledgeBase $knowledge, int $knowledge_id): RedirectResponse
    {
        $knowledge->updateAnswer(
            AssistantKnowledgeModel::findOrFail($knowledge_id),
            (string) $request->validated('question'),
            (string) $request->validated('answer'),
            (string) $request->validated('scope'),
            $request->filled('scope_id') ? $request->integer('scope_id') : null,
        );

        return $this->done('Đã sửa câu trả lời. Trợ lý dùng nội dung mới từ câu hỏi tiếp theo.');
    }

    public function destroy(KnowledgeBase $knowledge, int $knowledge_id): RedirectResponse
    {
        $knowledge->deleteAnswer(AssistantKnowledgeModel::findOrFail($knowledge_id));

        return $this->done('Đã xoá câu trả lời. Trợ lý không dùng nó nữa; câu hỏi nó đã trả lời quay lại danh sách chưa trả lời được.');
    }

    public function publish(AssistantPublishRequest $request, KnowledgeBase $knowledge, int $knowledge_id): RedirectResponse
    {
        $entry = AssistantKnowledgeModel::findOrFail($knowledge_id);

        // The FAQ page is shop-wide; an answer about one product would mislead there.
        if ($entry->scope_type !== AssistantKnowledgeModel::SCOPE_ALL) {
            Session::flash('iconMessage', 'warning');

            return redirect()->route('assistant_admin.answers')->with('message', 'Chỉ câu trả lời áp dụng cho toàn shop mới đăng lên trang câu hỏi thường gặp được.');
        }

        $knowledge->publishAnswer($entry, (int) $request->validated('group_id'), $request->user()?->name);

        return $this->done('Đã đăng lên trang câu hỏi thường gặp. Trợ lý vẫn dùng câu này, giờ lấy từ trang đó.');
    }

    public function storeFromChat(AssistantAnswerRequest $request, ResolveQuestionGroupAction $resolve, int $conversation_id): RedirectResponse
    {
        $reply = Message::where('conversation_id', $conversation_id)
            ->where('sender', Message::STAFF)
            ->findOrFail($request->integer('message_id'));

        $entry = $resolve->answerFromChat(
            $reply,
            (string) $request->validated('question'),
            (string) $request->validated('answer'),
            (string) $request->validated('scope'),
            $request->filled('scope_id') ? $request->integer('scope_id') : null,
            (int) $request->user()?->user_id,
        );

        Session::flash('iconMessage', $entry === null ? 'warning' : 'success');

        return redirect()->route('assistant_admin.index', ['chat' => $conversation_id])->with('message', $entry === null
            ? 'Câu trả lời này đã được lưu thành câu trả lời mẫu trước đó.'
            : 'Đã lưu thành câu trả lời mẫu. Trợ lý dùng được từ câu hỏi tiếp theo.');
    }

    private function done(string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'success');

        return redirect()->route('assistant_admin.answers')->with('message', $message);
    }
}
