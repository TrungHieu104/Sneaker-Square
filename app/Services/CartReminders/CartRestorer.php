<?php

namespace App\Services\CartReminders;

use App\Models\CartReminderModel;
use App\Services\CartPricingService;
use Illuminate\Contracts\Session\Session;

/**
 * What the reminder's button does: put the cart the email showed back into
 * the session, and apply the reminder's coupon if it can still be used.
 */
class CartRestorer
{
    public function __construct(
        private readonly CartLines $lines,
        private readonly CartPricingService $pricing,
    ) {}

    /**
     * Merged into whatever the session cart already holds; a pair in both
     * keeps the larger quantity rather than adding the two.
     *
     * @return int the number of lines restored
     */
    public function restore(CartReminderModel $reminder, Session $session): int
    {
        $reminder->clicked_at ??= now();
        $reminder->opened_at ??= $reminder->clicked_at;
        $reminder->save();

        $sellable = $this->lines->sellable($reminder->lines);
        $cart = (array) $session->get('cart', []);

        foreach ($sellable as $line) {
            $index = collect($cart)->search(fn (array $item) => ($item['proSlug'] ?? null) === $line['product']->pro_slug
                && (string) ($item['size_id'] ?? '') === (string) $line['size_id']
                && (string) ($item['color_id'] ?? '') === (string) $line['color_id']);

            if ($index !== false) {
                $cart[$index]['quantity'] = max((int) $cart[$index]['quantity'], $line['quantity']);

                continue;
            }

            $cart[] = [
                'proSlug' => $line['product']->pro_slug,
                'pro_name' => $line['product']->pro_name,
                'quantity' => $line['quantity'],
                'color_id' => $line['color_id'],
                'size_id' => $line['size_id'],
                'pro_price' => $line['price'],
            ];
        }

        $session->put('cart', $cart);

        $subtotal = array_sum(array_map(fn (array $item) => (int) $item['pro_price'] * (int) $item['quantity'], $cart));

        if ($reminder->coupon && $this->pricing->couponIsUsable($reminder->coupon, $subtotal)) {
            $session->put('coupon_data', $reminder->coupon);
        }

        return count($sellable);
    }
}
