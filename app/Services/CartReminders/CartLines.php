<?php

namespace App\Services\CartReminders;

use App\Models\ColorModel;
use App\Models\ProductModel;
use App\Models\SizeModel;
use App\Services\CartPricingService;

/**
 * Turns the pairs a reminder stores into what can still be sold of them,
 * priced as the shop prices them today.
 */
class CartLines
{
    public function __construct(private readonly CartPricingService $pricing) {}

    /**
     * Hidden, deleted and sold-out pairs drop out; a quantity beyond what is
     * left is cut down to it.
     *
     * @param  array<int, array{pro_id: int, size_id: ?int, color_id: ?int, quantity: int}>  $lines
     * @return list<array{product: ProductModel, size_id: ?int, color_id: ?int, size: ?string, color: ?string, quantity: int, price: int}>
     */
    public function sellable(array $lines): array
    {
        $products = ProductModel::with('getQuantities')
            ->whereIn('pro_id', array_column($lines, 'pro_id'))
            ->where('pro_hidden', 1)
            ->get()
            ->keyBy('pro_id');
        $sizes = SizeModel::pluck('size', 'size_id');
        $colors = ColorModel::pluck('color_vn', 'color_id');
        $sellable = [];

        foreach ($lines as $line) {
            $product = $products->get($line['pro_id']);
            $variant = $product?->getQuantities
                ->where('size_id', $line['size_id'])
                ->where('color_id', $line['color_id'])
                ->first();

            if ($product === null || $variant === null || (int) $variant->quantity <= 0) {
                continue;
            }

            $sellable[] = [
                'product' => $product,
                'size_id' => $line['size_id'],
                'color_id' => $line['color_id'],
                'size' => $sizes[$line['size_id']] ?? null,
                'color' => $colors[$line['color_id']] ?? null,
                'quantity' => min((int) $line['quantity'], (int) $variant->quantity),
                'price' => $this->pricing->unitPrice($product, $line['color_id'], $line['size_id']),
            ];
        }

        return $sellable;
    }

    /**
     * @param  array<int, array{quantity: int, price: int}>  $sellable
     */
    public function value(array $sellable): int
    {
        return array_sum(array_map(fn (array $line) => $line['quantity'] * $line['price'], $sellable));
    }
}
