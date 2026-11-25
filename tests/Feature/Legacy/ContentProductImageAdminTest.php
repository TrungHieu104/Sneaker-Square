<?php

namespace Tests\Feature\Legacy;

use App\Models\ImageModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of a product's gallery in the admin: uploading, hiding,
 * trashing and restoring its extra images.
 */
class ContentProductImageAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Sản phẩm';

    private const GALLERY_DIR = 'backend/uploads/product/gallery/';

    // Every file this class uploads carries this in its name, so tearDown
    // removes only what these tests put in public/.
    private const UPLOAD_MARKER = 'content-legacy-test';

    private const PRODUCT_NOT_FOUND = 'Sản phẩm không tồn tại';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->seedLookupTables();
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path(self::GALLERY_DIR.'*'.self::UPLOAD_MARKER.'*')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_khach_chua_dang_nhap_bi_chuyen_ve_dang_nhap_admin(): void
    {
        $product = $this->makeProduct();

        $this->get(route('image.show', $product->pro_slug))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_san_pham_bi_tu_choi(): void
    {
        $staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
        $product = $this->makeProduct();
        $image = $this->image($product);

        $this->actingAs($staff)->get(route('image.show', $product->pro_slug))->assertForbidden();
        $this->actingAs($staff)->put(route('image.update', $image->img_id), ['img_hidden' => 0])->assertForbidden();
        $this->actingAs($staff)->delete(route('image.destroy', $image->img_id))->assertForbidden();

        $this->assertSame(1, (int) $image->refresh()->img_hidden);
        $this->assertNotSoftDeleted($image);
    }

    public function test_trang_anh_san_pham_liet_ke_anh_cua_san_pham(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct(slug: 'san-pham-khac');
        $mine = $this->image($product, 'anh-1.jpg');
        $this->image($other, 'anh-khac.jpg');

        $response = $this->actingAs($this->admin())->get(route('image.show', $product->pro_slug))
            ->assertOk()
            ->assertViewIs('backend.pages.product.image.product_img_list')
            ->assertViewHas('proId', $product->pro_id);

        $this->assertSame([$mine->img_id], collect($response->viewData('allImages')->items())->pluck('img_id')->all());
    }

    public function test_trang_anh_cua_san_pham_chua_co_anh_van_mo_duoc(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin())->get(route('image.show', $product->pro_slug))->assertOk();
    }

    public function test_san_pham_khong_ton_tai_quay_ve_danh_sach_san_pham(): void
    {
        $admin = $this->admin();

        foreach ([['get', 'image.show'], ['get', 'image.trashed'], ['patch', 'image.restore.all'], ['delete', 'image.delete.all']] as [$method, $route]) {
            $this->actingAs($admin)->{$method}(route($route, 'khong-co'))
                ->assertRedirect(route('product.index'))
                ->assertSessionHas('message', self::PRODUCT_NOT_FOUND)
                ->assertSessionHas('iconMessage', 'info');
        }
    }

    public function test_trang_anh_khong_kem_san_pham_quay_ve_danh_sach_san_pham(): void
    {
        $this->actingAs($this->admin())->get(route('image.index'))
            ->assertRedirect(route('product.index'));
    }

    public function test_tai_nhieu_anh_hop_le_luu_vao_thu_vien_san_pham(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin())->from(route('image.show', $product->pro_slug))->post(route('image.store'), [
            'pro_id' => $product->pro_id,
            'img_hidden' => '0',
            'img_name' => [
                UploadedFile::fake()->image(self::UPLOAD_MARKER.'-1.jpg'),
                UploadedFile::fake()->image(self::UPLOAD_MARKER.'-2.png'),
            ],
        ])
            ->assertRedirect(route('image.show', $product->pro_slug))
            ->assertSessionHas('message', 'Thêm hình ảnh thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $images = ImageModel::where('pro_id', $product->pro_id)->orderBy('img_id')->get();
        $this->assertCount(2, $images);
        $this->assertSame([0, 0], $images->pluck('img_hidden')->map(fn ($hidden) => (int) $hidden)->all());
        foreach ($images as $image) {
            $this->assertStringStartsWith(self::GALLERY_DIR, $image->img_name);
            $this->assertStringContainsString(self::UPLOAD_MARKER, $image->img_name);
            $this->assertFileExists(public_path($image->img_name));
        }
    }

    public function test_tai_anh_thieu_file_hoac_sai_dinh_dang_bao_loi_tieng_viet(): void
    {
        $product = $this->makeProduct();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('image.store'), ['pro_id' => $product->pro_id, 'img_hidden' => '1'])
            ->assertSessionHasErrors(['img_name' => 'Vui lòng chọn file hình ảnh cho sản phẩm!']);
        $this->actingAs($admin)->post(route('image.store'), [
            'pro_id' => $product->pro_id,
            'img_hidden' => '1',
            'img_name' => [UploadedFile::fake()->create(self::UPLOAD_MARKER.'.pdf', 10, 'application/pdf')],
        ])->assertSessionHasErrors(['img_name.0' => 'File không được hỗ trợ!']);

        $this->assertDatabaseCount('image', 0);
    }

    public function test_an_hien_anh_tra_json(): void
    {
        $image = $this->image($this->makeProduct());

        $this->actingAs($this->admin())->put(route('image.update', $image->img_id), ['img_hidden' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $image->refresh()->img_hidden);

        $this->actingAs($this->admin())->put(route('image.update', 999), ['img_hidden' => 1])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_xoa_anh_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $show = route('image.show', $this->makeProduct()->pro_slug);

        $this->actingAs($this->admin())->from($show)->delete(route('image.destroy', 999))
            ->assertRedirect($show)
            ->assertSessionHas('message', 'Không tồn tại hình ảnh.')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_thung_rac_anh_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $product = $this->makeProduct();
        $first = $this->image($product, 'anh-1.jpg');
        $second = $this->image($product, 'anh-2.jpg');
        $admin = $this->admin();
        $show = route('image.show', $product->pro_slug);
        $trash = route('image.trashed', $product->pro_slug);

        $this->actingAs($admin)->from($show)->delete(route('image.destroy', $first->img_id))
            ->assertRedirect($show)
            ->assertSessionHas('message', 'Xóa hình ảnh thành công!');
        $this->assertSoftDeleted($first);

        $trashed = $this->actingAs($admin)->get($trash)
            ->assertOk()
            ->assertViewIs('backend.pages.product.image.product_img_trash')
            ->assertViewHas('proSlug', $product->pro_slug)
            ->viewData('imgTrash');
        $this->assertSame([$first->img_id], collect($trashed->items())->pluck('img_id')->all());

        $this->actingAs($admin)->from($trash)->patch(route('image.restore', $first->img_id))
            ->assertRedirect($trash)->assertSessionHas('message', 'Khôi phục hình ảnh thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('image.restore', 999))->assertNotFound();

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->patch(route('image.restore.all', $product->pro_slug))
            ->assertRedirect($show)->assertSessionHas('message', 'Khôi phục tất cả hình ảnh thành công!');
        $this->assertSame(0, ImageModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($trash)->delete(route('image.delete', $first->img_id))
            ->assertRedirect($trash)->assertSessionHas('message', 'Xóa hình ảnh thành công!');
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('image.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->delete(route('image.delete.all', $product->pro_slug))
            ->assertRedirect($show)->assertSessionHas('message', 'Xóa tất cả hình ảnh thành công!');
        $this->assertModelMissing($second);
    }

    public function test_khoi_phuc_va_xoa_tat_ca_chi_dong_vao_anh_cua_san_pham_do(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct(slug: 'san-pham-khac');
        $mine = $this->image($product, 'anh-1.jpg');
        $theirs = $this->image($other, 'anh-khac.jpg');
        $mine->delete();
        $theirs->delete();

        $this->actingAs($this->admin())->patch(route('image.restore.all', $product->pro_slug))->assertRedirect();
        $this->assertNotSoftDeleted($mine);
        $this->assertSoftDeleted($theirs);

        $mine->delete();
        $this->actingAs($this->admin())->delete(route('image.delete.all', $other->pro_slug))->assertRedirect();
        $this->assertModelMissing($theirs);
        $this->assertSoftDeleted($mine);
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $product = $this->makeProduct();
        $kept = $this->image($product, 'anh-1.jpg');
        $this->image($product, 'anh-2.jpg')->delete();

        $this->actingAs($this->admin())->get(route('image.show', $product->pro_slug))
            ->assertSee('data-confirm="Bạn muốn đưa hình ảnh này vào thùng rác?"', false);
        $this->actingAs($this->admin())->get(route('image.trashed', $product->pro_slug))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả hình ảnh của sản phẩm này vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa hình ảnh này vĩnh viễn?"', false);
        $this->assertNotSoftDeleted($kept);
    }

    // -------------------------------------------------------------- helpers

    private function admin(): UserModel
    {
        $admin = UserModel::where('username', 'quantri')->first()
            ?? $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::PERMISSION, 'web'));

        return $admin;
    }

    private function image(ProductModel $product, string $name = 'anh.jpg'): ImageModel
    {
        return ImageModel::create([
            'img_name' => self::GALLERY_DIR.$product->pro_slug.'-'.$name,
            'pro_id' => $product->pro_id,
            'img_hidden' => 1,
        ]);
    }
}
