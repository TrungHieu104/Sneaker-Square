<?php

namespace Tests\Feature;

use App\Models\ProductQuantityModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Adding a variant to the cart: what is turned away, and how a second helping
 * of the same variant lands.
 */
class CartAddTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    private function add(string $slug, int $quantity, int $colorId = 1, int $sizeId = 1)
    {
        return $this->from('/san-pham/'.$slug)->post(route('addProduct.cart', $slug), [
            'action' => 'stay', 'quantity' => $quantity, 'options-color' => $colorId, 'options-size' => $sizeId,
        ]);
    }

    public function test_san_pham_khong_ton_tai_thi_bao_loi(): void
    {
        $this->add('khong-co', 1)->assertSessionHas('message', 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống');
        $this->assertNull(session('cart'));
    }

    public function test_san_pham_dang_an_hoac_chua_toi_ngay_ban_thi_khong_them_duoc(): void
    {
        $this->makeProduct(slug: 'giay-dang-an')->update(['pro_hidden' => 0]);
        $this->makeProduct(slug: 'giay-sap-ban')->update(['pro_date' => now()->addDay()->toDateString()]);

        $this->add('giay-dang-an', 1)->assertSessionHas('message', 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống');
        $this->add('giay-sap-ban', 1)->assertSessionHas('message', 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống');
        $this->assertNull(session('cart'));
    }

    public function test_so_luong_ngoai_khoang_1_den_20_thi_bi_tu_choi(): void
    {
        $this->makeProduct(stock: 100, slug: 'giay-nhieu');

        foreach ([0, -3, 21] as $quantity) {
            $this->add('giay-nhieu', $quantity)->assertSessionHas('message', 'Mỗi lần thêm được từ 1 đến 20 sản phẩm.');
        }

        $this->assertNull(session('cart'));
        $this->add('giay-nhieu', 20)->assertSessionHas('message', 'Đã thêm vào giỏ hàng.');
    }

    public function test_bien_the_chua_nhap_kho_thi_bao_loi(): void
    {
        $this->makeProduct(slug: 'giay-thieu-size');

        $this->add('giay-thieu-size', 1, sizeId: 99)
            ->assertSessionHas('message', 'Opps!!! Sản phẩm bạn vừa chọn chưa được nhập trong kho');
    }

    public function test_bien_the_het_hang_thi_bao_loi(): void
    {
        $this->makeProduct(stock: 0, slug: 'giay-het');

        $this->add('giay-het', 1)->assertSessionHas('message', 'Opps!!! Sản phẩm bạn vừa chọn đã hết hàng');
    }

    public function test_vuot_ton_kho_thi_bao_loi(): void
    {
        $this->makeProduct(stock: 3, slug: 'giay-it');

        $this->add('giay-it', 4)->assertSessionHas('message', 'Opps!!! Số lượng bạn chọn vượt quá số lượng trong kho');
    }

    public function test_them_lan_hai_cung_bien_the_thi_cong_don_va_tinh_ca_phan_da_co(): void
    {
        $product = $this->makeProduct(stock: 5, slug: 'giay-cong-don');

        $this->add('giay-cong-don', 2)->assertSessionHas('message', 'Đã thêm vào giỏ hàng.');
        $this->add('giay-cong-don', 2)->assertSessionHas('message', 'Đã thêm vào giỏ hàng.');

        $this->assertCount(1, session('cart'));
        $this->assertSame(4, (int) session('cart')[0]['quantity']);

        $this->add('giay-cong-don', 2)
            ->assertSessionHas('message', 'Opps!!! Giỏ hàng của bạn đã có 4 sản phẩm này, trong kho chỉ còn 5');
        $this->assertSame(5, (int) ProductQuantityModel::where('pro_id', $product->pro_id)->value('quantity'));
    }
}
