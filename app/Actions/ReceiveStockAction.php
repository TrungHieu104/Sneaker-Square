<?php

namespace App\Actions;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Support\Facades\DB;

/**
 * Books one delivery into stock, all of it or none of it.
 */
class ReceiveStockAction
{
    /**
     * @param  list<array{size_id: mixed, color_id: mixed, quantity: int, prices: array<string, mixed>}>  $lines
     */
    public function execute(int $proId, string $quantityDate, array $lines): void
    {
        DB::transaction(function () use ($proId, $quantityDate, $lines) {
            // Two deliveries of one product wait for each other here, so they
            // cannot both create the same variant.
            ProductModel::whereKey($proId)->lockForUpdate()->first();

            foreach ($lines as $line) {
                $this->receive($proId, $quantityDate, $line);
            }
        });
    }

    /**
     * A blank price box leaves the variant's price alone. Clearing a price is
     * done on the stock screen, where blank means "follow the product" instead.
     *
     * @param  array{size_id: mixed, color_id: mixed, quantity: int, prices: array<string, mixed>}  $line
     */
    private function receive(int $proId, string $quantityDate, array $line): void
    {
        $columns = ['quantity_date' => $quantityDate];

        foreach ($line['prices'] as $column => $value) {
            if ($value !== null && $value !== '') {
                $columns[$column] = (int) $value;
            }
        }

        $variant = ProductQuantityModel::where('pro_id', $proId)
            ->where('size_id', $line['size_id'])
            ->where('color_id', $line['color_id'])
            ->first();

        if ($variant) {
            // Added in the statement, so an order taking stock at the same
            // moment is not written over.
            $variant->increment('quantity', $line['quantity'], $columns);

            return;
        }

        ProductQuantityModel::create($columns + [
            'pro_id' => $proId,
            'size_id' => $line['size_id'],
            'color_id' => $line['color_id'],
            'quantity' => $line['quantity'],
        ]);
    }
}
