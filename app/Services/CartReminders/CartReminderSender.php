<?php

namespace App\Services\CartReminders;

use App\Mail\CartReminderMail;
use App\Models\CartReminderModel;
use App\Models\CouponModel;
use App\Models\UserModel;
use App\Services\OneUseCoupons;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CartReminderSender
{
    private const TOKEN_LENGTH = 40;

    public function __construct(
        private readonly CartLines $lines,
        private readonly OneUseCoupons $coupons,
    ) {}

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
            fn (CartReminderModel $reminder) => $this->coupons->create('Tặng mã giỏ hàng #'.$reminder->id, 'GIOHANG', $terms),
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
}
