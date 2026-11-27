<?php

namespace Tests\Feature;

use App\Models\CategoryModel;
use App\Models\ColorModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'pro_weight' => 1200,
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
        $product = $this->makeProduct(stock: 10, slug: 'giay-nhap-kho', withVariant: false);

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 1,
                'quantityOthers' => 0,
            ])
            ->assertSessionHasErrors('quantityOthers');

        $this->assertSame(10, $this->stockOf($product, null, null), 'Tồn kho không được đổi khi nhập hàng thất bại');
    }

    public function test_nhap_kho_hop_le_cong_them_vao_ton(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-nhap-them', withVariant: false);

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 1,
                'quantityOthers' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(15, $this->stockOf($product, null, null));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function receiveVariant(int $proId, mixed $quantity, array $overrides = [])
    {
        return $this->actingAs($this->admin)
            ->post(route('stock.store'), array_merge([
                'pro_id' => $proId,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => $quantity],
            ], $overrides));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notWholeNumbers(): array
    {
        return ['số lẻ' => ['1.5'], 'dạng mũ' => ['1e3'], 'chữ' => ['abc']];
    }

    #[DataProvider('notWholeNumbers')]
    public function test_nhap_kho_so_luong_khong_phai_so_nguyen_bi_tu_choi(string $quantity): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-so-le');

        $this->receiveVariant($product->pro_id, $quantity)
            ->assertSessionHasErrors('quantityColorAndSize.1');

        $this->assertSame(10, $this->stockOf($product));
    }

    public function test_nhap_kho_so_luong_vuot_gioi_han_bi_tu_choi(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-qua-nhieu');

        $this->receiveVariant($product->pro_id, '1000000')
            ->assertSessionHasErrors(['quantityColorAndSize.1' => 'Mỗi lần nhập tối đa 999.999!']);
        $this->receiveVariant($product->pro_id, '999999')->assertSessionHasNoErrors();

        $this->assertSame(1_000_009, $this->stockOf($product));
    }

    public function test_nhap_kho_san_pham_khong_ton_tai_bao_loi_thay_vi_500(): void
    {
        $this->receiveVariant(999_999, 5)
            ->assertRedirect()
            ->assertSessionHasErrors(['pro_id' => 'Sản phẩm không tồn tại!']);

        $this->assertSame(0, ProductQuantityModel::where('pro_id', 999_999)->count());
    }

    public function test_nhap_kho_san_pham_da_xoa_bi_tu_choi(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-da-xoa');
        $product->delete();

        $this->receiveVariant($product->pro_id, 5)->assertSessionHasErrors('pro_id');

        $this->assertSame(10, $this->stockOf($product));
    }

    public function test_nhap_kho_mau_hoac_size_khong_ton_tai_bi_tu_choi(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-mau-la');

        $this->receiveVariant($product->pro_id, 5, ['size_id' => [999], 'color_id' => [998], 'quantityColorAndSize' => [998 => 5]])
            ->assertSessionHasErrors(['size_id.0', 'color_id.0']);

        $this->assertSame(1, ProductQuantityModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_nhap_kho_gui_mau_long_mang_bao_loi_thay_vi_500(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-mau-long');

        $this->receiveVariant($product->pro_id, 5, ['color_id' => [[1]]])
            ->assertRedirect()
            ->assertSessionHasErrors('color_id.0');

        $this->assertSame(10, $this->stockOf($product));
    }

    public function test_nhap_kho_khong_ghi_de_luot_ban_xay_ra_cung_luc(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-ban-cung-luc');
        $sold = false;
        ProductQuantityModel::updating(function () use (&$sold, $product) {
            if (! $sold) {
                $sold = true;
                // An order taking stock between the delivery's read and its write.
                DB::table('products_quantity')->where('pro_id', $product->pro_id)->decrement('quantity', 3);
            }
        });

        $this->receiveVariant($product->pro_id, 5)->assertSessionHasNoErrors();

        $this->assertTrue($sold);
        $this->assertSame(12, $this->stockOf($product), '10 tồn - 3 vừa bán + 5 vừa nhập');
    }

    public function test_nhap_kho_thieu_ngay_nhap_bi_chan(): void
    {
        $product = $this->makeProduct(stock: 10, slug: 'giay-thieu-ngay', withVariant: false);

        $this->actingAs($this->admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'pro_type' => 1,
                'quantityOthers' => 5,
            ])
            ->assertSessionHasErrors('quantity_date');

        $this->assertSame(10, $this->stockOf($product, null, null));
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
