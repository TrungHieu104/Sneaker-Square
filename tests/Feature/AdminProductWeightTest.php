<?php

namespace Tests\Feature;

use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Shipping weight on the product form.
 *
 * The number is in grams because that is the unit the carrier's fee API takes;
 * converting at the edge would leave the stored value in one unit and the
 * request in another.
 */
class AdminProductWeightTest extends TestCase
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(ProductModel $product, array $overrides = []): array
    {
        return array_merge([
            'pro_name' => $product->pro_name,
            'pro_slug' => $product->pro_slug,
            'pro_code' => $product->pro_code,
            'pro_price' => 1_000_000,
            'capital_price' => 600_000,
            'pro_price_sale' => 0,
            'pro_weight' => 1200,
            'pro_date' => now()->toDateString(),
            'cate_id' => 1,
        ], $overrides);
    }

    public function test_luu_can_nang_hop_le(): void
    {
        $product = $this->makeProduct(slug: 'giay-can-nang');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->payload($product, [
                'pro_weight' => 1450,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1450, (int) $product->fresh()->pro_weight);
    }

    public function test_thieu_can_nang_bi_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-thieu-can');
        $payload = $this->payload($product);
        unset($payload['pro_weight']);

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $payload)
            ->assertSessionHasErrors('pro_weight');
    }

    public function test_can_nang_bang_khong_bi_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-can-0');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->payload($product, ['pro_weight' => 0]))
            ->assertSessionHasErrors('pro_weight');

        $this->assertNotSame(0, (int) $product->fresh()->pro_weight);
    }

    public function test_can_nang_khong_phai_so_nguyen_bi_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-can-le');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->payload($product, ['pro_weight' => 1200.5]))
            ->assertSessionHasErrors('pro_weight');
    }

    /**
     * 50kg is past what any carrier takes as one parcel, and the column is a
     * smallint: a typo of 120000 would otherwise be stored truncated.
     */
    public function test_can_nang_vuot_nguong_bi_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-can-lon');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->payload($product, ['pro_weight' => 120000]))
            ->assertSessionHasErrors('pro_weight');
    }

    public function test_form_sua_san_pham_hien_o_can_nang(): void
    {
        $this->makeProduct(slug: 'giay-hien-can');

        $this->actingAs($this->admin)
            ->get(route('product.index'))
            ->assertOk()
            ->assertSee('name="pro_weight"', false)
            ->assertSee('Sneaker phổ thông');
    }

    public function test_form_them_san_pham_hien_o_can_nang(): void
    {
        $this->actingAs($this->admin)
            ->get(route('product.create'))
            ->assertOk()
            ->assertSee('name="pro_weight"', false)
            ->assertSee('Cổ cao · đế chunky');
    }
}
