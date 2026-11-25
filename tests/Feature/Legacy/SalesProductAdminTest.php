<?php

namespace Tests\Feature\Legacy;

use App\Enums\OrderStatus;
use App\Models\CommentModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin product screens not already covered by the
 * Admin*Test files: access, listing, validation messages, show/hide, hot,
 * delete, trash and restore.
 */
class SalesProductAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Sản phẩm';

    private const STATS_PERMISSION = 'Quản trị Sản phẩm (Thống kê)';

    private const IMAGE_DIR = 'backend/uploads/product';

    private const DELETED = 'Xóa sản phẩm thành công!';

    /** @var list<string> */
    private array $imagesBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->imagesBefore = $this->uploadedImages();
    }

    // The controller moves uploads straight into public/, so anything a test
    // added there is removed again.
    protected function tearDown(): void
    {
        foreach (array_diff($this->uploadedImages(), $this->imagesBefore) as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_bi_dua_ve_trang_dang_nhap_admin(): void
    {
        $product = $this->makeProduct(slug: 'giay-khach');

        $this->get(route('product.index'))->assertRedirect(route('admin.login'));
        $this->post(route('product.update.status', $product->pro_id), ['pro_hidden' => 0])->assertRedirect(route('admin.login'));
        $this->delete(route('product.destroy', $product->pro_id))->assertRedirect(route('admin.login'));

        $this->assertSame(1, (int) $product->refresh()->pro_hidden);
        $this->assertNotSoftDeleted('products', ['pro_id' => $product->pro_id]);
    }

    public function test_nhan_vien_khong_co_quyen_san_pham_bi_tu_choi(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-cam');
        $staff = $this->admin(withPermission: false);

        $this->actingAs($staff)->get(route('product.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('product.create'))->assertForbidden();
        $this->actingAs($staff)->delete(route('product.destroy', $product->pro_id))->assertForbidden();
        $this->actingAs($staff)->get(route('product.trashed'))->assertForbidden();
        $this->actingAs($staff)->patch(route('product.restore.all'))->assertForbidden();
        $this->actingAs($staff)->delete(route('product.delete.all'))->assertForbidden();
        $this->actingAs($staff)->get(route('product.statistical'))->assertForbidden();

        $this->assertNotSoftDeleted('products', ['pro_id' => $product->pro_id]);
    }

    // ------------------------------------------------------------- listing

    public function test_danh_sach_tim_theo_ten_hoac_ma_san_pham(): void
    {
        $this->makeProduct(slug: 'air-force');
        $this->makeProduct(slug: 'ultra-boost');

        $this->actingAs($this->admin())->get(route('product.index'))
            ->assertOk()
            ->assertSee('Quản lý sản phẩm')
            ->assertSee('Sản Phẩm Air-force')
            ->assertSee('Sản Phẩm Ultra-boost')
            ->assertSee('Giày thể thao');

        $this->actingAs($this->admin())->get(route('product.index', ['keyword' => 'SKU-ultra']))
            ->assertOk()
            ->assertSee('Sản Phẩm Ultra-boost')
            ->assertDontSee('Sản Phẩm Air-force');
    }

    // --------------------------------------------------------------- store

    public function test_them_san_pham_thieu_thong_tin_bi_tu_choi_bang_tieng_viet(): void
    {
        $this->actingAs($this->admin())->post(route('product.store'), [])
            ->assertSessionHasErrors([
                'pro_name' => 'Vui lòng nhập tên sản phẩm!',
                'pro_slug' => 'Vui lòng nhập tên sản phẩm!',
                'pro_code' => 'Vui lòng nhập mã sản phẩm!',
                'capital_price' => 'Vui lòng nhập giá vốn sản phẩm!',
                'pro_price' => 'Vui lòng nhập giá bán sản phẩm!',
                'pro_weight' => 'Vui lòng nhập cân nặng sản phẩm!',
                'pro_img' => 'Vui lòng thêm hình ảnh cho sản phẩm!',
                'pro_date' => 'Vui lòng nhập ngày bán sản phẩm!',
                'cate_id' => 'Vui lòng chọn danh mục sản phẩm!',
            ]);

        $this->assertSame(0, ProductModel::count());
    }

    public function test_them_san_pham_trung_ma_va_sai_gia_bi_tu_choi(): void
    {
        $this->makeProduct(slug: 'giay-co-san');

        $this->actingAs($this->admin())->post(route('product.store'), $this->payload([
            'pro_code' => 'SKU-giay-co-san',
            'capital_price' => 'abc',
            'pro_price' => 0,
            'pro_price_sale' => 2_000_000,
            'pro_img' => UploadedFile::fake()->create('giay.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors([
            'pro_code' => 'Mã sản phẩm đã tồn tại!',
            'capital_price' => 'Giá vốn không được nhập chữ!',
            'pro_price' => 'Giá bán phải lớn hơn 0!',
            'pro_price_sale' => 'Giá giảm phải thấp hơn giá bán!',
            'pro_img' => 'File không được hỗ trợ!',
        ]);

        $this->assertSame(1, ProductModel::count());
    }

    public function test_them_san_pham_an_mac_dinh_va_giu_gia_giam(): void
    {
        $this->actingAs($this->admin())->post(route('product.store'), $this->payload([
            'pro_price_sale' => 1_200_000,
            'pro_hidden' => 1,
            'pro_description' => '<p>Êm chân</p>',
            'pro_SEO_title' => 'Giày chạy bộ',
        ]))->assertRedirect(route('product.index'));

        $product = ProductModel::where('pro_slug', 'giay-chay-bo')->firstOrFail();
        $this->assertSame(1_200_000, (int) $product->pro_price_sale);
        $this->assertSame(1, (int) $product->pro_hidden);
        $this->assertSame(0, (int) $product->pro_hot);
        $this->assertSame('<p>Êm chân</p>', $product->pro_description);
        $this->assertSame('Giày chạy bộ', $product->pro_SEO_title);
        $this->assertFileExists(public_path($product->pro_img));
    }

    // -------------------------------------------------------------- update

    public function test_cap_nhat_san_pham_khong_ton_tai_bao_khong_tim_thay(): void
    {
        $this->actingAs($this->admin())->from(route('product.index'))->put(route('product.update', 999), $this->payload())
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', 'Không tìm thấy sản phẩm này!')
            ->assertSessionHas('iconMessage', 'error');
    }

    public function test_cap_nhat_san_pham_kem_anh_moi_thay_anh_va_quay_lai_trang_truoc(): void
    {
        $product = $this->makeProduct(slug: 'giay-doi-anh');

        $this->actingAs($this->admin())->from(route('product.index'))->put(route('product.update', $product->pro_id), $this->payload([
            'pro_name' => $product->pro_name,
            'pro_slug' => $product->pro_slug,
            'pro_code' => $product->pro_code,
            'pro_img' => UploadedFile::fake()->image('anh-moi.png'),
        ]))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', 'Cập nhật sản phẩm thành công!');

        $this->assertStringStartsWith(self::IMAGE_DIR.'/', $product->refresh()->pro_img);
        $this->assertStringEndsWith('-anh-moi.png', $product->pro_img);
    }

    // -------------------------------------------------------- show / hide

    public function test_an_san_pham_thi_an_ca_binh_luan_cua_no(): void
    {
        $product = $this->makeProduct(slug: 'giay-an');
        $other = $this->makeProduct(slug: 'giay-khac');
        $comment = $this->comment($product);
        $otherComment = $this->comment($other);

        $this->actingAs($this->admin())->post(route('product.update.status', $product->pro_id), ['pro_hidden' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);

        $this->assertSame(0, (int) $product->refresh()->pro_hidden);
        $this->assertSame(0, (int) $comment->refresh()->comment_hidden);
        $this->assertSame(1, (int) $otherComment->refresh()->comment_hidden);
    }

    public function test_bat_tat_san_pham_hot(): void
    {
        $product = $this->makeProduct(slug: 'giay-hot');

        $this->actingAs($this->admin())->post(route('product.update.hot', $product->pro_id), ['pro_hot' => 1])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(1, (int) $product->refresh()->pro_hot);

        $this->actingAs($this->admin())->post(route('product.update.hot', $product->pro_id), ['pro_hot' => 0])->assertOk();
        $this->assertSame(0, (int) $product->refresh()->pro_hot);
    }

    public function test_doi_trang_thai_hay_hot_san_pham_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->post(route('product.update.status', 999), ['pro_hidden' => 0])
            ->assertNotFound()
            ->assertJson(['message' => 'Không tìm thấy dữ liệu']);
        $this->actingAs($this->admin())->post(route('product.update.hot', 999), ['pro_hot' => 1])
            ->assertNotFound()
            ->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    // -------------------------------------------------------------- delete

    public function test_khong_xoa_duoc_san_pham_con_hang(): void
    {
        $product = $this->makeProduct(stock: 3, slug: 'giay-con-hang');

        $this->actingAs($this->admin())->from(route('product.index'))->delete(route('product.destroy', $product->pro_id))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', 'Không thể xóa sản phẩm còn hàng trong kho.')
            ->assertSessionHas('iconMessage', 'error');

        $this->assertNotSoftDeleted('products', ['pro_id' => $product->pro_id]);
    }

    public function test_khong_xoa_duoc_san_pham_da_ban(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-da-ban');
        $this->sell($product);

        $this->actingAs($this->admin())->delete(route('product.destroy', $product->pro_id))
            ->assertSessionHas('message', 'Không thể xóa sản phẩm đã bán.');

        $this->assertNotSoftDeleted('products', ['pro_id' => $product->pro_id]);
    }

    public function test_xoa_san_pham_khong_ton_tai_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->delete(route('product.destroy', 999))
            ->assertSessionHas('message', 'Không tồn tại sản phẩm.')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_xoa_san_pham_het_hang_chua_ban_dua_vao_thung_rac_cung_binh_luan(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-het-hang');
        $comment = $this->comment($product);

        $this->actingAs($this->admin())->delete(route('product.destroy', $product->pro_id))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', self::DELETED);

        $this->assertSoftDeleted('products', ['pro_id' => $product->pro_id]);
        $this->assertSoftDeleted('comments', ['comment_id' => $comment->comment_id]);
    }

    // --------------------------------------------------------------- trash

    public function test_thung_rac_chi_hien_san_pham_da_xoa_va_tim_duoc(): void
    {
        $this->makeProduct(slug: 'air-force')->delete();
        $this->makeProduct(slug: 'ultra-boost')->delete();
        $this->makeProduct(slug: 'dang-ban');

        $this->actingAs($this->admin())->get(route('product.trashed'))
            ->assertOk()
            ->assertSee('Sản Phẩm Air-force')
            ->assertSee('Sản Phẩm Ultra-boost')
            ->assertDontSee('Sản Phẩm Dang-ban');

        $this->actingAs($this->admin())->get(route('product.trashed', ['keyword' => 'ultra']))
            ->assertOk()
            ->assertSee('Sản Phẩm Ultra-boost')
            ->assertDontSee('Sản Phẩm Air-force');
    }

    public function test_khoi_phuc_mot_san_pham_va_tat_ca(): void
    {
        $first = $this->makeProduct(slug: 'air-force');
        $second = $this->makeProduct(slug: 'ultra-boost');
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->from(route('product.trashed'))->patch(route('product.restore', $first->pro_id))
            ->assertRedirect(route('product.trashed'))
            ->assertSessionHas('message', 'Khôi phục sản phẩm thành công!');
        $this->assertNotSoftDeleted('products', ['pro_id' => $first->pro_id]);
        $this->assertSoftDeleted('products', ['pro_id' => $second->pro_id]);

        $this->actingAs($this->admin())->patch(route('product.restore.all'))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', 'Khôi phục tất cả sản phẩm thành công!');
        $this->assertNotSoftDeleted('products', ['pro_id' => $second->pro_id]);
    }

    public function test_xoa_vinh_vien_mot_san_pham_va_tat_ca(): void
    {
        $first = $this->makeProduct(stock: 0, slug: 'air-force');
        $second = $this->makeProduct(stock: 0, slug: 'ultra-boost');
        $kept = $this->makeProduct(slug: 'dang-ban');
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->delete(route('product.delete', $first->pro_id))->assertSessionHas('message', self::DELETED);
        $this->assertDatabaseMissing('products', ['pro_id' => $first->pro_id]);
        $this->assertSame(0, ProductQuantityModel::where('pro_id', $first->pro_id)->count());

        $this->actingAs($this->admin())->delete(route('product.delete.all'))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('message', 'Xóa tất cả sản phẩm thành công!');
        $this->assertDatabaseMissing('products', ['pro_id' => $second->pro_id]);
        $this->assertNotNull(ProductModel::find($kept->pro_id));
    }

    public function test_khoi_phuc_hay_xoa_vinh_vien_san_pham_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->patch(route('product.restore', 999))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('product.delete', 999))->assertNotFound();
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $this->makeProduct(slug: 'dang-ban');
        $this->makeProduct(slug: 'da-xoa')->delete();

        $this->actingAs($this->admin())->get(route('product.index'))
            ->assertSee('data-confirm="Bạn muốn đưa sản phẩm này vào thùng rác?"', false);
        $this->actingAs($this->admin())->get(route('product.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả sản phẩm vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa sản phẩm này vĩnh viễn?"', false);
    }

    // ---------------------------------------------------------- statistics

    public function test_thong_ke_san_pham_hien_hang_ban_chay_va_sap_het(): void
    {
        $bestSeller = $this->makeProduct(stock: 50, slug: 'ban-chay');
        $this->makeProduct(stock: 2, slug: 'sap-het');
        $this->sell($bestSeller, quantity: 4);

        $admin = $this->admin(withPermission: false);
        $admin->givePermissionTo(Permission::findOrCreate(self::STATS_PERMISSION, 'web'));

        $this->actingAs($admin)->get(route('product.statistical'))
            ->assertOk()
            ->assertSee('Sản phẩm bán chạy trong tháng')
            ->assertSee('Sản phẩm sắp hết hàng')
            ->assertSee('Sản Phẩm Ban-chay')
            ->assertSee('Sản Phẩm Sap-het');
    }

    private function admin(bool $withPermission = true): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');
        Permission::findOrCreate(self::STATS_PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function comment(ProductModel $product): CommentModel
    {
        return CommentModel::create([
            'comment_content' => 'Giày đẹp',
            'comment_hidden' => 1,
            'comment_date' => now(),
            'pro_id' => $product->pro_id,
        ]);
    }

    private function sell(ProductModel $product, int $quantity = 1): void
    {
        $customer = UserModel::where('email', 'khach@example.test')->first() ?? $this->makeUser();

        $order = OrderModel::create([
            'order_code' => 'SS'.$product->pro_id,
            'order_name' => $customer->name,
            'order_email' => $customer->email,
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 0,
            'order_coupon_value' => 0,
            'order_total' => 1_000_000 * $quantity,
            'order_payment' => 'cod',
            'order_payment_status' => 1,
            'order_date' => now()->toDateString(),
            'order_status' => OrderStatus::Completed,
            'order_completed_at' => now(),
            'user_id' => $customer->user_id,
        ]);

        OrderDetailModel::create([
            'order_id' => $order->order_id,
            'pro_id' => $product->pro_id,
            'pro_name' => $product->pro_name,
            'size' => '42',
            'color' => 'Đen',
            'price' => 1_000_000,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'pro_name' => 'giày chạy bộ',
            'pro_slug' => 'giay-chay-bo',
            'pro_code' => 'SKU-CHAY',
            'pro_price' => 1_500_000,
            'capital_price' => 900_000,
            'pro_weight' => 800,
            'pro_date' => now()->toDateString(),
            'cate_id' => 1,
            'pro_img' => UploadedFile::fake()->image('giay.jpg'),
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function uploadedImages(): array
    {
        return array_values(array_filter(glob(public_path(self::IMAGE_DIR).'/*') ?: [], 'is_file'));
    }
}
