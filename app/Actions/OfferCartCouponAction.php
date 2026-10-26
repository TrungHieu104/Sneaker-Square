<?php

namespace App\Actions;

use App\Models\CartReminderModel;
use App\Services\CartReminders\AbandonedCarts;
use App\Services\CartReminders\CartReminderSender;
use App\Services\ShopSettings;

/**
 * Gives one customer a coupon for the cart they left, at an admin's request.
 *
 * The page the admin clicked on may be minutes old, so everything it showed
 * is checked again: the cart may have been bought, emptied or already given
 * a coupon by another admin since.
 */
class OfferCartCouponAction
{
    public function __construct(
        private readonly AbandonedCarts $carts,
        private readonly CartReminderSender $sender,
        private readonly ShopSettings $settings,
    ) {}

    /**
     * @param  array{percent: int, days: int, min_order: int, max_discount: ?int}  $terms
     * @return ?string why nothing was sent, or null once the email is queued
     */
    public function execute(int $userId, array $terms, int $adminId): ?string
    {
        $cart = $this->carts->stillOpen($userId, now()->subDays($this->settings->cartReminder()['max_age_days']));

        if ($cart === null) {
            return 'Khách đã đặt hàng hoặc bỏ hết sản phẩm khỏi giỏ.';
        }

        if (in_array($userId, $this->carts->optedOut(), true)) {
            return 'Khách đã huỷ nhận email nhắc giỏ hàng.';
        }

        if (CartReminderModel::where('cart_key', $cart->key)->where('step', CartReminderModel::OFFER)->exists()) {
            return 'Giỏ hàng này đã được tặng mã.';
        }

        return $this->sender->offer($cart, $terms, $adminId) === null
            ? 'Sản phẩm trong giỏ đã hết hàng.'
            : null;
    }
}
