<?php

namespace App\Http\Controllers\Backend;

use App\Actions\SavePageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PageUpdateRequest;
use App\Models\PageModel;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The admin "Giới thiệu" screen: the about page, which is only ever edited.
 */
class AboutAdminController extends Controller
{
    private const NOT_FOUND = 'Không tồn tại nội dung';

    public function __construct(Request $request, private readonly SavePageAction $save)
    {
        View::share('keyword', $request->input('keyword'));
    }

    public function index()
    {
        $about = PageModel::about()->orderBy('page_id', 'desc')->paginate(5);

        return view('backend.pages.about.about_list', compact('about'));
    }

    public function edit(string $encryptedAboutId)
    {
        try {
            $about = PageModel::about()->find((int) Crypt::decrypt($encryptedAboutId));
        } catch (DecryptException) {
            return $this->toList(self::NOT_FOUND.'!');
        }

        return $about === null ? $this->toList(self::NOT_FOUND) : view('backend.pages.about.about_edit', compact('about'));
    }

    public function update(PageUpdateRequest $request, int $id): RedirectResponse
    {
        $about = PageModel::about()->find($id);

        if ($about === null) {
            return $this->toList('Không tồn tại chính sách');
        }

        $this->save->execute($about, $request->post(), visibleByDefault: true);
        Session::flash('iconMessage', 'success');

        return redirect()->route('about.index')->with('message', 'Chỉnh sửa thành công');
    }

    private function toList(string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'info');

        return redirect()->route('about.index')->with('message', $message);
    }
}
