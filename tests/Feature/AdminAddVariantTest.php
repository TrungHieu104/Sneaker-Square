<?php

namespace Tests\Feature;

use App\Http\Requests\Backend\StockVariantRequest;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Adding one variant straight from a product's stock screen.
 */
class AdminAddVariantTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        DB::table('size')->insertOrIgnore(['size_id' => 2, 'size' => '36', 'size_hidden' => 1]);

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        foreach (['Quản trị Sản phẩm', 'Quản trị Sản phẩm (Kho)'] as $name) {
            $this->admin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
    }

    /**
     * size_id 1 is "42" and 2 is "36"; color_id 1 is Đen and 2 is Đỏ.
     */
    private function stockedProduct(int $price = 1_000_000): ProductModel
    {
        $product = $this->makeProduct(price: $price, slug: 'giay-them');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1, stock: 5);

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'size_id' => 2,
            'color_id' => 2,
            'quantity' => 7,
            'quantity_date' => now()->toDateString(),
        ], $overrides);
    }

    private function errors($response)
    {
        return session('errors')?->getBag(StockVariantRequest::ERROR_BAG);
    }

    public function test_them_bien_the_moi_vao_kho(): void
    {
        $product = $this->stockedProduct();

        $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('products_quantity', [
            'pro_id' => $product->pro_id,
            'size_id' => 2,
            'color_id' => 2,
            'quantity' => 7,
            'pro_price' => null,
            'pro_price_sale' => null,
            'capital_price' => null,
        ]);
    }

    public function test_them_bien_the_kem_gia_rieng(): void
    {
        $product = $this->stockedProduct();

        $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'pro_price' => 1_200_000,
                'capital_price' => 700_000,
            ]));

        $variant = ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('size_id', 2)->where('color_id', 2)->first();

        $this->assertSame(1_200_000, (int) $variant->pro_price);
        $this->assertSame(700_000, (int) $variant->capital_price);
        $this->assertNull($variant->pro_price_sale);
    }

    public function test_them_bien_the_khong_phan_size_va_mau(): void
    {
        $product = $this->stockedProduct();

        $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'size_id' => '',
                'color_id' => '',
            ]));

        $this->assertDatabaseHas('products_quantity', [
            'pro_id' => $product->pro_id,
            'size_id' => null,
            'color_id' => null,
            'quantity' => 7,
        ]);
    }

    public function test_khong_the_them_bien_the_da_co(): void
    {
        $product = $this->stockedProduct();

        $response = $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'size_id' => 1,
                'color_id' => 1,
            ]));

        $this->assertTrue($this->errors($response)->has('size_id'));
        $this->assertSame(1, ProductQuantityModel::where('pro_id', $product->pro_id)->count());
        $this->assertSame(5, (int) $this->stockOf($product, 1, 1));
    }

    /**
     * MySQL counts each NULL as distinct, so the unique index cannot catch a second
     * unsized, uncoloured row — the request has to.
     */
    public function test_khong_the_them_hai_dong_khong_size_khong_mau(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-tron');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);
        DB::table('products_quantity')->insert([
            'quantity' => 4, 'quantity_date' => now()->toDateString(),
            'pro_id' => $product->pro_id, 'size_id' => null, 'color_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'size_id' => '',
                'color_id' => '',
            ]));

        $this->assertTrue($this->errors($response)->has('size_id'));
        $this->assertSame(2, ProductQuantityModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_so_luong_phai_lon_hon_khong(): void
    {
        $product = $this->stockedProduct();

        $response = $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload(['quantity' => 0]));

        $this->assertTrue($this->errors($response)->has('quantity'));
        $this->assertSame(1, ProductQuantityModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_gia_ban_phai_lon_hon_gia_von(): void
    {
        $product = $this->stockedProduct();

        $response = $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'pro_price' => 500_000,
                'capital_price' => 900_000,
            ]));

        $this->assertTrue($this->errors($response)->has('pro_price'));
        $this->assertSame(1, ProductQuantityModel::where('pro_id', $product->pro_id)->count());
    }

    /**
     * A blank list price makes the variant follow the product's, so a sale price
     * has to be measured against that rather than against nothing.
     */
    public function test_gia_giam_phai_thap_hon_gia_ban_ke_thua(): void
    {
        $product = $this->stockedProduct(price: 1_000_000);

        $response = $this->actingAs($this->admin)
            ->post(route('stock.variant.store', $product->pro_slug), $this->payload([
                'pro_price_sale' => 1_500_000,
            ]));

        $this->assertTrue($this->errors($response)->has('pro_price_sale'));
    }

    public function test_nut_them_bien_the_hien_tren_trang_kho(): void
    {
        $product = $this->stockedProduct();

        $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->assertSee('Thêm biến thể')
            ->assertSee('add-variant-modal');
    }

    /**
     * Every size and colour is on offer, not only the ones already stocked — a
     * variant that exists is not one you can add.
     */
    public function test_form_them_liet_ke_moi_size_va_mau(): void
    {
        $product = $this->stockedProduct();

        $response = $this->actingAs($this->admin)->get(route('stock.show', $product->pro_slug));

        $this->assertCount(2, $response->viewData('allSize'));
        $this->assertCount(2, $response->viewData('allColor'));
    }
}
