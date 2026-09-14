<?php

namespace Tests\Feature;

use App\Http\Requests\Backend\StockAdjustRequest;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Moving one variant's stock up or down from the stock screen.
 */
class AdminStockAdjustTest extends TestCase
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
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-dieu-chinh');
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
        return session('errors')?->getBag(StockAdjustRequest::errorBagFor($quantityId));
    }

    // ------------------------------------------------------------ nhập thêm

    public function test_nhap_them_cong_vao_ton_kho(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 5));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'in',
                'quantity' => 7,
                'quantity_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(12, (int) $variant->fresh()->quantity);
    }

    public function test_nhap_them_cap_nhat_ngay_nhap(): void
    {
        $variant = $this->variantOf($this->stockedProduct());
        $today = now()->toDateString();

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'in',
                'quantity' => 3,
                'quantity_date' => $today,
            ]);

        $this->assertSame($today, $variant->fresh()->quantity_date);
    }

    // ------------------------------------------------------------ giảm bớt

    public function test_giam_bot_tru_khoi_ton_kho(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 30));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 5,
            ])
            ->assertRedirect();

        $this->assertSame(25, (int) $variant->fresh()->quantity);
    }

    public function test_giam_bot_het_sach_ton_kho(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 4));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 4,
            ]);

        $this->assertSame(0, (int) $variant->fresh()->quantity);
    }

    /**
     * The column says "ngày nhập hàng gần nhất", so writing stock off must leave it
     * where it was.
     */
    public function test_giam_bot_khong_doi_ngay_nhap(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 10));
        $variant->quantity_date = '2026-01-05';
        $variant->save();

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 2,
                'quantity_date' => now()->toDateString(),
            ]);

        $this->assertSame('2026-01-05', $variant->fresh()->quantity_date);
    }

    public function test_giam_bot_khong_can_ngay_nhap(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 10));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 3,
            ]);

        $this->assertSame(7, (int) $variant->fresh()->quantity);
    }

    public function test_khong_giam_qua_ton_kho(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 5));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 6,
            ]);

        $this->assertTrue($this->errors($variant->quantity_id)->has('quantity'));
        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    /**
     * Stock never goes below zero, whichever way the request is shaped.
     */
    public function test_ton_kho_khong_bao_gio_am(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 1));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'out',
                'quantity' => 999,
            ]);

        $this->assertGreaterThanOrEqual(0, (int) $variant->fresh()->quantity);
    }

    // ------------------------------------------------------------ chung

    public function test_phai_chon_nhap_hay_giam(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 5));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), ['quantity' => 2]);

        $this->assertTrue($this->errors($variant->quantity_id)->has('mode'));
        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    public function test_nhap_them_van_bat_buoc_ngay(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 5));

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', $variant->quantity_id), [
                'mode' => 'in',
                'quantity' => 2,
            ]);

        $this->assertTrue($this->errors($variant->quantity_id)->has('quantity_date'));
        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    public function test_so_luong_phai_lon_hon_khong(): void
    {
        $variant = $this->variantOf($this->stockedProduct(stock: 5));

        foreach (['in', 'out'] as $mode) {
            $this->actingAs($this->admin)
                ->put(route('stock.adjust', $variant->quantity_id), [
                    'mode' => $mode,
                    'quantity' => 0,
                    'quantity_date' => now()->toDateString(),
                ]);

            $this->assertTrue($this->errors($variant->quantity_id)->has('quantity'));
        }

        $this->assertSame(5, (int) $variant->fresh()->quantity);
    }

    /**
     * Adjusting stock is not a chance to reprice: the row has its own form for
     * that, and quietly repricing here is how a per-size price gets lost.
     */
    public function test_dieu_chinh_khong_dong_toi_gia(): void
    {
        $variant = $this->variantOf($this->stockedProduct());
        $variant->pro_price = 1_200_000;
        $variant->capital_price = 700_000;
        $variant->save();

        foreach ([['in', now()->toDateString()], ['out', null]] as [$mode, $date]) {
            $this->actingAs($this->admin)
                ->put(route('stock.adjust', $variant->quantity_id), [
                    'mode' => $mode,
                    'quantity' => 1,
                    'quantity_date' => $date,
                    'pro_price' => 1,
                    'capital_price' => 1,
                    'pro_price_sale' => 1,
                ]);
        }

        $fresh = $variant->fresh();
        $this->assertSame(1_200_000, (int) $fresh->pro_price);
        $this->assertSame(700_000, (int) $fresh->capital_price);
        $this->assertNull($fresh->pro_price_sale);
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
            ->put(route('stock.adjust', $rows[0]->quantity_id), [
                'mode' => 'out',
                'quantity' => 999,
            ]);

        $this->assertTrue($this->errors($rows[0]->quantity_id)->has('quantity'));
        $this->assertTrue($this->errors($rows[1]->quantity_id)->isEmpty());
    }

    public function test_bien_the_khong_ton_tai(): void
    {
        $this->stockedProduct();

        $this->actingAs($this->admin)
            ->put(route('stock.adjust', 999999), [
                'mode' => 'in',
                'quantity' => 2,
                'quantity_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('Không tìm thấy sản phẩm này trong kho!', session('message'));
    }

    public function test_nut_dieu_chinh_hien_tren_moi_dong(): void
    {
        $product = $this->stockedProduct();
        $variant = $this->variantOf($product);

        $this->actingAs($this->admin)
            ->get(route('stock.show', $product->pro_slug))
            ->assertSee('Điều chỉnh tồn kho')
            ->assertSee('adjust-modal-'.$variant->quantity_id)
            ->assertSee('Nhập thêm')
            ->assertSee('Giảm bớt');
    }
}
