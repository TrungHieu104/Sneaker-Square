<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\CartReminderModel;
use App\Services\CartReminders\CartRestorer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * The three links in a reminder email. Each is reached by the reminder's own
 * random token, which stands in for logging in: whoever has the email may use
 * them.
 */
class CartReminderController extends Controller
{
    use SharesStorefrontLayout;

    // A transparent 1×1 GIF.
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function restore(string $token, Request $request, CartRestorer $restorer): RedirectResponse
    {
        $restored = $restorer->restore($this->reminder($token), $request->session());

        Session::flash('iconMessage', $restored > 0 ? 'success' : 'info');

        return redirect()->route('product.cart')->with('message', $restored > 0
            ? 'Giỏ hàng của bạn đã được khôi phục.'
            : 'Rất tiếc, các sản phẩm trong giỏ đã hết hàng hoặc ngừng bán.');
    }

    /**
     * Counts as opened only when the mail client loads images, so the open
     * rate is a floor rather than the true figure.
     */
    public function opened(string $token): Response
    {
        CartReminderModel::where('token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);

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
        $this->reminder($token);
        $this->shareStorefrontLayout();

        return view('frontend.pages.cart_reminder_unsubscribe', [
            'token' => $token,
            'done' => false,
            'keyword' => $request->input('keyword'),
        ]);
    }

    public function unsubscribe(string $token, Request $request): View
    {
        $reminder = $this->reminder($token);
        DB::table('cart_reminder_opt_outs')->insertOrIgnore(['user_id' => $reminder->user_id, 'created_at' => now()]);
        $this->shareStorefrontLayout();

        return view('frontend.pages.cart_reminder_unsubscribe', [
            'token' => $token,
            'done' => true,
            'keyword' => $request->input('keyword'),
        ]);
    }

    private function reminder(string $token): CartReminderModel
    {
        return CartReminderModel::where('token', $token)->firstOrFail();
    }
}
