<?php

namespace Tests\Feature;

use App\Models\ProductQuantityModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What the product page tells a customer before they commit to anything.
 *
 * Whether the shelf is empty used to be answerable only by filling in the form
 * and being turned away at the cart.
 */
class ProductPageTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    public function test_con_hang_thi_trang_noi_con_hang(): void
    {
        $product = $this->makeProduct(stock: 40, slug: 'giay-con-hang');

        $response = $this->get(route('product.detail', $product->pro_slug))->assertOk();

        $this->assertSame(40, $response->viewData('stockTotal'));
        $response->assertSee('Còn hàng');
    }

    public function test_het_hang_thi_trang_noi_het_hang_va_khoa_nut_mua(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-het-hang');

        $html = $this->get(route('product.detail', $product->pro_slug))->assertOk()->getContent();

        $this->assertStringContainsString('Hết hàng', $html);
        $this->assertMatchesRegularExpression('/btn-cart_order[^>]*disabled/s', $html);
        $this->assertMatchesRegularExpression('/btn-add-pro__detail[^>]*disabled/s', $html);
    }

    public function test_san_pham_khong_co_dong_ton_kho_nao_cung_bao_het_hang(): void
    {
        $product = $this->makeProduct(slug: 'phu-kien-khong-bien-the');
        ProductQuantityModel::where('pro_id', $product->pro_id)->delete();

        $response = $this->get(route('product.detail', $product->pro_slug))->assertOk();

        $this->assertSame(0, $response->viewData('stockTotal'));
        $response->assertSee('Hết hàng');
    }

    public function test_chon_xong_bien_the_thi_tem_in_so_luong(): void
    {
        $html = Blade::render("@include('components.stock_tag', ['stock' => 12, 'exact' => true])");

        $this->assertStringContainsString('Số lượng:', $html);
        $this->assertStringContainsString('>12<', $html);
        // The script re-draws the tag from this attribute, so the wording is
        // written down once, here.
        $this->assertStringContainsString('data-label-exact="Số lượng:"', $html);
    }

    public function test_sap_het_hang_thi_nhan_doi_sang_trang_thai_gap(): void
    {
        $product = $this->makeProduct(stock: 3, slug: 'giay-sap-het');

        $html = $this->get(route('product.detail', $product->pro_slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-state="low"', $html);
        $this->assertStringContainsString('Chỉ còn', $html);
        // Zero-padded, the way the tag prints every count.
        $this->assertStringContainsString('>03<', $html);
    }

    public function test_them_vao_gio_hang_thi_o_lai_trang_san_pham(): void
    {
        $product = $this->makeProduct(stock: 5, slug: 'giay-them-gio');

        $this->from(route('product.detail', $product->pro_slug))
            ->post(route('addProduct.cart', $product->pro_slug), [
                'action' => 'stay',
                'quantity' => 2,
                'options-color' => 1,
                'options-size' => 1,
            ])
            ->assertRedirect(route('product.detail', $product->pro_slug));

        $this->assertSame(2, (int) session('cart')[0]['quantity']);
    }

    public function test_mua_hang_thi_di_thang_toi_gio_hang(): void
    {
        $product = $this->makeProduct(stock: 5, slug: 'giay-mua-ngay');

        $this->from(route('product.detail', $product->pro_slug))
            ->post(route('addProduct.cart', $product->pro_slug), [
                'quantity' => 1,
                'options-color' => 1,
                'options-size' => 1,
            ])
            ->assertRedirect('/gio-hang');
    }
}
