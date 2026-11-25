<?php

namespace Tests\Feature\Legacy;

use App\Models\CateSlideModel;
use App\Models\PromotionModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin screens for slide categories, the groups the
 * storefront banners are filed under.
 */
class ContentSlideCategoryAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Slide';

    private const DELETED = 'Xóa thành công!';

    private const EMPTY_TRASH = 'Không có dữ liệu trong thùng rác!';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_khach_chua_dang_nhap_bi_chuyen_ve_dang_nhap_admin(): void
    {
        $this->get(route('cate-slide.index'))->assertRedirect(route('admin.login'));
        $this->get(route('cate_slide.trashed'))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_slide_bi_tu_choi(): void
    {
        $staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($staff)->get(route('cate-slide.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('cate-slide.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('cate-slide.store'), ['name' => 'Banner mới'])->assertForbidden();
        $this->actingAs($staff)->delete(route('cate_slide.softDelete', $cate->cate_slide_id))->assertForbidden();

        $this->assertDatabaseCount('cate_slide', 1);
        $this->assertNotSoftDeleted($cate);
    }

    public function test_danh_sach_danh_muc_slide_va_tim_kiem(): void
    {
        $this->cate('Banner trang chủ');
        $this->cate('Quảng cáo giữa trang', 'quang-cao');

        $list = $this->actingAs($this->admin())->get(route('cate-slide.index', ['keyword' => 'Quảng']))
            ->assertOk()
            ->assertViewIs('backend.pages.slide.slide_cate.slide_cate_list')
            ->assertSee('Quảng cáo giữa trang')
            ->viewData('cateSlide');

        $this->assertSame(['Quảng cáo giữa trang'], collect($list->items())->pluck('cate_slide_name')->all());
    }

    public function test_trang_them_va_sua_danh_muc_slide(): void
    {
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($this->admin())->get(route('cate-slide.create'))
            ->assertOk()->assertViewIs('backend.pages.slide.slide_cate.slide_cate_create');
        $this->actingAs($this->admin())->get(route('cate-slide.edit', $cate->cate_slide_id))
            ->assertOk()->assertViewIs('backend.pages.slide.slide_cate.slide_cate_edit')->assertSee('Banner trang chủ');
        $this->actingAs($this->admin())->get(route('cate-slide.edit', 999))
            ->assertRedirect('admin/cate-slide')
            ->assertSessionHas('message', 'Không tồn tại thông tin liên hệ');
    }

    public function test_them_danh_muc_slide_hop_le(): void
    {
        $this->actingAs($this->admin())->post(route('cate-slide.store'), ['name' => 'Banner trang chủ', 'slug' => 'banner-trang-chu', 'status' => '1'])
            ->assertRedirect(route('cate-slide.index'))
            ->assertSessionHas('message', 'Thêm mới thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertDatabaseHas('cate_slide', ['cate_slide_name' => 'Banner trang chủ', 'cate_slide_slug' => 'banner-trang-chu', 'cate_slide_hidden' => 1]);

        $this->actingAs($this->admin())->post(route('cate-slide.store'), ['name' => 'Banner phụ', 'slug' => 'banner-phu'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cate_slide', ['cate_slide_name' => 'Banner phụ', 'cate_slide_hidden' => 0]);
    }

    public function test_them_danh_muc_slide_sai_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->cate('Banner trang chủ');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('cate-slide.store'), [])
            ->assertSessionHasErrors(['name' => 'Vui lòng nhập tiêu đề!']);
        $this->actingAs($admin)->post(route('cate-slide.store'), ['name' => 'ab'])
            ->assertSessionHasErrors(['name' => 'Tiêu đề quá ngắn!']);
        $this->actingAs($admin)->post(route('cate-slide.store'), ['name' => str_repeat('a', 51)])
            ->assertSessionHasErrors(['name' => 'Tên quá dài!']);
        $this->actingAs($admin)->post(route('cate-slide.store'), ['name' => 'Banner trang chủ'])
            ->assertSessionHasErrors(['name' => 'Tiêu đề đã tồn tại!']);

        $this->assertDatabaseCount('cate_slide', 1);
    }

    public function test_cap_nhat_danh_muc_slide_giu_ten_cu_thi_duoc(): void
    {
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($this->admin())->put(route('cate-slide.update', $cate->cate_slide_id), [
            'id' => $cate->cate_slide_id, 'name' => 'Banner trang chủ', 'slug' => 'banner-moi',
        ])
            ->assertRedirect(route('cate-slide.index'))
            ->assertSessionHas('message', 'Chỉnh sửa thành công!');

        $cate->refresh();
        $this->assertSame('banner-moi', $cate->cate_slide_slug);
        $this->assertSame(0, (int) $cate->cate_slide_hidden);
    }

    public function test_cap_nhat_danh_muc_slide_trung_ten_danh_muc_khac_bi_tu_choi(): void
    {
        $this->cate('Quảng cáo giữa trang', 'quang-cao');
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($this->admin())->put(route('cate-slide.update', $cate->cate_slide_id), [
            'id' => $cate->cate_slide_id, 'name' => 'Quảng cáo giữa trang',
        ])->assertSessionHasErrors(['name' => 'Tên danh mục đã tồn tại!']);
        $this->actingAs($this->admin())->put(route('cate-slide.update', $cate->cate_slide_id), ['id' => $cate->cate_slide_id])
            ->assertSessionHasErrors(['name' => 'Vui lòng nhập tiêu đề!']);

        $this->assertSame('Banner trang chủ', $cate->refresh()->cate_slide_name);
    }

    public function test_cap_nhat_danh_muc_slide_khong_ton_tai_quay_ve_danh_sach_danh_muc(): void
    {
        $this->actingAs($this->admin())->put(route('cate-slide.update', 999), [
            'id' => 999, 'name' => 'Danh mục đã bị xoá', 'slug' => 'da-bi-xoa',
        ])
            ->assertRedirect(route('cate-slide.index'))
            ->assertSessionHas('message', 'Không tồn tại danh mục')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_xoa_danh_muc_slide_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('cate-slide.index'))->delete(route('cate_slide.softDelete', 999))
            ->assertRedirect(route('cate-slide.index'))
            ->assertSessionHas('message', 'Không tồn tại danh mục!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_an_hien_danh_muc_slide_tra_json(): void
    {
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($this->admin())->post(route('cate_slide.status', $cate->cate_slide_id), ['status' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $cate->refresh()->cate_slide_hidden);

        $this->actingAs($this->admin())->post(route('cate_slide.status', 999), ['status' => 1])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_khong_xoa_duoc_danh_muc_dang_co_slide(): void
    {
        $cate = $this->cate('Banner trang chủ');
        $this->promotion($cate);

        $this->actingAs($this->admin())->delete(route('cate_slide.softDelete', $cate->cate_slide_id))
            ->assertRedirect('admin/cate-slide')
            ->assertSessionHas('message', 'Không thể xóa danh mục!')
            ->assertSessionHas('iconMessage', 'info');

        $this->assertNotSoftDeleted($cate);
    }

    public function test_thung_rac_danh_muc_slide_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $first = $this->cate('Banner trang chủ');
        $second = $this->cate('Quảng cáo giữa trang', 'quang-cao');
        $admin = $this->admin();
        $from = route('cate_slide.trashed');

        $this->actingAs($admin)->from(route('cate-slide.index'))->delete(route('cate_slide.softDelete', $first->cate_slide_id))
            ->assertRedirect(route('cate-slide.index'))
            ->assertSessionHas('message', self::DELETED);
        $this->assertSoftDeleted($first);

        $trash = $this->actingAs($admin)->get($from)
            ->assertOk()->assertViewIs('backend.pages.slide.slide_cate.slide_cate_trash')->assertSee('Banner trang chủ')
            ->viewData('cateSlides');
        $this->assertSame([$first->cate_slide_id], collect($trash->items())->pluck('cate_slide_id')->all());

        $this->actingAs($admin)->from($from)->patch(route('cate_slide.restore', $first->cate_slide_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('cate_slide.restore', 999))->assertNotFound();

        $this->actingAs($admin)->from($from)->patch(route('cate_slide.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', self::EMPTY_TRASH)->assertSessionHas('iconMessage', 'info');
        $this->actingAs($admin)->from($from)->delete(route('cate_slide.delete.all'))
            ->assertRedirect($from)->assertSessionHas('message', self::EMPTY_TRASH);

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->from($from)->patch(route('cate_slide.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertSame(0, CateSlideModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($from)->delete(route('cate_slide.delete', $first->cate_slide_id))
            ->assertRedirect($from)->assertSessionHas('message', self::DELETED);
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('cate_slide.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->from($from)->delete(route('cate_slide.delete.all'))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa danh mục thành công!');
        $this->assertModelMissing($second);
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $admin = $this->admin();
        $cate = $this->cate('Banner trang chủ');

        $this->actingAs($admin)->get(route('cate-slide.index'))->assertSee('data-confirm="Bạn có chắc muốn xóa?"', false);

        $cate->delete();
        $this->actingAs($admin)->get(route('cate_slide.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả danh mục slide vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa danh mục slide này vĩnh viễn?"', false);
    }

    public function test_xoa_vinh_vien_danh_muc_con_slide_trong_thung_rac_bi_chan(): void
    {
        $admin = $this->admin();
        $cate = $this->cate('Banner trang chủ');
        $this->promotion($cate)->delete();
        $cate->delete();
        $unused = $this->cate('Banner cũ', 'banner-cu');
        $unused->delete();

        $this->actingAs($admin)->delete(route('cate_slide.delete', $cate->cate_slide_id))
            ->assertRedirect()
            ->assertSessionHas('message', 'Danh mục còn slide, kể cả trong thùng rác. Hãy xóa vĩnh viễn các slide đó trước!');
        $this->assertSoftDeleted($cate);

        $this->actingAs($admin)->delete(route('cate_slide.delete.all'))
            ->assertSessionHas('message', 'Còn 1 danh mục có slide nên được giữ lại trong thùng rác.');
        $this->assertSoftDeleted($cate);
        $this->assertNull(CateSlideModel::withTrashed()->find($unused->cate_slide_id));
    }

    // -------------------------------------------------------------- helpers

    private function admin(): UserModel
    {
        $admin = UserModel::where('username', 'quantri')->first()
            ?? $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::PERMISSION, 'web'));

        return $admin;
    }

    private function cate(string $name, string $slug = 'banner-trang-chu'): CateSlideModel
    {
        return CateSlideModel::create(['cate_slide_name' => $name, 'cate_slide_slug' => $slug, 'cate_slide_hidden' => 1]);
    }

    private function promotion(CateSlideModel $cate): PromotionModel
    {
        return PromotionModel::create([
            'type' => 'slide',
            'promotion_name' => 'Giảm giá mùa hè',
            'promotion_img' => 'backend/uploads/slide/he.jpg',
            'promotion_link' => '/san-pham',
            'promotion_sort' => 1,
            'promotion_content' => 'Giảm đến 50%',
            'promotion_date' => now(),
            'promotion_start' => today()->toDateString(),
            'promotion_end' => today()->addMonth()->toDateString(),
            'cate_slide_id' => $cate->cate_slide_id,
        ]);
    }
}
