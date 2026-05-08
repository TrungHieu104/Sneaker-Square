<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('order_details')->insert([
            // Bill 1
            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 36,
                'color' => 'Đen',
                'price' => 1909000,
                'quantity' => 2,
                'order_id' => 1,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 37,
                'color' => 'Trắng',
                'price' => 1909000,
                'quantity' => 4,
                'order_id' => 1,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Nike Air Zoom Pegasus 39',
                'size' => 38,
                'color' => 'Trắng',
                'price' => 3519000,
                'quantity' => 2,
                'order_id' => 1,
                'pro_id' => 2,
            ],

            [
                'pro_name' => 'Nike Air Zoom Pegasus 39',
                'size' => 40,
                'color' => 'Xanh dương',
                'price' => 3519000,
                'quantity' => 2,
                'order_id' => 1,
                'pro_id' => 2,
            ],

            // Bill 2
            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 36,
                'color' => 'Trắng',
                'price' => 1909000,
                'quantity' => 2,
                'order_id' => 2,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 37,
                'color' => 'Đen',
                'price' => 1909000,
                'quantity' => 4,
                'order_id' => 2,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Nike Air Zoom Pegasus 39',
                'size' => 38,
                'color' => 'Trắng',
                'price' => 3519000,
                'quantity' => 2,
                'order_id' => 2,
                'pro_id' => 2,
            ],

            [
                'pro_name' => 'Nike Air Zoom Pegasus 39',
                'size' => 40,
                'color' => 'Xanh dương',
                'price' => 3519000,
                'quantity' => 2,
                'order_id' => 2,
                'pro_id' => 2,
            ],

            // Bill 3
            [
                'pro_name' => 'Dây giày ALB002',
                'size' => NULL,
                'color' => 'Đen',
                'price' => 150000,
                'quantity' => 2,
                'order_id' => 3,
                'pro_id' => 12,
            ],

            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 37,
                'color' => 'Đen',
                'price' => 1909000,
                'quantity' => 4,
                'order_id' => 3,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Addidas Avryn',
                'size' => 38,
                'color' => 'Trắng',
                'price' => 2500000,
                'quantity' => 2,
                'order_id' => 3,
                'pro_id' => 5,
            ],

            [
                'pro_name' => 'Vớ Nữ Bitis Hunter ASWH00300',
                'size' => NULL,
                'color' => 'Xanh dương',
                'price' => 78000,
                'quantity' => 2,
                'order_id' => 3,
                'pro_id' => 11,
            ],

            // Bill 4
            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 36,
                'color' => 'Đen',
                'price' => 1909000,
                'quantity' => 2,
                'order_id' => 4,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Vớ Nữ Bitis Hunter ASWH00300',
                'size' => NULL,
                'color' => 'Đen',
                'price' => 78000,
                'quantity' => 2,
                'order_id' => 4,
                'pro_id' => 11,
            ],

            [
                'pro_name' => 'Nike React Escape Run 2',
                'size' => 38,
                'color' => 'Trắng',
                'price' => 1859000,
                'quantity' => 2,
                'order_id' => 4,
                'pro_id' => 3,
            ],

            [
                'pro_name' => 'Nike React Escape Run 2',
                'size' => 40,
                'color' => 'Xanh dương',
                'price' => 1859000,
                'quantity' => 2,
                'order_id' => 4,
                'pro_id' => 3,
            ],

            // Bill 5
            [
                'pro_name' => 'Dây giày ALB002',
                'size' => NULL,
                'color' => 'Trắng',
                'price' => 150000,
                'quantity' => 2,
                'order_id' => 5,
                'pro_id' => 12,
            ],

            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'size' => 37,
                'color' => 'Trắng',
                'price' => 1600000,
                'quantity' => 4,
                'order_id' => 5,
                'pro_id' => 1,
            ],

            [
                'pro_name' => 'Chai xịt tạo bọt vệ sinh giày Sneaker XIMO',
                'size' => NULL,
                'color' => NULL,
                'price' => 85000,
                'quantity' => 2,
                'order_id' => 5,
                'pro_id' => 13,
            ],

            [
                'pro_name' => 'Nike React Escape Run 2',
                'size' => 40,
                'color' => 'Xanh dương',
                'price' => 1859000,
                'quantity' => 2,
                'order_id' => 5,
                'pro_id' => 3,
            ],
        ]);
    }
}
