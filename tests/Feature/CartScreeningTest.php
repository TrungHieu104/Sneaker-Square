<?php

namespace Tests\Feature;

use App\Models\ProductQuantityModel;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Covers the rewrite of the cart screening that used to be checkProduct().
 *
 * The original read `->quantity` off a stock row that might not exist, spliced
 * the array it was iterating, and returned at the first problem so a second bad
 * line went through to checkout untouched.
 */
class CartScreeningTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLookupTables();
    }

    private function service(): CartService
    {
        return app(CartService::class);
    }

    public function test_san_pham_bi_an_bi_loai_khoi_gio_hang(): void
    {
        $product = $this->makeProduct(slug: 'giay-an');
        $product->update(['pro_hidden' => 0]);

        $result = $this->service()->screen([$this->cartLine($product)]);

        $this->assertSame([], $result['cart']);
        $this->assertTrue($result['removed']);
    }

    public function test_san_pham_khong_con_ton_tai_bi_loai(): void
    {
        $result = $this->service()->screen([[
            'proSlug' => 'khong-ton-tai',
            'quantity' => 1,
            'size_id' => 1,
            'color_id' => 1,
        ]]);

        $this->assertSame([], $result['cart']);
        $this->assertTrue($result['removed']);
    }

    public function test_bien_the_chua_tung_nhap_kho_khong_gay_loi(): void
    {
        $product = $this->makeProduct(slug: 'giay-chua-nhap');
        // A variant that has no row in products_quantity at all, which is what
        // the old code dereferenced.
        ProductQuantityModel::where('pro_id', $product->pro_id)->delete();

        $result = $this->service()->screen([$this->cartLine($product)]);

        $this->assertSame([], $result['cart']);
        $this->assertTrue($result['insufficient']);
    }

    /**
     * Trimmed to what is left rather than dropped: the customer is told to reduce
     * the quantity, so the line has to still be there to reduce.
     */
    public function test_mua_qua_so_luong_ton_kho_thi_bi_cat_bot(): void
    {
        $product = $this->makeProduct(stock: 2, slug: 'giay-it-hang');

        $result = $this->service()->screen([$this->cartLine($product, 5)]);

        $this->assertCount(1, $result['cart'], 'Dòng hàng phải còn lại để khách sửa');
        $this->assertSame(2, $result['cart'][0]['quantity']);
        $this->assertTrue($result['insufficient']);
    }

    /**
     * Nothing left to trim to, so the line does go.
     */
    public function test_het_sach_hang_thi_dong_do_bi_loai(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-het-sach');

        $result = $this->service()->screen([$this->cartLine($product, 3)]);

        $this->assertSame([], $result['cart']);
        $this->assertTrue($result['insufficient']);
    }

    public function test_moi_dong_hang_loi_deu_duoc_xu_ly_khong_dung_o_dong_dau_tien(): void
    {
        $good = $this->makeProduct(stock: 10, slug: 'giay-ban-duoc');
        $hidden = $this->makeProduct(stock: 10, slug: 'giay-bi-an');
        $hidden->update(['pro_hidden' => 0]);
        $short = $this->makeProduct(stock: 1, slug: 'giay-sap-het');

        $result = $this->service()->screen([
            $this->cartLine($hidden),
            $this->cartLine($good, 2),
            $this->cartLine($short, 5),
        ]);

        $this->assertCount(2, $result['cart'], 'Dòng bị ẩn thì loại, dòng thiếu hàng thì cắt bớt');
        $this->assertSame('giay-ban-duoc', $result['cart'][0]['proSlug']);
        $this->assertSame('giay-sap-het', $result['cart'][1]['proSlug']);
        $this->assertSame(1, $result['cart'][1]['quantity']);
        $this->assertTrue($result['removed']);
        $this->assertTrue($result['insufficient']);
    }

    public function test_gio_hang_con_lai_duoc_danh_so_lai_tu_dau(): void
    {
        $hidden = $this->makeProduct(slug: 'giay-bi-an-2');
        $hidden->update(['pro_hidden' => 0]);
        $good = $this->makeProduct(stock: 10, slug: 'giay-con-hang');

        $result = $this->service()->screen([
            $this->cartLine($hidden),
            $this->cartLine($good),
        ]);

        $this->assertSame([0], array_keys($result['cart']), 'Khóa mảng phải liên tục từ 0');
    }
}
