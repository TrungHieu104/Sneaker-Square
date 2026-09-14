<?php

namespace Tests\Feature;

use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Services\CartPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * A price per variant, and a blank one meaning "sell at the product's" — held from
 * adding to the cart through to what the order line records.
 */
class VariantPricingTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seedLookupTables();
    }

    // --------------------------------------------------- a price per variant

    public function test_moi_mau_ban_dung_gia_cua_minh(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 5);
        $this->priceVariant($product, price: 100_000);           // đen
        $this->addVariant($product, colorId: 2, price: 120_000);  // red

        $pricing = app(CartPricingService::class);

        $this->assertSame(100_000, $pricing->unitPrice($product, 1, 1), 'Màu đen phải bán 100k');
        $this->assertSame(120_000, $pricing->unitPrice($product, 2, 1), 'Màu đỏ phải bán 120k');
    }

    public function test_bien_the_khong_dat_gia_thi_ban_theo_gia_san_pham(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 5);
        $this->addVariant($product, colorId: 2, price: 120_000);

        $pricing = app(CartPricingService::class);

        $this->assertSame(
            100_000,
            $pricing->unitPrice($product, 1, 1),
            'Biến thể để trống giá phải lấy đúng giá sản phẩm',
        );
    }

    public function test_gia_giam_cua_bien_the_duoc_uu_tien(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 5);
        $this->addVariant($product, colorId: 2, price: 120_000, salePrice: 99_000);

        $this->assertSame(99_000, app(CartPricingService::class)->unitPrice($product, 2, 1));
    }

    /**
     * A variant selling at 120,000 must not pick up the product's 80,000 discount:
     * it would then sell below its own list price.
     */
    public function test_bien_the_tu_dat_gia_thi_khong_an_theo_gia_giam_cua_san_pham(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 80_000, stock: 5);
        $this->addVariant($product, colorId: 2, price: 120_000);

        $pricing = app(CartPricingService::class);

        $this->assertSame(80_000, $pricing->unitPrice($product, 1, 1), 'Màu ăn theo sản phẩm vẫn được giảm');
        $this->assertSame(120_000, $pricing->unitPrice($product, 2, 1), 'Màu tự đặt giá bán đúng giá của nó');
    }

    // ------------------------------------------------ through the whole purchase

    public function test_dat_hang_tinh_tien_theo_gia_cua_bien_the_da_chon(): void
    {
        $user = $this->makeUser();
        $this->makeAddress($user);
        $product = $this->makeProduct(price: 100_000, stock: 5);
        $this->addVariant($product, colorId: 2, price: 120_000, capitalPrice: 72_000);

        $cart = $this->cartLine($product, 2);
        $cart['color_id'] = 2;
        session(['cart' => [$cart]]);

        $this->actingAs($user)->post('/thanh-toan', ['payment' => 'cod']);

        $order = OrderModel::where('user_id', $user->user_id)->firstOrFail();
        $line = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->assertSame(120_000, (int) $line->price, 'Dòng đơn hàng phải ghi giá của màu đỏ');
        $this->assertSame(72_000, (int) $line->capital_price, 'Giá vốn của đúng biến thể phải được chốt lại');
        $this->assertSame(270_000, (int) $order->order_total, '2 × 120.000 + 30.000 ship');
    }

    public function test_gia_von_chot_tren_don_hang_khong_doi_khi_nhap_lai_hang(): void
    {
        $user = $this->makeUser();
        $this->makeAddress($user);
        $product = $this->makeProduct(price: 100_000, stock: 5);
        $this->priceVariant($product, price: 100_000, capitalPrice: 60_000);

        session(['cart' => [$this->cartLine($product, 1)]]);
        $this->actingAs($user)->post('/thanh-toan', ['payment' => 'cod']);

        // The next delivery comes in dearer.
        $this->priceVariant($product, price: 100_000, capitalPrice: 75_000);

        $line = OrderDetailModel::firstOrFail();

        $this->assertSame(
            60_000,
            (int) $line->capital_price,
            'Đơn đã bán phải giữ giá vốn tại lúc bán, không chạy theo giá nhập mới',
        );
    }

    // -------------------------------------------------------- what pages print

    public function test_trang_danh_sach_in_khoang_gia_khi_cac_bien_the_lech_gia(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 5, slug: 'giay-nhieu-gia');
        $this->addVariant($product, colorId: 2, price: 120_000);

        $loaded = ProductModel::withPriceRange()->where('pro_id', $product->pro_id)->firstOrFail();

        $this->assertSame(['min' => 100_000, 'max' => 120_000], $loaded->priceRange());
        $this->assertSame('100.000 - 120.000', $loaded->displaySellingPrice());
    }

    public function test_san_pham_mot_gia_van_in_dung_mot_con_so(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 5, slug: 'giay-mot-gia');

        $loaded = ProductModel::withPriceRange()->where('pro_id', $product->pro_id)->firstOrFail();

        $this->assertSame('100.000', $loaded->displaySellingPrice());
        $this->assertFalse($loaded->isOnSale());
    }

    /**
     * scopeWithPriceRange() restates the pricing rule as COALESCE/NULLIF — a second
     * copy of it, easy to let drift from the model's.
     */
    public function test_khoang_gia_tinh_bang_sql_khop_voi_tinh_bang_php(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 80_000, stock: 5, slug: 'giay-doi-chieu');
        $this->addVariant($product, colorId: 2, price: 120_000, salePrice: 99_000);

        $viaSql = ProductModel::withPriceRange()->where('pro_id', $product->pro_id)->firstOrFail()->priceRange();
        $viaPhp = ProductModel::where('pro_id', $product->pro_id)->firstOrFail()->priceRange();

        $this->assertSame($viaPhp, $viaSql);
        $this->assertSame(['min' => 80_000, 'max' => 99_000], $viaSql);
    }
}
