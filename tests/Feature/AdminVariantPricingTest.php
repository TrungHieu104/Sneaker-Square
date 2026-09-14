<?php

namespace Tests\Feature;

use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The two admin screens read a blank price box in opposite ways, deliberately:
 * receiving stock it leaves the price alone, editing stock it clears it.
 */
class AdminVariantPricingTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        foreach (['Quản trị Sản phẩm', 'Quản trị Sản phẩm (Kho)'] as $name) {
            $this->admin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stockPayload(int $proId, array $overrides = []): array
    {
        return array_merge([
            'pro_id' => $proId,
            'quantity_date' => now()->toDateString(),
            'pro_type' => 0,
            'size_id' => [1],
            'color_id' => [1, 2],
            'quantityColorAndSize' => [1 => 5, 2 => 5],
        ], $overrides);
    }

    // ------------------------------------------------------ receiving stock

    public function test_nhap_kho_ghi_gia_rieng_cho_tung_mau(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'giay-nhap');
        ProductQuantityModel::query()->delete();

        $this->actingAs($this->admin)
            ->post(route('stock.store'), $this->stockPayload($product->pro_id, [
                'priceColor' => [2 => 120_000],
                'capitalPriceColor' => [2 => 72_000],
            ]));

        $black = ProductQuantityModel::where('color_id', 1)->firstOrFail();
        $red = ProductQuantityModel::where('color_id', 2)->firstOrFail();

        $this->assertNull($black->pro_price, 'Màu để trống giá thì bán theo giá sản phẩm');
        $this->assertSame(100_000, $black->sellingPrice($product));
        $this->assertSame(120_000, $red->sellingPrice($product));
        $this->assertSame(72_000, $red->capitalPrice($product));
    }

    /**
     * The worry this feature started from. The form takes a price per colour, so
     * there is no path through which two sizes of one colour can diverge.
     */
    public function test_moi_size_cung_mau_deu_nhan_cung_mot_gia(): void
    {
        $this->seedExtraSize();
        $product = $this->makeProduct(price: 100_000, slug: 'giay-nhieu-size');
        ProductQuantityModel::query()->delete();

        $this->actingAs($this->admin)
            ->post(route('stock.store'), $this->stockPayload($product->pro_id, [
                'size_id' => [1, 2],
                'color_id' => [2],
                'quantityColorAndSize' => [2 => 5],
                'priceColor' => [2 => 120_000],
            ]));

        $prices = ProductQuantityModel::where('color_id', 2)->pluck('pro_price')->all();

        $this->assertCount(2, $prices, 'Phải tạo đủ một biến thể cho mỗi size');
        $this->assertSame([120_000, 120_000], $prices);
    }

    public function test_nhap_them_hang_khong_lam_mat_gia_rieng_da_dat(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'giay-nhap-lai');
        $this->priceVariant($product, price: 130_000);

        $this->actingAs($this->admin)
            ->post(route('stock.store'), $this->stockPayload($product->pro_id, [
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 7],
            ]));

        $variant = ProductQuantityModel::where('color_id', 1)->firstOrFail();

        $this->assertSame(130_000, (int) $variant->pro_price, 'Ô giá bỏ trống là không đổi giá, không phải xóa giá');
        $this->assertSame(17, (int) $variant->quantity, '10 tồn sẵn + 7 vừa nhập');
    }

    public function test_gia_von_cao_hon_gia_ban_bi_tu_choi(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'giay-gia-sai');
        ProductQuantityModel::query()->delete();

        $this->actingAs($this->admin)
            ->post(route('stock.store'), $this->stockPayload($product->pro_id, [
                'color_id' => [2],
                'quantityColorAndSize' => [2 => 5],
                'capitalPriceColor' => [2 => 200_000],
            ]))
            ->assertSessionHasErrors('priceColor.2');

        $this->assertSame(0, ProductQuantityModel::count(), 'Không được ghi gì khi giá vô lý');
    }

    // -------------------------------------------- editing prices in the stock

    public function test_sua_gia_bien_the_o_man_hinh_kho(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'giay-sua-gia');
        $variant = $this->priceVariant($product, price: 120_000);

        $this->actingAs($this->admin)
            ->put(route('stock.update.price', $variant->quantity_id), [
                'pro_price' => 150_000,
                'capital_price' => 90_000,
            ]);

        $variant->refresh();

        $this->assertSame(150_000, (int) $variant->pro_price);
        $this->assertSame(90_000, (int) $variant->capital_price);
    }

    public function test_de_trong_o_gia_o_man_hinh_kho_la_go_bo_gia_rieng(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'giay-go-gia');
        $variant = $this->priceVariant($product, price: 120_000, capitalPrice: 72_000);

        $this->actingAs($this->admin)
            ->put(route('stock.update.price', $variant->quantity_id), [
                'pro_price' => '',
                'pro_price_sale' => '',
                'capital_price' => '',
            ]);

        $variant->refresh();

        $this->assertNull($variant->pro_price);
        $this->assertNull($variant->capital_price);
        $this->assertSame(100_000, $variant->sellingPrice($product), 'Gỡ giá riêng thì quay về giá sản phẩm');
    }

    private function seedExtraSize(): void
    {
        \Illuminate\Support\Facades\DB::table('size')
            ->insertOrIgnore(['size_id' => 2, 'size' => '43', 'size_hidden' => 1]);
    }
}
