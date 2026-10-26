<?php

namespace App\Services\CartReminders;

use App\Models\CartReminderModel;
use App\Models\ColorModel;
use App\Models\ProductModel;
use App\Models\SizeModel;
use App\Models\UserModel;
use App\Services\ShopSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The carts customers have left open, as the admin sees them, and which of
 * them are worth a coupon.
 *
 * A coupon is suggested only to a customer the automatic reminders have
 * already run out on, who opened one of them, and whose cart is large enough
 * for the discount to pay for itself. Someone who never opens the emails will
 * not open the coupon either, and one who gets a coupon every time learns to
 * wait for it, hence the cooldown.
 */
class OpenCartBoard
{
    public const SORT_VALUE = 'gia-tri';

    public const SORT_RECENT = 'moi-nhat';

    // The minimum order an offer starts from: most of the cart, so the coupon
    // is spent on that cart and not on one cheap pair, with room for the
    // customer to drop an item or a size to sell out.
    private const MIN_ORDER_SHARE = 90;

    private const MIN_ORDER_STEP = 100_000;

    public function __construct(
        private readonly AbandonedCarts $carts,
        private readonly CartLines $lines,
        private readonly ShopSettings $settings,
    ) {}

    /**
     * @return list<array{cart: AbandonedCart, user: UserModel, lines: list<array{product: ProductModel, size_id: ?int, color_id: ?int, size: ?string, color: ?string, quantity: int, price: int}>, value: int, reminders: Collection<int, CartReminderModel>, optedOut: bool, lastOffer: ?Carbon, reasons: list<string>, minOrder: int}>
     */
    public function rows(string $sort = self::SORT_VALUE, bool $suggestedOnly = false): array
    {
        $config = $this->settings->cartReminder();
        $carts = $this->carts->open(now()->subDays($config['max_age_days']));
        $userIds = array_map(fn (AbandonedCart $cart) => $cart->userId, $carts);

        $users = UserModel::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $reminders = CartReminderModel::whereIn('cart_key', array_map(fn (AbandonedCart $cart) => $cart->key, $carts))
            ->get()
            ->groupBy('cart_key');
        $lastOffers = CartReminderModel::where('step', CartReminderModel::OFFER)
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, max(sent_at) as last_sent')
            ->pluck('last_sent', 'user_id');
        $optedOut = $this->carts->optedOut();
        $rows = [];

        foreach ($carts as $cart) {
            $sellable = $this->lines->sellable($cart->lines);
            // A plain collection: Eloquent's only() and except() go by primary key.
            $sent = ($reminders->get($cart->key) ?? collect())->keyBy('step')->toBase();
            $lastOffer = $lastOffers->has($cart->userId) ? Carbon::parse($lastOffers[$cart->userId]) : null;
            $row = [
                'cart' => $cart,
                'user' => $users[$cart->userId],
                'lines' => $sellable,
                'value' => $this->lines->value($sellable),
                'reminders' => $sent,
                'optedOut' => in_array($cart->userId, $optedOut, true),
                'lastOffer' => $lastOffer,
            ];
            $row['reasons'] = $this->reasons($row, $config);
            $row['minOrder'] = intdiv($row['value'] * self::MIN_ORDER_SHARE, 100 * self::MIN_ORDER_STEP) * self::MIN_ORDER_STEP;

            if (! $suggestedOnly || $row['reasons'] !== []) {
                $rows[] = $row;
            }
        }

        usort($rows, $sort === self::SORT_RECENT
            ? fn (array $a, array $b) => $b['cart']->lastActivity <=> $a['cart']->lastActivity
            : fn (array $a, array $b) => $b['value'] <=> $a['value']);

        return $rows;
    }

    /**
     * Where each product stands in the shop right now, priced the way the
     * storefront prices it, so the admin can see whether the cart can still be
     * filled before offering anything.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, array{product: ProductModel, variants: list<array{color_id: ?int, size_id: ?int, color: ?string, size: ?string, quantity: int, price: int, list_price: int}>}>
     */
    public function stock(array $productIds): array
    {
        $sizes = SizeModel::pluck('size', 'size_id');
        $colors = ColorModel::pluck('color_vn', 'color_id');
        $stock = [];

        foreach (ProductModel::withPriceRange()->with('getQuantities')->whereIn('pro_id', $productIds)->get() as $product) {
            $variants = $product->getQuantities
                ->map(fn ($variant) => [
                    'color_id' => $variant->color_id === null ? null : (int) $variant->color_id,
                    'size_id' => $variant->size_id === null ? null : (int) $variant->size_id,
                    'color' => $colors[$variant->color_id] ?? null,
                    'size' => $sizes[$variant->size_id] ?? null,
                    'quantity' => (int) $variant->quantity,
                    'price' => $variant->sellingPrice($product),
                    'list_price' => $variant->listPrice($product),
                ])
                ->sortBy([['color', 'asc'], fn (array $a, array $b) => (int) $a['size'] <=> (int) $b['size']])
                ->values()
                ->all();

            $stock[(int) $product->pro_id] = ['product' => $product, 'variants' => $variants];
        }

        return $stock;
    }

    /**
     * Why a coupon is worth offering this cart, or nothing when it is not.
     *
     * @param  array{value: int, reminders: Collection<int, CartReminderModel>, optedOut: bool, lastOffer: ?Carbon}  $row
     * @param  array{second_enabled: bool, offer_min_value: int, offer_cooldown_days: int}  $config
     * @return list<string>
     */
    private function reasons(array $row, array $config): array
    {
        $automatic = $row['reminders']->only([CartReminderModel::FIRST, CartReminderModel::SECOND]);
        $owed = $config['second_enabled'] ? 2 : 1;

        if ($row['optedOut']
            || $row['reminders']->has(CartReminderModel::OFFER)
            || $row['value'] < $config['offer_min_value']
            || $automatic->count() < $owed
            || $automatic->whereNotNull('opened_at')->isEmpty()
            || $row['lastOffer']?->gt(now()->subDays($config['offer_cooldown_days']))) {
            return [];
        }

        return [
            'Đã nhắc '.$automatic->count().' lần, chưa mua',
            $automatic->whereNotNull('clicked_at')->isNotEmpty() ? 'Có bấm quay lại giỏ' : 'Có mở email',
            'Giỏ từ '.number_format($config['offer_min_value'], 0, ',', '.').'đ',
        ];
    }
}
