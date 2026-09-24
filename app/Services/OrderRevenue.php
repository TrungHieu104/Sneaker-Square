<?php

namespace App\Services;

use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\StatisticModel;
use Illuminate\Support\Carbon;

/**
 * Adds an order to the revenue report, or takes it back out.
 *
 * `order_revenue_counted` is what makes both directions safe to repeat: a
 * second completion, or a refund confirmed twice, finds the flag already in
 * the state it wants and does nothing. Callers must hold the order's row lock.
 */
class OrderRevenue
{
    public function record(OrderModel $order): void
    {
        if ($order->order_revenue_counted) {
            return;
        }

        [$sales, $profit, $lines] = $this->figures($order);
        $row = $this->rowFor($order);

        $row->sales = (int) $row->sales + $sales;
        $row->profit = (int) $row->profit + $profit;
        $row->order_total = (int) $row->order_total + $lines;
        $row->save();

        $order->order_revenue_counted = 1;
        $order->save();
    }

    public function reverse(OrderModel $order): void
    {
        if (! $order->order_revenue_counted) {
            return;
        }

        [$sales, $profit, $lines] = $this->figures($order);
        $row = $this->rowFor($order);

        // The columns are unsigned: a report edited by hand could otherwise be
        // driven below zero and fail the write.
        $row->sales = max(0, (int) $row->sales - $sales);
        $row->profit = max(0, (int) $row->profit - $profit);
        $row->order_total = max(0, (int) $row->order_total - $lines);
        $row->save();

        $order->order_revenue_counted = 0;
        $order->save();
    }

    /**
     * Takes back only what the customer sent home.
     *
     * A whole return is the old reverse(): the sale is undone and the order
     * stops counting. A partial one leaves a real sale standing — the pair the
     * customer kept — so the flag stays set and only the returned units come
     * off the report. Reversing the whole order there would credit the shop
     * with a loss it did not make.
     */
    public function reverseReturn(OrderModel $order, OrderReturnModel $return): void
    {
        if (! $order->order_revenue_counted) {
            return;
        }

        if (! $return->isPartial()) {
            $this->reverse($order);

            return;
        }

        [$sales, $profit, $lines] = $this->returnedFigures($order, $return);
        $row = $this->rowFor($order);

        $row->sales = max(0, (int) $row->sales - $sales);
        $row->profit = max(0, (int) $row->profit - $profit);
        $row->order_total = max(0, (int) $row->order_total - $lines);
        $row->save();
    }

    /**
     * The returned units at the price the customer paid, carrying their share
     * of the order's coupon so a discount is not given back twice.
     *
     * Only a line sent back in full stops being a line of the report: half a
     * line is still a line that was sold.
     *
     * @return array{int, int, int}
     */
    private function returnedFigures(OrderModel $order, OrderReturnModel $return): array
    {
        $ordered = OrderDetailModel::where('order_id', $order->order_id)->get()->keyBy('order_details_id');
        $orderGoods = $ordered->sum(fn ($line) => (int) $line->price * (int) $line->quantity);
        $coupon = (int) $order->order_coupon_value;

        $sales = 0;
        $margin = 0;
        $wholeLines = 0;

        foreach ($return->items as $item) {
            $line = $ordered[$item->order_details_id] ?? null;

            if (! $line) {
                continue;
            }

            $sales += (int) $line->price * $item->quantity;
            $margin += ((int) $line->price - (int) $line->capital_price) * $item->quantity;

            if ($item->quantity >= (int) $line->quantity) {
                $wholeLines++;
            }
        }

        $share = $orderGoods > 0 ? (int) round($coupon * $sales / $orderGoods) : 0;

        return [max(0, $sales - $share), max(0, $margin - $share), $wholeLines];
    }

    /**
     * Never recompute revenue or profit from the product's current price: that
     * is neither what the customer paid nor what the variant cost.
     *
     * `order_total` counts lines, not orders, because that is what the report
     * has always been fed; changing it here would split the history in two.
     *
     * @return array{int, int, int}
     */
    private function figures(OrderModel $order): array
    {
        $lines = OrderDetailModel::where('order_id', $order->order_id)->get();
        $coupon = (int) $order->order_coupon_value;

        $sales = $lines->sum(fn ($line) => (int) $line->price * (int) $line->quantity);
        $margin = $lines->sum(fn ($line) => ((int) $line->price - (int) $line->capital_price) * (int) $line->quantity);

        return [max(0, $sales - $coupon), max(0, $margin - $coupon), $lines->count()];
    }

    /**
     * Filed under the day the order was placed, not the day it completed:
     * that is how every existing row in the report was written.
     */
    private function rowFor(OrderModel $order): StatisticModel
    {
        $day = Carbon::parse($order->order_date)->toDateString();

        return StatisticModel::where('order_date', $day)->lockForUpdate()->first()
            ?? new StatisticModel(['order_date' => $day, 'sales' => 0, 'profit' => 0, 'order_total' => 0]);
    }
}
