<?php

namespace App\Services\CartReminders;

use App\Mail\CartReminderMail;
use App\Models\CartReminderModel;
use App\Models\CouponModel;
use App\Models\UserModel;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CartReminderSender
{
    private const TOKEN_LENGTH = 40;

    // A percentage, in the coupon table's terms; 1 there is a fixed amount.
    private const PERCENT_OFF = 0;

    public function __construct(private readonly CartLines $lines) {}

    /**
     * Records the reminder and queues its email. Nothing is sent when none of
     * the cart can be bought any more: a reminder about sold-out shoes only
     * annoys.
     */
    public function send(AbandonedCart $cart, int $step): ?CartReminderModel
    {
        return $this->deliver($cart, ['step' => $step]);
    }

    /**
     * The coupon an admin chose to give this one customer, on the terms
     * chosen for this cart, sent at once rather than waiting for a reminder
     * that may never come.
     *
     * @param  array{percent: int, days: int, min_order: int, max_discount: ?int}  $terms
     */
    public function offer(AbandonedCart $cart, array $terms, int $adminId): ?CartReminderModel
    {
        return $this->deliver(
            $cart,
            ['step' => CartReminderModel::OFFER, 'sent_by' => $adminId],
            fn (CartReminderModel $reminder) => $this->couponFor($reminder, $terms),
        );
    }

    /**
     * @param  array{step: int, sent_by?: int}  $fields
     * @param  (Closure(CartReminderModel): CouponModel)|null  $coupon
     */
    private function deliver(AbandonedCart $cart, array $fields, ?Closure $coupon = null): ?CartReminderModel
    {
        $user = UserModel::find($cart->userId);
        $sellable = $this->lines->sellable($cart->lines);

        if ($user === null || ! $user->email || $sellable === []) {
            return null;
        }

        $reminder = DB::transaction(function () use ($cart, $fields, $sellable, $coupon) {
            $reminder = CartReminderModel::create($fields + [
                'user_id' => $cart->userId,
                'cart_key' => $cart->key,
                'lines' => $cart->lines,
                'cart_value' => $this->lines->value($sellable),
                'token' => Str::random(self::TOKEN_LENGTH),
                'sent_at' => now(),
            ]);

            if ($coupon !== null) {
                $reminder->coupon_id = $coupon($reminder)->coupon_id;
                $reminder->save();
            }

            return $reminder;
        });

        Mail::to($user->email)->queue(new CartReminderMail($reminder));

        return $reminder;
    }

    /**
     * A one-use code made for this email alone, so it cannot be passed around
     * and its use can be traced back to it.
     *
     * @param  array{percent: int, days: int, min_order: int, max_discount: ?int}  $terms
     */
    private function couponFor(CartReminderModel $reminder, array $terms): CouponModel
    {
        return CouponModel::create([
            'coupon_name' => 'Tặng mã giỏ hàng #'.$reminder->id,
            'coupon_code' => 'GIOHANG'.Str::upper(Str::random(6)),
            'coupon_value' => $terms['percent'],
            'coupon_min_order' => $terms['min_order'],
            'coupon_max_discount' => $terms['max_discount'],
            'coupon_quantity' => 1,
            'coupon_used' => 0,
            'coupon_condition' => self::PERCENT_OFF,
            'coupon_start' => now()->toDateString(),
            'coupon_end' => now()->addDays($terms['days'])->toDateString(),
        ]);
    }
}
