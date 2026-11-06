<?php

namespace App\Services;

use App\Models\CouponModel;
use Illuminate\Support\Str;

class OneUseCoupons
{
    // A percentage, in the coupon table's terms; 1 there is a fixed amount.
    private const PERCENT_OFF = 0;

    private const CODE_RANDOM_LENGTH = 6;

    /**
     * A code made for one email alone, whose use can be traced back to it. Given
     * an owner, CartPricingService refuses it to any other account.
     *
     * @param  array{percent: int, days: int, min_order: int, max_discount: ?int}  $terms
     */
    public function create(string $name, string $codePrefix, array $terms, ?int $ownerId = null): CouponModel
    {
        return CouponModel::create([
            'coupon_name' => $name,
            'coupon_code' => $codePrefix.Str::upper(Str::random(self::CODE_RANDOM_LENGTH)),
            'coupon_value' => $terms['percent'],
            'coupon_min_order' => $terms['min_order'],
            'coupon_max_discount' => $terms['max_discount'],
            'coupon_quantity' => 1,
            'coupon_used' => 0,
            'coupon_condition' => self::PERCENT_OFF,
            'coupon_user_id' => $ownerId,
            'coupon_start' => now()->toDateString(),
            'coupon_end' => now()->addDays($terms['days'])->toDateString(),
        ]);
    }
}
