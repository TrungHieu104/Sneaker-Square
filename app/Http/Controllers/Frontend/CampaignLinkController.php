<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\CampaignRecipientModel;
use App\Models\CouponModel;
use App\Services\CartPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * The links in a campaign email. Each is reached by the recipient's own random
 * token, which stands in for logging in: whoever has the email may use them.
 */
class CampaignLinkController extends Controller
{
    use SharesStorefrontLayout;

    // A transparent 1×1 GIF.
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function visit(string $token, Request $request, CartPricingService $pricing): RedirectResponse
    {
        $recipient = $this->recipient($token);
        $recipient->clicked_at ??= now();
        $recipient->opened_at ??= $recipient->clicked_at;
        $recipient->save();

        $coupon = $recipient->coupon;
        $subtotal = $pricing->cartSubtotal((array) $request->session()->get('cart', []));
        $usable = $coupon !== null && $pricing->couponIsUsable($coupon, $subtotal);

        if ($usable) {
            $request->session()->put('coupon_data', $coupon);
        }

        Session::flash('iconMessage', $usable ? 'success' : 'info');

        return redirect()->route('product.page')->with('message', match (true) {
            $usable => 'Mã '.$coupon->coupon_code.' đã được áp sẵn cho đơn hàng của bạn.',
            $this->onlyShortOfMinimum($pricing, $coupon) => 'Mã '.$coupon->coupon_code.' dùng cho đơn có tiền hàng từ '.number_format((int) $coupon->coupon_min_order, 0, ',', '.').' VNĐ. Hãy nhập mã ở giỏ hàng khi đơn đủ điều kiện.',
            $coupon !== null && $pricing->couponBelongsToAnother($coupon) => CartPricingService::NOT_YOUR_COUPON,
            default => 'Rất tiếc, mã giảm giá này đã hết hạn hoặc đã được sử dụng.',
        });
    }

    /**
     * Asked with a cart already at the minimum, the coupon can only be refused
     * for being expired or used up.
     */
    private function onlyShortOfMinimum(CartPricingService $pricing, ?CouponModel $coupon): bool
    {
        return $coupon !== null
            && (int) $coupon->coupon_min_order > 0
            && $pricing->couponIsUsable($coupon, (int) $coupon->coupon_min_order);
    }

    /**
     * Counts as opened only when the mail client loads images, so the open
     * rate is a floor rather than the true figure.
     */
    public function opened(string $token): Response
    {
        CampaignRecipientModel::where('token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Asks first: link scanners in mail systems open every link in an email,
     * and a GET that unsubscribed would unsubscribe people who never clicked.
     */
    public function confirmUnsubscribe(string $token, Request $request): View
    {
        $this->recipient($token);
        $this->shareStorefrontLayout();

        return view('frontend.pages.campaign_unsubscribe', [
            'token' => $token,
            'done' => false,
            'keyword' => $request->input('keyword'),
        ]);
    }

    public function unsubscribe(string $token, Request $request): View
    {
        $recipient = $this->recipient($token);
        DB::table('campaign_opt_outs')->insertOrIgnore(['user_id' => $recipient->user_id, 'created_at' => now()]);
        $this->shareStorefrontLayout();

        return view('frontend.pages.campaign_unsubscribe', [
            'token' => $token,
            'done' => true,
            'keyword' => $request->input('keyword'),
        ]);
    }

    private function recipient(string $token): CampaignRecipientModel
    {
        return CampaignRecipientModel::where('token', $token)->firstOrFail();
    }
}
