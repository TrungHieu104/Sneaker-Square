<?php

namespace Tests\Feature;

use App\Http\Requests\Backend\StockRestockRequest;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Adding a delivery to one variant from the stock screen.
 */
class AdminRestockTest extends TestCase
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

    private function stockedProduct(int $stock = 5): ProductModel
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-nhap-them');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1, stock: $stock);

        return $product;
    }

    private function variantOf(ProductModel $product): ProductQuantityModel
    {
        return ProductQuantityModel::where('pro_id', $product->pro_id)->firstOrFail();
    }

    private function errors($quantityId)
    {
        return session('errors')?->getBag(StockRestockRequest::errorBagFor($quantityId));
    }

    public function test_nhap_them_cong_vao_ton_kho(): void
    {
        $product = $this->stockedProduct(stock: 5);
        $variant = $this->variantOf($product);

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $variant->quantity_id), [
                'quantity' => 7,
                'quantity_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(12, (int) $variant->fresh()->quantity);
    }

    public function test_nhap_them_cap_nhat_ngay_nhap(): void
    {
        $product = $this->stockedProduct();
        $variant = $this->variantOf($product);
        $today = now()->toDateString();

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $variant->quantity_id), [
                'quantity' => 3,
                'quantity_date' => $today,
            ]);

        $this->assertSame($today, $variant->fresh()->quantity_date);
    }

    /**
     * Restocking is not a chance to reprice: the row has its own form for that,
     * and quietly repricing here is how a per-size price gets lost.
     */
    public function test_nhap_them_khong_dong_toi_gia(): void
    {
        $product = $this->stockedProduct();
        $variant = $this->variantOf($product);
        $variant->pro_price = 1_200_000;
        $variant->capital_price = 700_000;
        $variant->save();

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $variant->quantity_id), [
                'quantity' => 4,
                'quantity_date' => now()->toDateString(),
                'pro_price' => 1,
                'capital_price' => 1,
                'pro_price_sale' => 1,
            ]);

        $fresh = $variant->fresh();
        $this->assertSame(1_200_000, (int) $fresh->pro_price);
        $this->assertSame(700_000, (int) $fresh->capital_price);
        $this->assertNull($fresh->pro_price_sale);
    }

    public function test_so_luong_phai_lon_hon_khong(): void
    {
        $product = $this->stockedProduct(stock: 5);
        $variant = $this->variantOf($product);

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $variant->quantity_id), [
                'quantity' => 0,
                'quantity_date' => now()->toDateString(),
            ]);

        $this->assertTrue($this->errors($variant->quantity_id)->has('quantity'));
        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    public function test_ngay_nhap_khong_hop_le_bi_chan(): void
    {
        $product = $this->stockedProduct(stock: 5);
        $variant = $this->variantOf($product);

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $variant->quantity_id), [
                'quantity' => 2,
                'quantity_date' => 'hôm qua',
            ]);

        $this->assertTrue($this->errors($variant->quantity_id)->has('quantity_date'));
        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    /**
     * A failure on one row must not redden the forms belonging to the others.
     */
    public function test_loi_chi_thuoc_ve_dong_bi_loi(): void
    {
        $product = $this->stockedProduct();
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);
        $this->addVariant($product, colorId: 2, sizeId: 1);

        $rows = ProductQuantityModel::where('pro_id', $product->pro_id)->orderBy('quantity_id')->get();

        $this->actingAs($this->admin)
            ->put(route('stock.restock', $rows[0]->quantity_id), [
                'quantity' => 0,
                'quantity_date' => now()->toDateString(),
            ]);

        $this->assertTrue($this->errors($rows[0]->quantity_id)->has('quantity'));
        $this->assertTrue($this->errors($rows[1]->quantity_id)->isEmpty());
    }

    public function test_bien_the_khong_ton_tai(): void
    {
        $this->stockedProduct();

        $this->actingAs($this->admin)
            ->put(route('stock.restock', 999999), [
                'quantity' => 2,
                'quantity_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('Không tìm thấy sản phẩm này trong kho!', session('message'));
    }

    public function test_nut_nhap_them_hien_tren_moi_dong(): void
    {
        $product = $this->stockedProduct();
        $variant = $this->variantOf($product);

        $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->assertSee('Nhập thêm')
            ->assertSee('restock-modal-'.$variant->quantity_id);
    }
}
