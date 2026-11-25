<?php

namespace App\Http\Controllers\Backend;

use App\Enums\CustomerSegment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AssistantSettingRequest;
use App\Http\Requests\Backend\CartReminderSettingRequest;
use App\Http\Requests\Backend\SegmentOfferSettingRequest;
use App\Http\Requests\Backend\ShopInfoRequest;
use App\Http\Requests\Backend\ShopSettingRequest;
use App\Services\ShopSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Spatie\ResponseCache\Facades\ResponseCache;

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
            'paymentWindow' => $settings->paymentWindowMinutes(),
            'shopInfo' => $settings->shopInfo(),
            'assistantModel' => $settings->assistantModel(),
            'assistantIdleHours' => $settings->assistantIdleHours(),
            'assistantReady' => filled(config('services.anthropic.key')),
            'cartReminder' => $settings->cartReminder(),
            'cashbackEnabled' => $settings->segmentCashbackEnabled(),
            'segmentOffers' => collect(CustomerSegment::cases())->mapWithKeys(fn (CustomerSegment $segment) => [$segment->value => $settings->segmentOffer($segment)])->all(),
        ]);
    }

    public function update(ShopSettingRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setAutoCompleteDays((int) $request->validated('auto_complete_days'));
        $settings->setReturnDays((int) $request->validated('return_days'));
        $settings->setPaymentWindowMinutes((int) $request->validated('payment_window_minutes'));

        return $this->saved('#tab-don-hang', 'Đã lưu cấu hình.');
    }

    public function updateGeneral(ShopInfoRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setShopInfo($request->validated());

        // Every storefront page prints these, and the response cache would
        // keep serving the old address for up to ten minutes.
        ResponseCache::clear();

        return $this->saved('#tab-chung', 'Đã lưu thông tin cửa hàng.');
    }

    public function updateAssistant(AssistantSettingRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setAssistantModel((string) $request->validated('assistant_model'));
        $settings->setAssistantIdleHours((int) $request->validated('idle_close_hours'));

        return $this->saved('#tab-tro-ly', 'Đã lưu cấu hình trợ lý AI.');
    }

    public function updateSegmentOffers(SegmentOfferSettingRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setSegmentOffers($request->offers());
        $settings->setSegmentCashbackEnabled($request->boolean('cashback_enabled'));

        return $this->saved('#tab-phan-khuc', 'Đã lưu ưu đãi theo phân khúc.');
    }

    public function updateCartReminder(CartReminderSettingRequest $request, ShopSettings $settings): RedirectResponse
    {
        $settings->setCartReminder($request->validated());

        return $this->saved('#tab-nhac-gio-hang', 'Đã lưu cấu hình nhắc giỏ hàng.');
    }

    /**
     * Each form comes back to its own tab, so saving one section does not
     * land the admin on the tab that leads.
     */
    private function saved(string $tab, string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'success');

        return redirect()->to(route('setting.edit').$tab)->with('message', $message);
    }
}
