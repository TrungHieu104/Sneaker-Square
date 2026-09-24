<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ShopSettingRequest;
use App\Services\ShopSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class ShopSettingController extends Controller
{
    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function edit(ShopSettings $settings): View
    {
        return view('backend.pages.setting.setting_edit', [
            'autoCompleteDays' => $settings->autoCompleteDays(),
            'returnDays' => $settings->returnDays(),
        ]);
    }

    public function update(ShopSettingRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setAutoCompleteDays((int) $request->validated('auto_complete_days'));
        $settings->setReturnDays((int) $request->validated('return_days'));

        Session::flash('iconMessage', 'success');

        return redirect()->route('setting.edit')->with('message', 'Đã lưu cấu hình.');
    }
}
