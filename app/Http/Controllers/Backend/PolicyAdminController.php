<?php

namespace App\Http\Controllers\Backend;

use App\Actions\SavePageAction;
use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PageRequest;
use App\Http\Requests\Backend\PageUpdateRequest;
use App\Models\PageModel;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The admin "Chính sách" screens: the policy pages, their bin and their
 * show/hide switch. The about page lives in the same table but has its own screen.
 */
class PolicyAdminController extends Controller
{
    private const PER_PAGE = 10;

    private const NOT_FOUND = 'Không tồn tại nội dung!';

    private const NO_POLICY = 'Không tồn tại chính sách';

    private const BIN_EMPTY = 'Không có dữ liệu trong thùng rác!';

    private const DELETED = 'Xóa thành công';

    private const SEARCHABLE = ['page_name'];

    public function __construct(Request $request, private readonly SavePageAction $save)
    {
        View::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request)
    {
        [$orderBy, $orderType] = $this->listingSort($request, PageModel::class, 'page_id');
        $keyword = $request->input('keyword');

        $policies = $this->performSearch(PageModel::policies()->orderBy($orderBy, $orderType), $keyword, self::SEARCHABLE)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('backend.pages.policy.policy_list', compact('policies', 'keyword', 'orderBy', 'orderType'));
    }

    public function create()
    {
        return view('backend.pages.policy.policy_create');
    }

    public function status(Request $request, int $id): JsonResponse
    {
        $policy = PageModel::find($id);

        if (! $policy) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $policy->page_hidden = (int) $request->input('policy-status');
        $policy->save();

        return response()->json(['message' => 'Cập nhật thành công']);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $this->save->execute(new PageModel(['page_type' => PageType::Policy]), $request->post(), visibleByDefault: false);

        return $this->toList('success', 'Thêm thành công');
    }

    public function edit(Request $request, string $encryptedPageId)
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::find($id));

        if ($policy instanceof RedirectResponse) {
            return $policy;
        }

        return $policy === null
            ? $this->toList('info', self::NO_POLICY)
            : view('backend.pages.policy.policy_edit', compact('policy'));
    }

    public function update(PageUpdateRequest $request, int $id): RedirectResponse
    {
        $policy = PageModel::find($id);

        if ($policy === null) {
            return $this->toList('info', self::NO_POLICY);
        }

        $this->save->execute($policy, $request->post(), visibleByDefault: false);

        return $this->toList('success', 'Chỉnh sửa thành công');
    }

    public function softDelete(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::find($id));

        if ($policy instanceof RedirectResponse) {
            return $policy;
        }

        if ($policy === null) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', self::NO_POLICY);
        }

        $policy->delete();
        Session::flash('iconMessage', 'success');

        return back()->with('message', self::DELETED);
    }

    public function trashed(Request $request)
    {
        [$orderBy, $orderType] = $this->listingSort($request, PageModel::class, 'page_id');
        $keyword = $request->input('keyword');

        $trashed = $this->performSearch(PageModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, self::SEARCHABLE)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('backend.pages.policy.policy_trash', compact('trashed', 'keyword', 'orderBy', 'orderType'));
    }

    public function restore(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::withTrashed()->find($id));

        if ($policy instanceof RedirectResponse) {
            return $policy;
        }

        abort_if($policy === null, 404);
        $policy->restore();
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Khôi phục thành công');
    }

    public function restoreAll(): RedirectResponse
    {
        if (PageModel::onlyTrashed()->count() === 0) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', self::BIN_EMPTY);
        }

        PageModel::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Hoàn tác thành công');
    }

    public function forceDelete(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::withTrashed()->find($id));

        if ($policy instanceof RedirectResponse) {
            return $policy;
        }

        abort_if($policy === null, 404);
        $policy->forceDelete();
        Session::flash('iconMessage', 'success');

        return back()->with('message', self::DELETED);
    }

    public function deleteAll(): RedirectResponse
    {
        if (PageModel::onlyTrashed()->count() === 0) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', self::BIN_EMPTY);
        }

        PageModel::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Xóa dữ liệu thành công!');
    }

    /**
     * The page behind an id from a link, or the way back to the list when the
     * id was tampered with.
     *
     * @param  callable(int): ?PageModel  $find
     */
    private function decrypted(string $encryptedId, callable $find): PageModel|RedirectResponse|null
    {
        try {
            return $find((int) Crypt::decrypt($encryptedId));
        } catch (DecryptException) {
            return $this->toList('info', self::NOT_FOUND);
        }
    }

    private function toList(string $icon, string $message): RedirectResponse
    {
        Session::flash('iconMessage', $icon);

        return redirect()->route('policy.index')->with('message', $message);
    }
}
