<?php

namespace App\Services\CustomerSegments;

use App\Enums\CustomerSegment;
use App\Models\CustomerSegmentModel;
use App\Models\OrderCashbackModel;
use App\Models\OrderModel;
use App\Services\ShopSettings;

/**
 * How much of an order goes back into the customer's wallet.
 *
 * Worked out on the goods after any coupon, so a customer is not paid back on
 * money they never spent and the shop does not pay back on shipping.
 */
class Cashback
{
    // The nightly run comes after the return window, so a day on top of it.
    private const PAYOUT_DELAY_DAYS = 1;

    public function __construct(private readonly ShopSettings $settings) {}

    /**
     * @return array{segment: CustomerSegment, percent: int, cap: ?int, amount: int}|null
     */
    public function quote(int $userId, int $goodsValue): ?array
    {
        if (! $this->settings->segmentCashbackEnabled()) {
            return null;
        }

        $segment = CustomerSegmentModel::find($userId)?->segment;

        if ($segment === null) {
            return null;
        }

        $offer = $this->settings->segmentOffer($segment);
        $amount = (int) round($goodsValue * $offer['cashback'] / 100);

        if ($offer['cap'] !== null) {
            $amount = min($amount, $offer['cap']);
        }

        if ($amount <= 0) {
            return null;
        }

        return ['segment' => $segment, 'percent' => $offer['cashback'], 'cap' => $offer['cap'], 'amount' => $amount];
    }

    /**
     * Fixes the promise at the moment the order is placed.
     */
    public function promise(OrderModel $order, int $goodsValue): void
    {
        $quote = $this->quote((int) $order->user_id, $goodsValue);

        if ($quote !== null) {
            OrderCashbackModel::create(['order_id' => $order->order_id, 'segment' => $quote['segment']->value] + $quote);
        }
    }

    /**
     * What the customer is told. Days rather than the reason for them: which
     * orders pay out and when is the shop's own rule.
     */
    public function payoutDays(): int
    {
        return $this->settings->returnDays() + self::PAYOUT_DELAY_DAYS;
    }
}
