<?php

namespace Tests\Feature;

use App\Models\CategoryModel;
use App\Models\ColorModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Covers the admin forms whose uniqueness checks were hand-rolled.
 *
 * The FormRequests already used Rule::unique()->ignore(), but they ignored
 * `request()->id` — a parameter none of these routes defines. So on every edit
 * a record collided with itself, and the controllers had grown chains of
 * Validator calls to route around it. One of those chains read
 * `if (the name changed) … elseif (the code changed) …`, which meant an edit
 * that changed both never checked the product code at all.
 */
class AdminProductValidationTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->admin = $this->makeAdmin();
    }

    private function makeAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);

        foreach (['Quản trị Sản phẩm', 'Quản trị Sản phẩm (Kho)'] as $name) {
            $admin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(ProductModel $product, array $overrides = []): array
    {
        return array_merge([
            'pro_name' => $product->pro_name,
            'pro_slug' => $product->pro_slug,
            'pro_code' => $product->pro_code,
            'pro_price' => 1_000_000,
            'capital_price' => 600_000,
            'pro_price_sale' => 0,
            'pro_date' => now()->toDateString(),
            'cate_id' => 1,
        ], $overrides);
    }

    // --------------------------------------------------------- products

    public function test_sua_san_pham_ma_khong_doi_ten_thi_khong_bi_bao_trung(): void
    {
        $product = $this->makeProduct(slug: 'giay-a');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->productPayload($product, [
                'pro_price' => 1_500_000,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1_500_000, (int) $product->fresh()->pro_price, 'Giữ nguyên tên mà vẫn phải lưu được');
    }

    public function test_doi_dong_thoi_ten_va_ma_van_bi_chan_khi_ma_trung(): void
    {
        $other = $this->makeProduct(slug: 'giay-khac');
        $product = $this->makeProduct(slug: 'giay-can-sua');

        // Both fields change at once. The old chain checked the name, found it
        // free, and never looked at the code.
        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->productPayload($product, [
                'pro_name' => 'Một Cái Tên Hoàn Toàn Mới',
                'pro_code' => $other->pro_code,
            ]))
            ->assertSessionHasErrors('pro_code');

        $this->assertSame('SKU-giay-can-sua', $product->fresh()->pro_code, 'Mã sản phẩm trùng không được lưu');
    }

    public function test_doi_ten_thanh_ten_da_ton_tai_bi_chan(): void
    {
        $other = $this->makeProduct(slug: 'giay-da-co');
        $product = $this->makeProduct(slug: 'giay-moi');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->productPayload($product, [
                'pro_name' => $other->pro_name,
            ]))
            ->assertSessionHasErrors('pro_name');
    }

    public function test_gia_ban_thap_hon_gia_von_bi_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-gia');

        $this->actingAs($this->admin)
            ->put(route('product.update', $product->pro_id), $this->productPayload($product, [
                'pro_price' => 100,
                'capital_price' => 900_000,
            ]))
            ->assertSessionHasErrors('pro_price');
    }

    // --------------------------------------------------------- categories

    public function test_sua_danh_muc_ma_khong_doi_ten_thi_luu_duoc(): void
    {
        $cate = CategoryModel::find(1);

        $this->actingAs($this->admin)
            ->put(route('product-category.update', $cate->cate_id), [
                'cate_name' => $cate->cate_name,
                'cate_slug' => $cate->cate_slug,
                'cate_sort' => $cate->cate_sort,
                'cate_meta_keywords' => 'giày, thể thao',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('giày, thể thao', $cate->fresh()->cate_meta_keywords);
    }

    // -------------------------------------------------------------- colours

    public function test_sua_mau_ma_khong_doi_ten_thi_luu_duoc(): void
    {
        $color = ColorModel::find(1);

        $this->actingAs($this->admin)
            ->put(route('stock.update.color', $color->color_id), [
                'color' => 'black',
                'color_vn' => 'Đen',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Đen', $color->fresh()->color_vn);
    }

    public function test_doi_mau_thanh_ten_cua_mau_khac_bi_chan(): void
    {
        ColorModel::create(['color' => 'white', 'color_vn' => 'Trắng', 'color_hidden' => 1]);
        $color = ColorModel::where('color', 'black')->first();

        $this->actingAs($this->admin)
            ->put(route('stock.update.color', $color->color_id), [
                'color' => 'white',
                'color_vn' => 'Trắng',
            ])
            ->assertSessionHasErrors('color');

        $this->assertSame('black', $color->fresh()->color);
    }

    // ------------------------------------------------------------- stock

    public function test_nhap_kho_so_luong_khong_duong_bi_chan(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-nhap-kho');

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 1,
                'quantityOthers' => 0,
            ])
            ->assertSessionHasErrors('quantityOthers');

        $this->assertSame(10, $this->stockOf($product), 'Tồn kho không được đổi khi nhập hàng thất bại');
    }

    public function test_nhap_kho_hop_le_cong_them_vao_ton(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-nhap-them');

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 1,
                'quantityOthers' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(15, $this->stockOf($product));
    }

    public function test_nhap_kho_thieu_ngay_nhap_bi_chan(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-thieu-ngay');

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'pro_type' => 1,
                'quantityOthers' => 5,
            ])
            ->assertSessionHasErrors('quantity_date');

        $this->assertSame(10, $this->stockOf($product));
    }

    public function test_ten_mau_tieng_viet_duoc_chuan_hoa_truoc_khi_kiem_tra(): void
    {
        $color = ColorModel::find(1);

        $this->actingAs($this->admin)
            ->put(route('stock.update.color', $color->color_id), [
                'color' => 'black',
                'color_vn' => 'đen',
            ])
            ->assertSessionHasNoErrors();

        // Stored title-cased, and checked for uniqueness in that same form.
        $this->assertSame('Đen', $color->fresh()->color_vn);
    }
}
