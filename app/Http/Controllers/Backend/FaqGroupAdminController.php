<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FaqGroupRequest;
use App\Models\FaqGroupModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The admin "Nhóm câu hỏi" screen: the headings of the storefront FAQ page,
 * listed beside the form that adds or edits one.
 */
class FaqGroupAdminController extends Controller
{
    public function __construct(Request $request)
    {
        View::share('keyword', $request->input('keyword'));
    }

    public function index()
    {
        return $this->screen(null);
    }

    public function store(FaqGroupRequest $request): RedirectResponse
    {
        FaqGroupModel::create([
            'group_name' => trim((string) $request->validated('name')),
            'group_position' => $request->validated('position') ?? FaqGroupModel::nextPosition(),
            'group_hidden' => (int) $request->validated('status'),
        ]);

        return $this->toList('success', 'Thêm nhóm thành công');
    }

    public function edit(FaqGroupModel $group)
    {
        return $this->screen($group);
    }

    public function update(FaqGroupRequest $request, FaqGroupModel $group): RedirectResponse
    {
        $group->update([
            'group_name' => trim((string) $request->validated('name')),
            'group_position' => $request->validated('position') ?? $group->group_position,
            'group_hidden' => (int) $request->validated('status'),
        ]);

        return $this->toList('success', 'Chỉnh sửa thành công');
    }

    public function destroy(FaqGroupModel $group): RedirectResponse
    {
        // The table refuses it too; this says why instead of failing.
        if ($group->faqs()->exists()) {
            return $this->toList('warning', 'Nhóm còn câu hỏi, hãy chuyển hoặc xoá các câu hỏi trước');
        }

        $group->delete();

        return $this->toList('success', 'Đã xoá nhóm');
    }

    private function screen(?FaqGroupModel $editing)
    {
        return view('backend.pages.faq.faq_groups', [
            'groups' => FaqGroupModel::withCount('faqs')->orderBy('group_position')->orderBy('group_id')->get(),
            'editing' => $editing,
            'nextPosition' => FaqGroupModel::nextPosition(),
        ]);
    }

    private function toList(string $icon, string $message): RedirectResponse
    {
        Session::flash('iconMessage', $icon);

        return redirect()->route('faq_group.index')->with('message', $message);
    }
}
