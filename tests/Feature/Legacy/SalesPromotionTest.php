<?php

namespace Tests\Feature\Legacy;

use App\Models\CateSlideModel;
use App\Models\PromotionModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin screens for homepage slides (promotions),
 * pinned before a refactor.
 */
class SalesPromotionTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Slide';

    private const IMAGE_DIR = 'backend/uploads/slide';

    private const LIST_URL = '/admin/promotion';

    private const TITLE = 'Sale mùa hè';

    /** @var list<string> */
    private array $imagesBefore = [];

    private CateSlideModel $slideCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagesBefore = $this->uploadedImages();
        $this->slideCategory = CateSlideModel::create([
            'cate_slide_name' => 'Banner trang chủ',
            'cate_slide_slug' => 'banner-trang-chu',
            'cate_slide_hidden' => 1,
        ]);
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
        $this->get(route('promotion.index'))->assertRedirect(route('admin.login'));
        $this->get(route('promotion.trashed'))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_slide_bi_tu_choi(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);
        $staff = $this->admin(withPermission: false);

        $this->actingAs($staff)->get(route('promotion.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('promotion.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('promotion.edit', $promotion->promotion_id))->assertForbidden();
        $this->actingAs($staff)->put(route('promotion.update', $promotion->promotion_id), $this->updatePayload($promotion))->assertForbidden();
        $this->actingAs($staff)->delete(route('promotion.softDelete', $promotion->promotion_id))->assertForbidden();
        $this->actingAs($staff)->get(route('promotion.trashed'))->assertForbidden();
        $this->actingAs($staff)->delete(route('promotion.delete', $promotion->promotion_id))->assertForbidden();

        $this->assertNotSoftDeleted('promotion', ['promotion_id' => $promotion->promotion_id]);
        $this->assertSame(self::TITLE, $promotion->refresh()->promotion_name);
    }

    // ------------------------------------------------------------- listing

    public function test_danh_sach_hien_slide_ten_danh_muc_va_tim_theo_tieu_de(): void
    {
        $this->promotion(self::TITLE, 1);
        $this->promotion('Ra mắt Air Max', 2);

        $this->actingAs($this->admin())->get(route('promotion.index'))
            ->assertOk()
            ->assertSee('Quản lý nội dung Slide')
            ->assertSee(self::TITLE)
            ->assertSee('Ra mắt Air Max')
            ->assertSee('Banner trang chủ');

        $this->actingAs($this->admin())->get(route('promotion.index', ['keyword' => 'Air Max']))
            ->assertOk()
            ->assertSee('Ra mắt Air Max')
            ->assertDontSee(self::TITLE);
    }

    public function test_danh_sach_ghi_con_han_hay_het_han_theo_ngay_ket_thuc(): void
    {
        $this->promotion(self::TITLE, 1, end: now()->addDays(5)->toDateString());

        $this->actingAs($this->admin())->get(route('promotion.index'))->assertSee('Còn hạn');

        PromotionModel::query()->update(['promotion_end' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin())->get(route('promotion.index'))->assertSee('Hết hạn');
    }

    public function test_trang_them_slide_mo_duoc_va_dien_san_ngay_hom_nay(): void
    {
        $this->actingAs($this->admin())->get(route('promotion.create'))
            ->assertOk()
            ->assertSee('value="'.today()->format('d-m-Y').'"', false)
            ->assertSee('Banner trang chủ');
    }

    // --------------------------------------------------------------- store

    public function test_them_slide_thieu_thong_tin_bi_tu_choi_bang_tieng_viet(): void
    {
        $this->actingAs($this->admin())->post(route('promotion.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Vui lòng nhập tiêu đề Slide!',
                'start_pro' => 'Vui lòng chọn ngày bắt đầu!',
                'end_pro' => 'Vui lòng chọn ngày kết thúc!',
                'position' => 'Vui lòng nhập số lượng!',
                'img' => 'Vui lòng chọn hình ảnh!',
                'cate' => 'Vui lòng tạo danh mục Slide!',
                'url' => 'Vui lòng nhập đường dẫn!',
                'type' => 'Vui lòng nhập thể loại!',
                'contents' => 'Vui lòng nhập nội dung!',
            ]);

        $this->assertSame(0, PromotionModel::count());
    }

    public function test_them_slide_tieu_de_ngan_trung_va_ngay_qua_khu_bi_tu_choi(): void
    {
        $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->post(route('promotion.store'), [
            'name' => self::TITLE,
            'start_pro' => now()->subDays(2)->toDateString(),
            'end_pro' => now()->subDay()->toDateString(),
            'position' => 1,
            'contents' => 'ab',
        ])->assertSessionHasErrors([
            'name' => 'Tiều đề đã tồn tại!',
            'start_pro' => 'Ngày bắt đầu phải bằng hoặc lớn hơn ngày hiện tại!',
            'position' => 'Số thứ tự này đã tồn tại!',
            'contents' => 'Nội dung quá ngắn!',
        ]);

        $this->actingAs($this->admin())->post(route('promotion.store'), ['name' => 'ab', 'position' => 'abc'])
            ->assertSessionHasErrors([
                'name' => 'Tiêu đề quá ngắn',
                'position' => 'Số lượng mã không được nhập chữ!',
            ]);

        $this->assertSame(1, PromotionModel::count());
    }

    // ---------------------------------------------------------------- edit

    public function test_trang_sua_slide_hien_du_lieu_cu(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->get(route('promotion.edit', $promotion->promotion_id))
            ->assertOk()
            ->assertSee(self::TITLE)
            ->assertSee('name="id" value="'.$promotion->promotion_id.'"', false);
    }

    public function test_sua_slide_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->get(route('promotion.edit', 999))
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Không tồn tại nội dung');
    }

    public function test_cap_nhat_slide_luu_cac_truong_va_doi_dinh_dang_ngay(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->put(route('promotion.update', $promotion->promotion_id), $this->updatePayload($promotion, [
            'name' => 'Sale cuối năm',
            'type' => 'banner',
            'url' => '/san-pham-sale',
            'position' => 9,
            'start_pro' => '01-12-2026',
            'end_pro' => '31-12-2026',
            'contents' => 'Giảm đến 50%',
            'note' => 'Chạy cả tháng 12',
            'status' => 0,
        ]))
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Chỉnh sửa thành công');

        $promotion->refresh();
        $this->assertSame('Sale cuối năm', $promotion->promotion_name);
        $this->assertSame('banner', $promotion->type);
        $this->assertSame('/san-pham-sale', $promotion->promotion_link);
        $this->assertSame(9, (int) $promotion->promotion_sort);
        $this->assertSame('2026-12-01', date('Y-m-d', strtotime((string) $promotion->promotion_start)));
        $this->assertSame('2026-12-31', date('Y-m-d', strtotime((string) $promotion->promotion_end)));
        $this->assertSame('Giảm đến 50%', $promotion->promotion_content);
        $this->assertSame('Chạy cả tháng 12', $promotion->promotion_note);
        $this->assertSame(0, (int) $promotion->promotion_hidden);
        $this->assertSame($this->slideCategory->cate_slide_id, (int) $promotion->cate_slide_id);
    }

    public function test_cap_nhat_slide_giu_nguyen_tieu_de_va_thu_tu_cua_chinh_no(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->put(route('promotion.update', $promotion->promotion_id), $this->updatePayload($promotion))
            ->assertSessionHasNoErrors();
    }

    public function test_cap_nhat_slide_kem_anh_moi_thay_duong_dan_anh(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->put(route('promotion.update', $promotion->promotion_id), $this->updatePayload($promotion, [
            'img' => UploadedFile::fake()->image('banner.png'),
        ]))->assertSessionHasNoErrors();

        $path = (string) $promotion->refresh()->promotion_img;
        $this->assertStringStartsWith(self::IMAGE_DIR.'/', $path);
        $this->assertStringEndsWith('.png', $path);
        $this->assertFileExists(public_path($path));
    }

    public function test_cap_nhat_slide_trung_tieu_de_hoac_ngay_ket_thuc_truoc_ngay_bat_dau_bi_tu_choi(): void
    {
        $this->promotion('Ra mắt Air Max', 2);
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->put(route('promotion.update', $promotion->promotion_id), $this->updatePayload($promotion, [
            'name' => 'Ra mắt Air Max',
            'position' => 2,
            'start_pro' => '2026-12-10',
            'end_pro' => '2026-12-01',
            'contents' => 'ab',
        ]))->assertSessionHasErrors([
            'name' => 'Tiều đề đã tồn tại!',
            'position' => 'Số thứ tự đã tồn tại!',
            'end_pro' => 'Ngày kết thúc phải sau ngày bắt đầu!',
            'contents' => 'Nội dung quá ngắn!',
        ]);

        $this->assertSame(self::TITLE, $promotion->refresh()->promotion_name);
    }

    public function test_cap_nhat_slide_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);
        $payload = $this->updatePayload($promotion, ['id' => 999, 'name' => 'Slide đã bị xoá', 'position' => 5]);

        $this->actingAs($this->admin())->put(route('promotion.update', 999), $payload)
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Không tồn tại nội dung');
    }

    // -------------------------------------------------------- show / hide

    public function test_bat_tat_hien_thi_slide(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);

        $this->actingAs($this->admin())->post(route('promotion.status', $promotion->promotion_id), ['p-status' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $promotion->refresh()->promotion_hidden);

        $this->actingAs($this->admin())->post(route('promotion.status', 999), ['p-status' => 1])
            ->assertNotFound()
            ->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    // --------------------------------------------------------------- trash

    public function test_xoa_slide_dua_vao_thung_rac_va_thung_rac_hien_no(): void
    {
        $promotion = $this->promotion(self::TITLE, 1);
        $this->promotion('Ra mắt Air Max', 2);

        $this->actingAs($this->admin())->delete(route('promotion.softDelete', $promotion->promotion_id))
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Xóa thành công!');
        $this->assertSoftDeleted('promotion', ['promotion_id' => $promotion->promotion_id]);

        $this->actingAs($this->admin())->get(route('promotion.trashed'))
            ->assertOk()
            ->assertSee(self::TITLE)
            ->assertDontSee('Ra mắt Air Max');

        $this->actingAs($this->admin())->get(route('promotion.trashed', ['keyword' => 'không có']))
            ->assertOk()
            ->assertDontSee(self::TITLE);
    }

    public function test_xoa_slide_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(self::LIST_URL)->delete(route('promotion.softDelete', 999))
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Không tồn tại quảng cáo!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_khoi_phuc_mot_slide_va_tat_ca(): void
    {
        $first = $this->promotion(self::TITLE, 1);
        $second = $this->promotion('Ra mắt Air Max', 2);
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->from(route('promotion.trashed'))->patch(route('promotion.restore', $first->promotion_id))
            ->assertRedirect(route('promotion.trashed'))
            ->assertSessionHas('message', 'Hoàn tác thành công');
        $this->assertNotSoftDeleted('promotion', ['promotion_id' => $first->promotion_id]);

        $this->actingAs($this->admin())->patch(route('promotion.restoreAll'))->assertSessionHas('message', 'Hoàn tác thành công');
        $this->assertNotSoftDeleted('promotion', ['promotion_id' => $second->promotion_id]);

        $this->actingAs($this->admin())->patch(route('promotion.restoreAll'))
            ->assertSessionHas('message', 'Không có dữ liệu trong thùng rác!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_xoa_vinh_vien_mot_slide_va_tat_ca(): void
    {
        $first = $this->promotion(self::TITLE, 1);
        $second = $this->promotion('Ra mắt Air Max', 2);
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->delete(route('promotion.delete', $first->promotion_id))
            ->assertSessionHas('message', 'Xóa thành công');
        $this->assertDatabaseMissing('promotion', ['promotion_id' => $first->promotion_id]);

        $this->actingAs($this->admin())->delete(route('promotion.delete.all'))->assertSessionHas('message', 'Xóa slide thành công!');
        $this->assertSame(0, PromotionModel::withTrashed()->count());

        $this->actingAs($this->admin())->delete(route('promotion.delete.all'))
            ->assertSessionHas('message', 'Không có dữ liệu trong thùng rác!');
    }

    public function test_khoi_phuc_hay_xoa_vinh_vien_slide_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->patch(route('promotion.restore', 999))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('promotion.delete', 999))->assertNotFound();
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $this->promotion(self::TITLE, 1);
        $this->promotion('Ra mắt Air Max', 2)->delete();

        $this->actingAs($this->admin())->get(self::LIST_URL)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa?"', false);
        $this->actingAs($this->admin())->get(route('promotion.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả slide vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa slide này vĩnh viễn?"', false);
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

    private function promotion(string $name, int $sort, ?string $end = null): PromotionModel
    {
        return PromotionModel::create([
            'type' => 'slide',
            'promotion_name' => $name,
            'promotion_img' => 'backend/uploads/slide/co-san.jpg',
            'promotion_link' => '/san-pham',
            'promotion_hidden' => 1,
            'promotion_sort' => $sort,
            'promotion_content' => 'Nội dung '.$name,
            'promotion_date' => now(),
            'promotion_start' => now()->toDateString(),
            'promotion_end' => $end ?? now()->addMonth()->toDateString(),
            'cate_slide_id' => $this->slideCategory->cate_slide_id,
        ]);
    }

    /**
     * What the edit form posts, including the hidden `id` field the unique
     * rules read.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(PromotionModel $promotion, array $overrides = []): array
    {
        return array_merge([
            'id' => $promotion->promotion_id,
            'name' => $promotion->promotion_name,
            'type' => $promotion->type,
            'url' => $promotion->promotion_link,
            'position' => $promotion->promotion_sort,
            'start_pro' => now()->toDateString(),
            'end_pro' => now()->addMonth()->toDateString(),
            'contents' => $promotion->promotion_content,
            'status' => 1,
            'cate' => $this->slideCategory->cate_slide_id,
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function uploadedImages(): array
    {
        return glob(public_path(self::IMAGE_DIR).'/*') ?: [];
    }

    public function test_them_slide_hop_le_luu_slide_va_ngay_tao(): void
    {
        $this->actingAs($this->admin())->post(route('promotion.store'), [
            'name' => self::TITLE,
            'type' => 'slide',
            'url' => '/san-pham',
            'position' => 5,
            'start_pro' => today()->format('d-m-Y'),
            'end_pro' => today()->addMonth()->format('d-m-Y'),
            'contents' => 'Giảm đến 30%',
            'status' => 1,
            'cate' => $this->slideCategory->cate_slide_id,
            'img' => UploadedFile::fake()->image('banner.jpg'),
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Thêm thành công');

        $promotion = PromotionModel::where('promotion_name', self::TITLE)->firstOrFail();
        $this->assertNotNull($promotion->promotion_date);
        $this->assertSame(5, (int) $promotion->promotion_sort);
    }
}
