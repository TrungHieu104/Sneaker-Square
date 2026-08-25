<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\CouponModel;
use App\Models\NewsModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\StatisticModel;
use App\Models\UserModel;
use App\Models\VisitorModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The figures the admin dashboard shows.
 *
 * All of this was inline in DashboardController::index(), where the rule for
 * "an order that counts" was written out three separate times and the
 * seven-day revenue query was built twice in a row, the second overwriting the
 * first. The queries themselves are unchanged; they simply have names now.
 */
class DashboardStatisticsService
{
    /**
     * How recently a visitor must have been seen to count as online.
     */
    private const ONLINE_WINDOW_MINUTES = 10;

    private const REVENUE_CHART_DAYS = 7;

    /**
     * Record counts across the whole shop.
     *
     * @return array<string, int>
     */
    public function totals(): array
    {
        return [
            'totalNews' => NewsModel::count(),
            'totalPro' => ProductModel::count(),
            'totalAccountUser' => UserModel::where('user_role', 0)->count(),
            'totalAccountAdmin' => UserModel::where('user_role', 1)->count(),
            'totalOrder' => OrderModel::confirmedSale()->count(),
            'totalCoupon' => CouponModel::count(),
        ];
    }

    /**
     * New orders waiting to be dealt with.
     */
    public function newOrderCount(): int
    {
        return OrderModel::confirmedSale()
            ->where('order_status', OrderStatus::New->value)
            ->count();
    }

    public function todayOrderCount(): int
    {
        // whereDate rather than a plain comparison against a Carbon instance:
        // the latter binds a full timestamp, which matches a DATE column on
        // MySQL but not on every driver.
        return OrderModel::confirmedSale()
            ->whereDate('order_date', Carbon::today())
            ->count();
    }

    /**
     * @return array{totalCoupon: int, stillValid: int, expiredValid: int, couponPopular: ?CouponModel}
     */
    public function coupons(): array
    {
        $today = Carbon::today();

        return [
            'totalCoupon' => CouponModel::count(),
            'stillValid' => CouponModel::where('coupon_end', '>=', $today)->count(),
            'expiredValid' => CouponModel::where('coupon_end', '<', $today)->count(),
            'couponPopular' => CouponModel::orderBy('coupon_used', 'desc')->first(),
        ];
    }

    /**
     * This month's orders, broken down by where they have got to.
     *
     * @return array<string, int|float>
     */
    public function ordersThisMonth(): array
    {
        $month = now()->startOfMonth();

        return [
            'orderMonth' => OrderModel::whereMonth('order_date', $month)->count(),
            'revenueOrder' => StatisticModel::whereMonth('order_date', $month)->sum('sales'),
            'orderFail' => OrderModel::where('order_status', OrderStatus::Cancelled->value)
                ->whereMonth('order_date', $month)
                ->count(),
            'orderWaitDelivery' => OrderModel::whereMonth('order_date', $month)
                ->where('order_status', OrderStatus::New->value)
                ->orWhere('order_status', OrderStatus::Confirmed->value)
                ->where('order_delivery_status', 0)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->count(),
            'orderDelivering' => OrderModel::where('order_status', OrderStatus::Confirmed->value)
                ->where('order_delivery_status', 1)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->whereMonth('order_date', $month)
                ->count(),
            'orderDelivered' => OrderModel::where('order_status', OrderStatus::Completed->value)
                ->whereMonth('order_date', $month)
                ->count(),
        ];
    }

    public function totalVisitorCount(): int
    {
        return VisitorModel::count();
    }

    public function onlineVisitorCount(): int
    {
        return VisitorModel::where('visitor_date', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES))->count();
    }

    /**
     * The last week of revenue, shaped for the chart.
     *
     * @return array<int, array{period: string, total: mixed, sales: mixed, profit: mixed}>
     */
    public function revenueChart(): array
    {
        $from = Carbon::now('Asia/Ho_Chi_Minh')->subDays(self::REVENUE_CHART_DAYS)->toDateString();

        return StatisticModel::whereBetween('order_date', [$from, Carbon::today()])
            ->orderBy('order_date', 'ASC')
            ->get()
            ->map(fn ($item) => [
                'period' => date('d/m/Y', strtotime($item->order_date)),
                'total' => $item->total_order,
                'sales' => $item->sales,
                'profit' => $item->profit,
            ])
            ->all();
    }

    public function allRevenueRows(): Collection
    {
        return StatisticModel::get();
    }
}
