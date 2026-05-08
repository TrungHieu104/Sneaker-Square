<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductQuantitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $getProduct = DB::table('products')
                            -> where('cate_id', '!=', 6)
                            -> where('cate_id', '!=', 7)
                            -> where('cate_id', '!=', 8)
                            -> get();
                            
        $getSize = DB::table('size')
                        -> get();

        $getColor = DB::table('color')  
                        -> where('color_id', 1)
                        -> orWhere('color_id', 2) 
                        -> get();

        $quantity = 20;

        foreach($getProduct as $product) {
            foreach($getColor as $color) {
                foreach($getSize as $size) {
                    DB::table('products_quantity')->insert([
                        [
                            'pro_id' => $product->pro_id, 
                            'size_id' => $size->size_id, 
                            'color_id' => $color->color_id, 
                            'quantity' => $quantity,
                            'quantity_date' => Now(),
                            'created_at' => Now(),
                            'updated_at' => Now(),
                        ],
                    ]);
                }
            }
        }
    }
}
