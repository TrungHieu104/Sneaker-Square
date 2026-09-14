<?php

namespace Tests\Feature;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What the stock listing says about where each row's price came from.
 */
class AdminStockListingPriceTest extends TestCase
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

    private function productWithOneVariant(int $price = 2_500_000, int $salePrice = 0): ProductModel
    {
        $product = $this->makeProduct(price: $price, salePrice: $salePrice, slug: 'giay-nhan');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);

        return $product;
    }

    private function priceCell(string $html): string
    {
        // The selling price cell, cut out of the row so the cost cell's own
        // "theo sản phẩm" cannot be mistaken for it.
        preg_match('/2\.[05]00\.000 VNĐ.*?<\/td>/su', $html, $match);

        return $match[0] ?? '';
    }

    public function test_dong_khong_co_gia_rieng_thi_ghi_theo_san_pham(): void
    {
        $product = $this->productWithOneVariant();

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $this->assertStringContainsString('theo sản phẩm', $this->priceCell($html));
    }

    public function test_dong_co_gia_ban_rieng_thi_khong_ghi_theo_san_pham(): void
    {
        $product = $this->productWithOneVariant();
        $this->priceVariant($product, price: 2_000_000, colorId: 1, sizeId: 1);

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $this->assertStringNotContainsString('theo sản phẩm', $this->priceCell($html));
    }

    /**
     * The case that was wrong: a variant carrying only its own sale price still
     * sells at a price of its own, so the row must not claim it follows the
     * product.
     */
    public function test_dong_chi_co_gia_sale_rieng_thi_khong_ghi_theo_san_pham(): void
    {
        $product = $this->productWithOneVariant();
        $this->priceVariant($product, salePrice: 2_000_000, colorId: 1, sizeId: 1);

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $cell = $this->priceCell($html);

        $this->assertStringContainsString('2.000.000 VNĐ', $cell);
        $this->assertStringNotContainsString('theo sản phẩm', $cell);
    }

    public function test_dang_sale_thi_gach_ngang_gia_goc(): void
    {
        $product = $this->productWithOneVariant();
        $this->priceVariant($product, salePrice: 2_000_000, colorId: 1, sizeId: 1);

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $this->assertMatchesRegularExpression('/<del[^>]*>\s*2\.500\.000 VNĐ\s*<\/del>/u', $html);
    }

    public function test_khong_sale_thi_khong_gach_ngang(): void
    {
        $product = $this->productWithOneVariant();

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $this->assertStringNotContainsString('<del', $html);
    }

    /**
     * A sale that belongs to the product, not to the variant: struck through all
     * the same, but the row does follow the product.
     */
    public function test_sale_cua_san_pham_van_ghi_theo_san_pham(): void
    {
        $product = $this->productWithOneVariant(salePrice: 2_000_000);

        $html = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->getContent();

        $cell = $this->priceCell($html);

        $this->assertStringContainsString('2.000.000 VNĐ', $cell);
        $this->assertStringContainsString('theo sản phẩm', $cell);
        $this->assertMatchesRegularExpression('/<del[^>]*>\s*2\.500\.000 VNĐ\s*<\/del>/u', $html);
    }
}
