<?php

namespace Tests\Feature;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The stock listing of one product, narrowed down.
 */
class AdminStockFilterTest extends TestCase
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
    private function productWithFourVariants(): ProductModel
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-loc');
        ProductQuantityModel::query()->delete();

        $this->addVariant($product, colorId: 1, sizeId: 1, stock: 5, price: 350_000, capitalPrice: 100_000);
        $this->addVariant($product, colorId: 1, sizeId: 2, stock: 0);
        $this->addVariant($product, colorId: 2, sizeId: 1, stock: 7);
        $this->addVariant($product, colorId: 2, sizeId: 2, stock: 0, price: 400_000, capitalPrice: 100_000);

        return $product;
    }

    private function rowIds($response): array
    {
        return $response->viewData('allQuantity')->pluck('quantity_id')->all();
    }

    public function test_khong_loc_thi_hien_het(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)->get(route('stock.show', $product->pro_slug));

        $response->assertOk();
        $this->assertCount(4, $this->rowIds($response));
    }

    public function test_loc_theo_size(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?size_id=2');

        $rows = $response->viewData('allQuantity');
        $this->assertCount(2, $rows);
        $this->assertSame([2, 2], $rows->pluck('size_id')->all());
    }

    public function test_loc_theo_mau(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?color_id=2');

        $rows = $response->viewData('allQuantity');
        $this->assertCount(2, $rows);
        $this->assertSame([2, 2], $rows->pluck('color_id')->all());
    }

    public function test_loc_theo_mau_va_size_cung_luc(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?color_id=1&size_id=1');

        $rows = $response->viewData('allQuantity');
        $this->assertCount(1, $rows);
        $this->assertSame(350_000, (int) $rows->first()->pro_price);
    }

    public function test_loc_dong_co_gia_rieng(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?price=own');

        $rows = $response->viewData('allQuantity');
        $this->assertCount(2, $rows);
        $rows->each(fn ($row) => $this->assertNotNull($row->pro_price));
    }

    public function test_loc_dong_an_theo_gia_san_pham(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?price=inherited');

        $rows = $response->viewData('allQuantity');
        $this->assertCount(2, $rows);
        $rows->each(function ($row) {
            $this->assertNull($row->pro_price);
            $this->assertNull($row->pro_price_sale);
            $this->assertNull($row->capital_price);
        });
    }

    public function test_loc_theo_ton_kho(): void
    {
        $product = $this->productWithFourVariants();

        $inStock = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?stock=in')
            ->viewData('allQuantity');
        $this->assertCount(2, $inStock);
        $inStock->each(fn ($row) => $this->assertGreaterThan(0, $row->quantity));

        $outOfStock = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?stock=out')
            ->viewData('allQuantity');
        $this->assertCount(2, $outOfStock);
        $outOfStock->each(fn ($row) => $this->assertSame(0, (int) $row->quantity));
    }

    /**
     * A filter that matches nothing must not be read as "this product has no
     * stock", which sends the page away to the intake form.
     */
    public function test_loc_khong_ra_ket_qua_thi_van_o_lai_trang(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?color_id=1&price=own&stock=out');

        $response->assertOk();
        $response->assertSee('Không có dòng nào khớp với bộ lọc.');
        $this->assertCount(0, $response->viewData('allQuantity'));
    }

    public function test_san_pham_chua_co_hang_van_bi_day_sang_form_nhap(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-rong');
        ProductQuantityModel::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->assertRedirect(route('stock.create'));
    }

    /**
     * The select only offers what this product actually has, so a shop-wide size
     * list does not fill it with choices that return nothing.
     */
    public function test_o_chon_chi_liet_ke_size_va_mau_cua_san_pham_nay(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-mot-mau');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);

        $response = $this->actingAs($this->admin)->get(route('stock.show', $product->pro_slug));

        $this->assertSame([1], $response->viewData('sizeOptions')->pluck('size_id')->all());
        $this->assertSame([1], $response->viewData('colorOptions')->pluck('color_id')->all());
    }

    /**
     * Filtering down to colourless rows must not drop the colour column, or the
     * header and the body stop lining up.
     */
    public function test_cot_bang_giu_nguyen_khi_loc(): void
    {
        $product = $this->productWithFourVariants();

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?stock=out&price=own');

        $this->assertTrue($response->viewData('hasSize'));
        $this->assertTrue($response->viewData('hasColor'));
    }

    public function test_phan_trang_giu_lai_bo_loc(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-nhieu-dong');
        ProductQuantityModel::query()->delete();

        for ($sizeId = 1; $sizeId <= 2; $sizeId++) {
            for ($i = 0; $i < 15; $i++) {
                DB::table('products_quantity')->insert([
                    'quantity' => 3,
                    'quantity_date' => now()->subDays($i)->toDateString(),
                    'pro_id' => $product->pro_id,
                    'size_id' => $sizeId,
                    'color_id' => null,
                ]);
            }
        }

        $response = $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug).'?size_id=1');

        $rows = $response->viewData('allQuantity');
        $this->assertSame(15, $rows->total());
        $this->assertStringContainsString('size_id=1', $rows->nextPageUrl() ?? $rows->url(1));
    }
}
