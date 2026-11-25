<?php

namespace Tests\Feature\Legacy;

use App\Models\CategoryModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the product category screens in the admin, pinned
 * before a refactor.
 */
class SalesProductCategoryTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Sản phẩm';

    private const IMAGE_DIR = 'backend/uploads/product/category';

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
        $this->get(route('product-category.index'))->assertRedirect(route('admin.login'));
        $this->post(route('product-category.store'), $this->payload())->assertRedirect(route('admin.login'));

        $this->assertSame(1, CategoryModel::count());
    }

    public function test_khach_hang_thuong_khong_vao_duoc_danh_muc(): void
    {
        $this->actingAs($this->makeUser())->get(route('product-category.index'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', 'Không được phép truy cập');
    }

    public function test_nhan_vien_khong_co_quyen_san_pham_bi_tu_choi(): void
    {
        $staff = $this->admin(withPermission: false);

        $this->actingAs($staff)->get(route('product-category.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('product-category.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('product-category.store'), $this->payload())->assertForbidden();
        $this->actingAs($staff)->post(route('product-category.update.status', 1), ['cate_hidden' => 0])->assertForbidden();
        $this->actingAs($staff)->delete(route('product-category.destroy', 1))->assertForbidden();
        $this->actingAs($staff)->get(route('product-category.trashed'))->assertForbidden();

        $this->assertSame(1, CategoryModel::count());
        $this->assertSame(1, (int) CategoryModel::find(1)->cate_hidden);
    }

    // ------------------------------------------------------------- listing

    public function test_danh_sach_hien_danh_muc_cha_con_va_tim_theo_ten(): void
    {
        $this->category('Giày chạy bộ', 2, parentId: 1);
        $this->category('Dép quai hậu', 3);

        $this->actingAs($this->admin())->get(route('product-category.index'))
            ->assertOk()
            ->assertSee('Quản lý danh mục')
            ->assertSee('Giày thể thao')
            ->assertSee('Giày chạy bộ')
            ->assertSee('Dép quai hậu');

        $this->actingAs($this->admin())->get(route('product-category.index', ['keyword' => 'Dép']))
            ->assertOk()
            ->assertSee('Dép quai hậu')
            ->assertDontSee('Giày chạy bộ');
    }

    public function test_trang_them_danh_muc_mo_duoc(): void
    {
        $this->actingAs($this->admin())->get(route('product-category.create'))
            ->assertOk()
            ->assertSee('name="cate_name"', false)
            ->assertSee('Giày thể thao');
    }

    // --------------------------------------------------------------- store

    public function test_them_danh_muc_hop_le_viet_hoa_ten_va_khong_co_cha(): void
    {
        $this->actingAs($this->admin())->post(route('product-category.store'), $this->payload())
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Thêm danh mục thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $cate = CategoryModel::where('cate_slug', 'dep-sandal')->firstOrFail();
        $this->assertSame('Dép Sandal', $cate->cate_name);
        $this->assertSame(5, (int) $cate->cate_sort);
        $this->assertSame('dép, sandal', $cate->cate_meta_keywords);
        $this->assertNull($cate->cate_parent_id);
        $this->assertSame(1, (int) $cate->cate_hidden);
        $this->assertNull($cate->cate_img);
    }

    public function test_them_danh_muc_con_khong_gui_trang_thai_thi_an(): void
    {
        $payload = $this->payload(['cate_parent_id' => 1]);
        unset($payload['cate_hidden']);

        $this->actingAs($this->admin())->post(route('product-category.store'), $payload)
            ->assertRedirect(route('product-category.index'));

        $cate = CategoryModel::where('cate_slug', 'dep-sandal')->firstOrFail();
        $this->assertSame(1, (int) $cate->cate_parent_id);
        $this->assertSame(0, (int) $cate->cate_hidden);
    }

    public function test_them_danh_muc_kem_anh_luu_duong_dan_anh(): void
    {
        $this->actingAs($this->admin())->post(route('product-category.store'), $this->payload([
            'cate_img' => UploadedFile::fake()->image('logo.jpg'),
        ]))->assertSessionHasNoErrors();

        $cate = CategoryModel::where('cate_slug', 'dep-sandal')->firstOrFail();
        $this->assertStringStartsWith(self::IMAGE_DIR.'/', $cate->cate_img);
        $this->assertStringEndsWith('-logo.jpg', $cate->cate_img);
        $this->assertFileExists(public_path($cate->cate_img));
    }

    public function test_them_danh_muc_thieu_ten_va_thu_tu_bi_tu_choi(): void
    {
        $this->actingAs($this->admin())->post(route('product-category.store'), ['cate_name' => '', 'cate_sort' => ''])
            ->assertSessionHasErrors([
                'cate_name' => 'Vui lòng nhập tên danh mục!',
                'cate_sort' => 'Vui lòng nhập số thứ tự!',
            ]);

        $this->assertSame(1, CategoryModel::count());
    }

    public function test_ten_va_thu_tu_trung_bi_tu_choi_sau_khi_viet_hoa_ten(): void
    {
        $this->category('Giày Chạy Bộ', 2);

        $this->actingAs($this->admin())->post(route('product-category.store'), $this->payload([
            'cate_name' => 'giày chạy bộ',
            'cate_sort' => 2,
        ]))->assertSessionHasErrors([
            'cate_name' => 'Tên danh mục đã tồn tại!',
            'cate_sort' => 'Số thứ tự đã tồn tại!',
        ]);
    }

    public function test_thu_tu_am_chu_hoac_so_le_bi_tu_choi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('product-category.store'), $this->payload(['cate_sort' => -1]))
            ->assertSessionHasErrors(['cate_sort' => 'Số thứ tự không được là số âm!']);
        $this->actingAs($admin)->post(route('product-category.store'), $this->payload(['cate_sort' => 'abc']))
            ->assertSessionHasErrors(['cate_sort' => 'Số thứ tự không được nhập chữ!']);
        $this->actingAs($admin)->post(route('product-category.store'), $this->payload(['cate_sort' => 1.5]))
            ->assertSessionHasErrors(['cate_sort' => 'Số thứ tự phải là số nguyên!']);

        $this->assertSame(1, CategoryModel::count());
    }

    public function test_anh_khong_phai_hinh_bi_tu_choi(): void
    {
        $this->actingAs($this->admin())->post(route('product-category.store'), $this->payload([
            'cate_img' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['cate_img' => 'File không được hỗ trợ!']);

        $this->assertSame(1, CategoryModel::count());
    }

    // -------------------------------------------------------------- update

    public function test_sua_danh_muc_luu_cac_truong_va_quay_lai_trang_truoc(): void
    {
        $cate = $this->category('Giày chạy bộ', 2);

        $this->actingAs($this->admin())
            ->from(route('product-category.index'))
            ->put(route('product-category.update', $cate->cate_id), $this->payload([
                'cate_name' => 'giày chạy trail',
                'cate_slug' => 'giay-chay-trail',
                'cate_sort' => 7,
                'cate_parent_id' => 1,
            ]))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Cập nhật danh mục thành công!');

        $cate->refresh();
        $this->assertSame('Giày Chạy Trail', $cate->cate_name);
        $this->assertSame('giay-chay-trail', $cate->cate_slug);
        $this->assertSame(7, (int) $cate->cate_sort);
        $this->assertSame(1, (int) $cate->cate_parent_id);
    }

    public function test_sua_danh_muc_kem_anh_moi_thay_duong_dan_anh(): void
    {
        $cate = $this->category('Giày chạy bộ', 2);

        $this->actingAs($this->admin())->put(route('product-category.update', $cate->cate_id), [
            'cate_name' => 'Giày chạy bộ',
            'cate_sort' => 2,
            'cate_img' => UploadedFile::fake()->image('moi.png'),
        ])->assertSessionHasNoErrors();

        $this->assertStringEndsWith('-moi.png', (string) $cate->refresh()->cate_img);
        $this->assertNull($cate->cate_parent_id);
    }

    public function test_sua_danh_muc_lay_ten_cua_danh_muc_khac_bi_tu_choi(): void
    {
        $this->category('Giày Lười', 4);
        $cate = $this->category('Giày chạy bộ', 2);

        $this->actingAs($this->admin())->put(route('product-category.update', $cate->cate_id), [
            'cate_name' => 'giày lười',
            'cate_sort' => 2,
        ])->assertSessionHasErrors(['cate_name' => 'Tên danh mục đã tồn tại!']);

        $this->assertSame('Giày chạy bộ', $cate->refresh()->cate_name);
    }

    public function test_sua_danh_muc_khong_ton_tai_bao_khong_tim_thay(): void
    {
        $this->actingAs($this->admin())
            ->from(route('product-category.index'))
            ->put(route('product-category.update', 999), $this->payload())
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Không tìm thấy danh mục này!')
            ->assertSessionHas('iconMessage', 'error');
    }

    // -------------------------------------------------------- show / hide

    public function test_an_danh_muc_cha_thi_an_ca_danh_muc_con(): void
    {
        $child = $this->category('Giày chạy bộ', 2, parentId: 1);
        $other = $this->category('Dép quai hậu', 3);

        $this->actingAs($this->admin())->post(route('product-category.update.status', 1), ['cate_hidden' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);

        $this->assertSame(0, (int) CategoryModel::find(1)->cate_hidden);
        $this->assertSame(0, (int) $child->refresh()->cate_hidden);
        $this->assertSame(1, (int) $other->refresh()->cate_hidden);

        $this->actingAs($this->admin())->post(route('product-category.update.status', 1), ['cate_hidden' => 1])->assertOk();
        $this->assertSame(1, (int) $child->refresh()->cate_hidden);
    }

    public function test_doi_trang_thai_danh_muc_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->post(route('product-category.update.status', 999), ['cate_hidden' => 0])
            ->assertNotFound()
            ->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    // -------------------------------------------------------------- delete

    public function test_khong_xoa_duoc_danh_muc_co_danh_muc_con(): void
    {
        $this->category('Giày chạy bộ', 2, parentId: 1);

        $this->actingAs($this->admin())->from(route('product-category.index'))->delete(route('product-category.destroy', 1))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Không thể xóa danh mục gốc!')
            ->assertSessionHas('iconMessage', 'error');

        $this->assertNotNull(CategoryModel::find(1));
    }

    public function test_khong_xoa_duoc_danh_muc_dang_co_san_pham(): void
    {
        $this->makeProduct(slug: 'giay-trong-danh-muc');

        $this->actingAs($this->admin())->delete(route('product-category.destroy', 1))
            ->assertSessionHas('message', 'Không thể xóa danh mục đang có sản phẩm!');

        $this->assertNotNull(CategoryModel::find(1));
    }

    public function test_xoa_danh_muc_rong_dua_vao_thung_rac(): void
    {
        $cate = $this->category('Dép quai hậu', 3);

        $this->actingAs($this->admin())->delete(route('product-category.destroy', $cate->cate_id))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Xóa danh mục thành công!');

        $this->assertSoftDeleted('category', ['cate_id' => $cate->cate_id]);
    }

    public function test_xoa_danh_muc_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('product-category.index'))->delete(route('product-category.destroy', 999))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Không tồn tại danh mục.')
            ->assertSessionHas('iconMessage', 'info');
    }

    // --------------------------------------------------------------- trash

    public function test_thung_rac_chi_hien_danh_muc_da_xoa_va_tim_duoc(): void
    {
        $this->category('Dép quai hậu', 3)->delete();
        $this->category('Giày lười', 4)->delete();

        $this->actingAs($this->admin())->get(route('product-category.trashed'))
            ->assertOk()
            ->assertSee('Dép quai hậu')
            ->assertSee('Giày lười')
            ->assertDontSee('Giày thể thao');

        $this->actingAs($this->admin())->get(route('product-category.trashed', ['keyword' => 'lười']))
            ->assertOk()
            ->assertSee('Giày lười')
            ->assertDontSee('Dép quai hậu');
    }

    public function test_khoi_phuc_mot_danh_muc_va_tat_ca(): void
    {
        $first = $this->category('Dép quai hậu', 3);
        $second = $this->category('Giày lười', 4);
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->from(route('product-category.trashed'))->patch(route('product-category.restore', $first->cate_id))
            ->assertRedirect(route('product-category.trashed'))
            ->assertSessionHas('message', 'Khôi phục danh mục thành công!');
        $this->assertNotSoftDeleted('category', ['cate_id' => $first->cate_id]);
        $this->assertSoftDeleted('category', ['cate_id' => $second->cate_id]);

        $this->actingAs($this->admin())->patch(route('product-category.restore.all'))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Khôi phục tất cả danh mục thành công!');
        $this->assertNotSoftDeleted('category', ['cate_id' => $second->cate_id]);
    }

    public function test_xoa_vinh_vien_mot_danh_muc_va_tat_ca(): void
    {
        $first = $this->category('Dép quai hậu', 3);
        $second = $this->category('Giày lười', 4);
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->delete(route('product-category.delete', $first->cate_id))
            ->assertSessionHas('message', 'Xóa danh mục thành công!');
        $this->assertDatabaseMissing('category', ['cate_id' => $first->cate_id]);

        $this->actingAs($this->admin())->delete(route('product-category.delete.all'))
            ->assertRedirect(route('product-category.index'))
            ->assertSessionHas('message', 'Xóa tất cả danh mục thành công!');
        $this->assertDatabaseMissing('category', ['cate_id' => $second->cate_id]);
        $this->assertNotNull(CategoryModel::find(1));
    }

    public function test_khoi_phuc_hay_xoa_vinh_vien_danh_muc_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->patch(route('product-category.restore', 999))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('product-category.delete', 999))->assertNotFound();
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $this->category('Dép quai hậu', 3)->delete();

        $this->actingAs($this->admin())->get(route('product-category.index'))
            ->assertSee('data-confirm="Bạn muốn đưa danh mục này vào thùng rác?"', false);
        $this->actingAs($this->admin())->get(route('product-category.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả danh mục vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa danh mục này vĩnh viễn?"', false);
    }

    public function test_xoa_vinh_vien_danh_muc_con_san_pham_trong_thung_rac_bi_chan(): void
    {
        $admin = $this->admin();
        $category = $this->category('Giày chạy bộ', 5);
        $product = $this->makeProduct(slug: 'giay-trong-thung-rac');
        $product->forceFill(['cate_id' => $category->cate_id])->save();
        $product->delete();
        $category->delete();

        $this->actingAs($admin)->delete(route('product-category.delete', $category->cate_id))
            ->assertSessionHas('message', 'Danh mục còn sản phẩm hoặc danh mục con, kể cả trong thùng rác. Hãy xóa vĩnh viễn chúng trước!');
        $this->actingAs($admin)->delete(route('product-category.delete.all'))
            ->assertSessionHas('message', 'Còn 1 danh mục có sản phẩm hoặc danh mục con nên được giữ lại trong thùng rác.');

        $this->assertSoftDeleted($category);
    }

    private function admin(bool $withPermission = true): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function category(string $name, int $sort, ?int $parentId = null): CategoryModel
    {
        return CategoryModel::create([
            'cate_name' => $name,
            'cate_slug' => str($name)->slug()->toString(),
            'cate_sort' => $sort,
            'cate_parent_id' => $parentId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'cate_name' => 'dép sandal',
            'cate_slug' => 'dep-sandal',
            'cate_sort' => 5,
            'cate_meta_keywords' => 'dép, sandal',
            'cate_parent_id' => '',
            'cate_hidden' => 1,
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function uploadedImages(): array
    {
        return glob(public_path(self::IMAGE_DIR).'/*') ?: [];
    }
}
