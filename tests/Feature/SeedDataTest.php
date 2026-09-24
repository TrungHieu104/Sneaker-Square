<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\ColorSeeder;
use Database\Seeders\ProductQuantitySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SizeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The demo data the shop is shown with.
 *
 * A stock row is what the product page reads to decide which sizes and colours
 * to offer, so seeded rows are not free scenery: a row for size 42 on a bottle
 * of shoe shampoo is a size the customer can pick.
 */
class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, ColorSeeder::class, SizeSeeder::class, ProductSeeder::class]);
        $this->seed(ProductQuantitySeeder::class);
    }

    /**
     * @return Collection<int, int>
     */
    private function danhMucCon(string $slug)
    {
        return DB::table('category')
            ->where('cate_parent_id', DB::table('category')->where('cate_slug', $slug)->value('cate_id'))
            ->pluck('cate_id');
    }

    public function test_giay_duoc_phat_du_size_va_mau(): void
    {
        $soSize = DB::table('size')->count();
        $giay = DB::table('products')
            ->whereIn('cate_id', $this->danhMucCon('thuong-hieu'))
            ->first();

        $this->assertSame(
            $soSize * 2,
            DB::table('products_quantity')->where('pro_id', $giay->pro_id)->count(),
        );
    }

    public function test_phu_kien_khong_co_size_giay(): void
    {
        $phuKien = DB::table('products')
            ->whereIn('cate_id', $this->danhMucCon('phu-kien'))
            ->pluck('pro_id');

        $this->assertNotEmpty($phuKien, 'Dữ liệu mẫu phải có sản phẩm phụ kiện thì phép thử mới có nghĩa.');
        $this->assertSame(0, DB::table('products_quantity')->whereIn('pro_id', $phuKien)->count());
    }
}
