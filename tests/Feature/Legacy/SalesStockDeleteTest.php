<?php

namespace Tests\Feature\Legacy;

use App\Models\ColorModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Removing a colour or a stock row from the stock screens.
 */
class SalesStockDeleteTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Sản phẩm (Kho)', 'web'));
    }

    public function test_xoa_mau_sac(): void
    {
        $this->actingAs($this->admin)->delete(route('stock.delete.color', 2))
            ->assertRedirect()
            ->assertSessionHas('message', 'Xóa màu sắc thành công!');

        $this->assertNull(ColorModel::find(2));
    }

    public function test_xoa_mau_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->admin)->from(route('stock.create'))->delete(route('stock.delete.color', 999))
            ->assertRedirect(route('stock.create'))
            ->assertSessionHas('message', 'Không tồn tại màu sắc.');
    }

    public function test_xoa_dong_kho(): void
    {
        $product = $this->makeProduct(slug: 'giay-xoa-kho');
        $variant = ProductQuantityModel::where('pro_id', $product->pro_id)->firstOrFail();

        $this->actingAs($this->admin)->delete(route('stock.destroy', $variant->quantity_id))
            ->assertRedirect()
            ->assertSessionHas('message', 'Xóa sản phẩm trong kho thành công!');

        $this->assertNull($variant->fresh());
    }

    public function test_xoa_dong_kho_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->admin)->from(route('stock.index'))->delete(route('stock.destroy', 999))
            ->assertRedirect(route('stock.index'))
            ->assertSessionHas('message', 'Không tồn tại sản phẩm này trong kho!');
    }
}
