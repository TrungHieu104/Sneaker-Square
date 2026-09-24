<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductQuantitySeeder extends Seeder
{
    /**
     * Stock rows, one per size and colour a shoe comes in.
     *
     * Only shoes get them. An accessory has neither a shoe size nor a colour
     * to choose, and the product page shows whatever variants it finds — so a
     * bottle of shoe shampoo with a stock row for size 42 is a bottle the shop
     * offers in size 42. This used to name the three accessory categories it
     * had to skip, by id, which held only until somebody added a fourth.
     */
    public function run(): void
    {
        $brandParent = DB::table('category')->where('cate_slug', 'thuong-hieu')->value('cate_id');

        $shoeCategories = DB::table('category')
            ->where('cate_parent_id', $brandParent)
            ->pluck('cate_id');

        $products = DB::table('products')->whereIn('cate_id', $shoeCategories)->get();
        $sizes = DB::table('size')->pluck('size_id');
        $colors = DB::table('color')->whereIn('color_id', [1, 2])->pluck('color_id');

        $rows = [];

        foreach ($products as $product) {
            foreach ($colors as $colorId) {
                foreach ($sizes as $sizeId) {
                    $rows[] = [
                        'pro_id' => $product->pro_id,
                        'size_id' => $sizeId,
                        'color_id' => $colorId,
                        'quantity' => 20,
                        'quantity_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('products_quantity')->insert($chunk);
        }
    }
}
