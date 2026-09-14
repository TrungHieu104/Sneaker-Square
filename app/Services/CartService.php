<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;

/**
 * Keeps the session cart in step with what the shop can still sell.
 *
 * The cart deliberately lives in the session rather than the database, so it
 * can hold products that were hidden, deleted or sold out since the customer
 * put them there. Every page that spends the cart screens it through here
 * first.
 *
 * The version this replaces had three faults: it read `->quantity` off a stock
 * row that may not exist, it called array_splice() while iterating the same
 * array with its own keys, and it stopped at the first problem so a second bad
 * line survived into checkout.
 */
class CartService
{
    /**
     * @param  array<int, array<string, mixed>>  $cart
     * @return array{cart: array<int, array<string, mixed>>, removed: bool, insufficient: bool}
     */
    public function screen(array $cart): array
    {
        $kept = [];
        $removed = false;
        $insufficient = false;

        foreach ($cart as $item) {
            $product = ProductModel::where('pro_slug', $item['proSlug'] ?? null)->first();

            if (! $product || (int) $product->pro_hidden === 0) {
                $removed = true;

                continue;
            }

            // A variant with no stock row has never been stocked, which counts
            // the same as having none left.
            $inStock = (int) (ProductQuantityModel::where('pro_id', $product->pro_id)
                ->where('color_id', $item['color_id'] ?? null)
                ->where('size_id', $item['size_id'] ?? null)
                ->value('quantity') ?? 0);

            if ((int) ($item['quantity'] ?? 0) > $inStock) {
                $insufficient = true;

                // Trimmed to what is left rather than thrown away. The customer is
                // told to reduce the quantity, so the line has to still be there to
                // reduce — dropping it emptied the cart and left the message
                // pointing at nothing.
                if ($inStock === 0) {
                    continue;
                }

                $item['quantity'] = $inStock;
            }

            $kept[] = $item;
        }

        return [
            // Re-indexed, because the views and the pricing service both walk
            // the cart as a plain list.
            'cart' => array_values($kept),
            'removed' => $removed,
            'insufficient' => $insufficient,
        ];
    }
}
