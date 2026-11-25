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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The admin "Chính sách & Giới thiệu" screens: every row of `pages` in one list.
 * The about page is only ever edited; showing, hiding and the bin are for
 * policies alone, so those lookups stay limited to them.
 */
class PageAdminController extends Controller
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
        $type = PageType::tryFrom((string) $request->input('type'));

        $query = PageModel::query()
            ->when($type, fn ($q) => $q->where('page_type', $type?->value))
            // The about page is the only one of its kind, so it leads the list.
            ->orderByRaw('page_type = ? desc', [PageType::About->value])
            ->orderBy($orderBy, $orderType);

        $pages = $this->performSearch($query, $keyword, self::SEARCHABLE)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = PageModel::query()->toBase()->selectRaw('page_type, count(*) as total')->groupBy('page_type')->pluck('total', 'page_type');

        return view('backend.pages.page.page_list', compact('pages', 'counts', 'type', 'keyword', 'orderBy', 'orderType'));
    }

    public function create()
    {
        return view('backend.pages.page.page_create');
    }

    public function status(Request $request, int $id): JsonResponse
    {
        $policy = PageModel::policies()->find($id);

        if (! $policy) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $policy->page_hidden = (int) $request->input('page-status');
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
        $page = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::find($id));

        if ($page instanceof RedirectResponse) {
            return $page;
        }

        return $page === null
            ? $this->toList('info', self::NO_POLICY)
            : view('backend.pages.page.page_edit', compact('page'));
    }

    public function update(PageUpdateRequest $request, int $id): RedirectResponse
    {
        $page = PageModel::find($id);

        if ($page === null) {
            return $this->toList('info', self::NO_POLICY);
        }

        // The about page is always shown and its address is fixed: a form
        // value for either is ignored.
        $form = $page->page_type === PageType::About
            ? Arr::except($request->post(), ['status', 'slug'])
            : $request->post();

        $this->save->execute($page, $form, visibleByDefault: $page->page_type === PageType::About);

        return $this->toList('success', 'Chỉnh sửa thành công');
    }

    public function softDelete(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::policies()->find($id));

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

        $trashed = $this->performSearch(PageModel::policies()->onlyTrashed()->orderBy($orderBy, $orderType), $keyword, self::SEARCHABLE)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('backend.pages.page.page_trash', compact('trashed', 'keyword', 'orderBy', 'orderType'));
    }

    public function restore(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::policies()->withTrashed()->find($id));

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
        $binned = PageModel::policies()->onlyTrashed()->get();

        if ($binned->isEmpty()) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', self::BIN_EMPTY);
        }

        // One by one, not one bulk update: each restore must fire the model
        // events that refresh the storefront cache.
        $binned->each(fn (PageModel $policy) => $policy->restore());
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Hoàn tác thành công');
    }

    public function forceDelete(Request $request, string $encryptedPageId): RedirectResponse
    {
        $policy = $this->decrypted($encryptedPageId, fn (int $id) => PageModel::policies()->onlyTrashed()->find($id));

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
        if (PageModel::policies()->onlyTrashed()->doesntExist()) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', self::BIN_EMPTY);
        }

        PageModel::policies()->onlyTrashed()->forceDelete();
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

        return redirect()->route('page.index')->with('message', $message);
    }
}
