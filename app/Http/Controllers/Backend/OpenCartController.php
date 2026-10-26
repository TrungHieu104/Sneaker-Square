<?php

namespace App\Http\Controllers\Backend;

use App\Actions\OfferCartCouponAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CartCouponOfferRequest;
use App\Services\CartReminders\OpenCartBoard;
use App\Services\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * The carts customers have left open, and the coupon an admin can choose to
 * give one of them.
 */
class OpenCartController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request, OpenCartBoard $board, ShopSettings $settings): View
    {
        $sort = $request->input('sap-xep') === OpenCartBoard::SORT_RECENT ? OpenCartBoard::SORT_RECENT : OpenCartBoard::SORT_VALUE;
        $suggestedOnly = $request->boolean('de-xuat');
        $rows = collect($board->rows($sort, $suggestedOnly));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $shown = $rows->forPage($page, self::PER_PAGE)->values();

        return view('backend.pages.order.open_carts', [
            'carts' => (new LengthAwarePaginator($shown, $rows->count(), self::PER_PAGE, $page, [
                'path' => $request->url(),
            ]))->withQueryString(),
            'stock' => $board->stock($shown->flatMap(fn (array $row) => array_map(fn (array $line) => (int) $line['product']->pro_id, $row['lines']))->unique()->all()),
            'total' => $rows->sum('value'),
            'suggested' => $rows->where('reasons', '!=', [])->count(),
            'sort' => $sort,
            'suggestedOnly' => $suggestedOnly,
            'config' => $settings->cartReminder(),
        ]);
    }

    public function offer(CartCouponOfferRequest $request, OfferCartCouponAction $action, int $user_id): RedirectResponse
    {
        $refused = $action->execute($user_id, [
            'percent' => $request->integer('coupon_percent'),
            'days' => $request->integer('coupon_days'),
            'min_order' => $request->integer('min_order'),
            'max_discount' => $request->filled('max_discount') ? $request->integer('max_discount') : null,
        ], (int) $request->user()?->user_id);

        Session::flash('iconMessage', $refused === null ? 'success' : 'error');

        return back()->with('message', $refused ?? 'Đã tặng mã và gửi email cho khách.');
    }
}
