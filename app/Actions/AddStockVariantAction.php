<?php

namespace App\Actions;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Support\Facades\DB;

/**
 * Adds one size and colour of a product that already has stock.
 */
class AddStockVariantAction
{
    /**
     * @param  array{size_id: ?int, color_id: ?int, quantity: int, quantity_date: string, pro_price: ?int, pro_price_sale: ?int, capital_price: ?int}  $variant
     * @return bool false when that size and colour is already in stock
     */
    public function execute(int $proId, array $variant): bool
    {
        return DB::transaction(function () use ($proId, $variant) {
            // The lock a delivery takes too. Checked again under it because the
            // unique index lets a second row with a null size or colour through.
            ProductModel::whereKey($proId)->lockForUpdate()->first();

            $exists = ProductQuantityModel::where('pro_id', $proId)
                ->where('size_id', $variant['size_id'])
                ->where('color_id', $variant['color_id'])
                ->exists();

            if ($exists) {
                return false;
            }

            ProductQuantityModel::create(['pro_id' => $proId] + $variant);

            return true;
        });
    }
}
