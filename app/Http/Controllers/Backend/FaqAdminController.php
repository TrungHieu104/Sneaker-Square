<?php

namespace App\Http\Controllers\Backend;

use App\Actions\ReorderFaqAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FaqReorderRequest;
use App\Http\Requests\Backend\FaqRequest;
use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The admin "Câu hỏi thường gặp" screens: the questions of the storefront FAQ page.
 * A new or moved question goes last in its group; the order inside a group is
 * changed by dragging rows while the list shows that one group.
 */
class FaqAdminController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(Request $request)
    {
        View::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request)
    {
        [$orderBy, $orderType] = $this->listingSort($request, FaqModel::class, 'faq_position');
        $keyword = trim((string) $request->input('keyword'));
        $groupId = $request->integer('group');

        // Dragging needs the whole group on one page, in storefront order.
        $sortable = $groupId > 0 && $keyword === '' && ! $request->filled('sort-by');

        $query = FaqModel::with('group')
            ->when($keyword !== '', fn ($q) => $q->where('faq_question', 'like', '%'.$keyword.'%'))
            ->when($groupId > 0, fn ($q) => $q->where('group_id', $groupId));

        $faqs = $this->sorted($query, $request->filled('sort-by') ? [$orderBy, $orderType] : null)
            ->paginate($sortable ? max(self::PER_PAGE, (clone $query)->count()) : self::PER_PAGE)
            ->withQueryString();

        return view('backend.pages.faq.faq_list', [
            'faqs' => $faqs,
            'groups' => $this->groups(),
            'groupId' => $groupId,
            'sortable' => $sortable,
            'orderBy' => $orderBy,
            'orderType' => $orderType,
        ]);
    }

    public function create()
    {
        return view('backend.pages.faq.faq_form', ['faq' => new FaqModel(['faq_hidden' => 1]), 'groups' => $this->groups()]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $groupId = (int) $request->validated('group_id');

        FaqModel::create($this->fields($request) + [
            'faq_position' => FaqModel::nextPosition($groupId),
            'faq_created_by' => $request->user()?->name,
        ]);

        return $this->toList('Thêm câu hỏi thành công');
    }

    public function edit(FaqModel $faq)
    {
        return view('backend.pages.faq.faq_form', ['faq' => $faq, 'groups' => $this->groups()]);
    }

    public function update(FaqRequest $request, FaqModel $faq): RedirectResponse
    {
        $fields = $this->fields($request);
        $moved = $fields['group_id'] !== (int) $faq->group_id;

        $faq->update($fields + [
            'faq_position' => $moved ? FaqModel::nextPosition($fields['group_id']) : $faq->faq_position,
            'faq_updated_by' => $request->user()?->name,
        ]);

        return $this->toList('Chỉnh sửa thành công');
    }

    public function destroy(FaqModel $faq): RedirectResponse
    {
        $faq->delete();
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã xoá câu hỏi');
    }

    public function reorder(FaqReorderRequest $request, FaqGroupModel $group, ReorderFaqAction $reorder): JsonResponse
    {
        return $reorder->questions($group, $request->ids())
            ? response()->json(['message' => 'Đã lưu thứ tự câu hỏi'])
            : response()->json(['message' => ReorderFaqAction::STALE], 409);
    }

    public function status(Request $request, FaqModel $faq): JsonResponse
    {
        $faq->update(['faq_hidden' => $request->boolean('faq-status') ? 1 : 0]);

        return response()->json(['message' => 'Cập nhật thành công']);
    }

    /**
     * Unsorted, the list reads like the storefront page: by group, then within it.
     *
     * @param  Builder<FaqModel>  $query
     * @param  array{0: string, 1: string}|null  $sort  a column header the admin clicked
     * @return Builder<FaqModel>
     */
    private function sorted(Builder $query, ?array $sort): Builder
    {
        $sorted = $sort !== null
            ? $query->orderBy($sort[0], $sort[1])
            : $query->orderBy(FaqGroupModel::select('group_position')->whereColumn('faq_groups.group_id', 'faqs.group_id'))->orderBy('faq_position');

        return $sorted->orderBy('faq_id');
    }

    /**
     * @return array{group_id: int, faq_question: string, faq_answer: string, faq_hidden: int}
     */
    private function fields(FaqRequest $request): array
    {
        return [
            'group_id' => (int) $request->validated('group_id'),
            'faq_question' => trim((string) $request->validated('question')),
            'faq_answer' => (string) $request->validated('answer'),
            'faq_hidden' => (int) $request->validated('status'),
        ];
    }

    /**
     * @return Collection<int, FaqGroupModel>
     */
    private function groups()
    {
        return FaqGroupModel::orderBy('group_position')->orderBy('group_id')->get();
    }

    private function toList(string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'success');

        return redirect()->route('faq.index')->with('message', $message);
    }
}
