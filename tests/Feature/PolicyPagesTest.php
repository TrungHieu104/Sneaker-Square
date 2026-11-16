<?php

namespace Tests\Feature;

use App\Models\FaqModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The policy pages and the about page: where the storefront shows them and
 * how the admin screens list, edit, hide and bin them.
 */
class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị FAQ';

    private const ABOUT_PERMISSION = 'Giới thiệu';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    // ---------------------------------------------------------- storefront

    public function test_trang_chinh_sach_hien_noi_dung_theo_duong_dan(): void
    {
        $this->policy('Chính sách giao hàng', 'Giao toàn quốc qua GHN.');

        $this->get(route('faq.detail', 'chinh-sach-giao-hang.html'))
            ->assertOk()
            ->assertSee('Chính sách giao hàng')
            ->assertSee('Giao toàn quốc qua GHN.');
    }

    public function test_duong_dan_khong_ton_tai_thi_quay_lai_kem_thong_bao(): void
    {
        $this->from(route('home.page'))
            ->get(route('faq.detail', 'khong-co.html'))
            ->assertRedirect(route('home.page'))
            ->assertSessionHas('message', 'Chính sách không tồn tại');
    }

    public function test_chinh_sach_dang_an_khong_mo_duoc(): void
    {
        $this->policy('Chính sách nháp', 'Chưa đăng.', visible: false);

        $this->get(route('faq.detail', 'chinh-sach-nhap.html'))->assertServerError();
    }

    public function test_footer_va_trang_san_pham_chi_liet_ke_chinh_sach_dang_hien(): void
    {
        $this->policy('Chính sách bảo mật', 'Nội dung.');
        $this->policy('Chính sách nháp', 'Nội dung.', visible: false);
        $this->aboutPage('Giới thiệu về Sneaker Square', 'Về shop.');
        $product = $this->makeProduct(slug: 'giay-co-chinh-sach');

        foreach ([route('home.page'), route('product.detail', $product->pro_slug)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Chính sách bảo mật')
                ->assertDontSee('Chính sách nháp')
                ->assertDontSee('Giới thiệu về Sneaker Square');
        }
    }

    public function test_trang_gioi_thieu_hien_noi_dung_gioi_thieu(): void
    {
        $this->aboutPage('Giới thiệu về Sneaker Square', 'Shop giày chính hãng.');
        $this->policy('Chính sách bảo mật', 'Không hiện ở đây.');

        $this->get(route('about.page'))
            ->assertOk()
            ->assertSee('Giới thiệu về Sneaker Square')
            ->assertSee('Shop giày chính hãng.')
            ->assertDontSee('Không hiện ở đây.');
    }

    // --------------------------------------------------------------- admin

    public function test_admin_khong_co_quyen_thi_khong_vao_duoc_chinh_sach_va_gioi_thieu(): void
    {
        $admin = $this->admin(withPermission: false);

        $this->actingAs($admin)->get(route('faq.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('about.index'))->assertForbidden();
    }

    public function test_danh_sach_chinh_sach_khong_co_trang_gioi_thieu_va_tim_theo_ten(): void
    {
        $this->policy('Chính sách giao hàng', 'Nội dung.');
        $this->policy('Chính sách thanh toán', 'Nội dung.');
        $this->aboutPage('Giới thiệu về Sneaker Square', 'Về shop.');

        $this->actingAs($this->admin())->get(route('faq.index'))
            ->assertOk()
            ->assertSee('Chính sách giao hàng')
            ->assertDontSee('Giới thiệu về Sneaker Square');

        $this->actingAs($this->admin())->get(route('faq.index', ['keyword' => 'thanh toán']))
            ->assertOk()
            ->assertSee('Chính sách thanh toán')
            ->assertDontSee('Chính sách giao hàng');
    }

    public function test_them_chinh_sach_gan_duoi_html_va_tac_gia(): void
    {
        $this->actingAs($this->admin())->post(route('faq.store'), [
            'name' => 'Chính sách kiểm hàng',
            'slug' => 'chinh-sach-kiem-hang',
            'author' => 'Quản trị viên',
            'faq-des' => 'Mô tả ngắn',
            'content' => 'Được xem hàng trước khi nhận.',
            'key' => 'kiem hang',
            'des' => 'SEO mô tả',
            'status' => '1',
        ])->assertRedirect('admin/faq')->assertSessionHas('message', 'Thêm thành công');

        $this->assertDatabaseHas('faq', [
            'faq_name' => 'Chính sách kiểm hàng',
            'faq_slug' => 'chinh-sach-kiem-hang.html',
            'faq_description' => 'Mô tả ngắn',
            'faq_content' => 'Được xem hàng trước khi nhận.',
            'faq_meta_keywords' => 'kiem hang',
            'faq_meta_description' => 'SEO mô tả',
            'faq_created_by' => 'Quản trị viên',
            'faq_hidden' => 1,
            'faq_about' => 0,
        ]);
    }

    public function test_them_chinh_sach_thieu_hoac_trung_ten_bi_tu_choi(): void
    {
        $this->policy('Chính sách giao hàng', 'Nội dung.');

        $this->actingAs($this->admin())
            ->post(route('faq.store'), ['name' => 'Chính sách giao hàng', 'slug' => '', 'content' => 'ab'])
            ->assertSessionHasErrors([
                'name' => 'Tiêu đề đã tồn tại!',
                'slug' => 'Vui lòng nhập đường dẫn!',
                'content' => 'Nội dung quá ngắn!',
            ]);
    }

    public function test_sua_chinh_sach_luu_nguoi_sua_va_an_hien(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Cũ.');

        $this->actingAs($this->admin())->get(route('faqs.edit', encrypt($page->faq_id)))
            ->assertOk()
            ->assertSee('Chính sách giao hàng');

        $this->actingAs($this->admin())->put(route('faq.update', $page->faq_id), [
            'id' => $page->faq_id,
            'name' => 'Chính sách vận chuyển',
            'slug' => 'chinh-sach-van-chuyen',
            'editor' => 'Biên tập viên',
            'faq-des' => 'Mô tả mới',
            'content' => 'Nội dung mới.',
            'status' => '0',
        ])->assertRedirect('/admin/faq')->assertSessionHas('message', 'Chỉnh sửa thành công');

        $page->refresh();
        $this->assertSame('Chính sách vận chuyển', $page->faq_name);
        $this->assertSame('chinh-sach-van-chuyen.html', $page->faq_slug);
        $this->assertSame('Nội dung mới.', $page->faq_content);
        $this->assertSame('Biên tập viên', $page->faq_updated_by);
        $this->assertSame(0, (int) $page->faq_hidden);
    }

    public function test_ma_sai_khi_mo_trang_sua_thi_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->get(route('faqs.edit', 'khong-giai-ma-duoc'))
            ->assertRedirect('admin/faq')
            ->assertSessionHas('message', 'Không tồn tại nội dung!');
    }

    public function test_bat_tat_hien_thi_chinh_sach(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Nội dung.');

        $this->actingAs($this->admin())->post(route('faq.status', $page->faq_id), ['faq-status' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $page->refresh()->faq_hidden);

        $this->actingAs($this->admin())->post(route('faq.status', 999), ['faq-status' => 1])->assertNotFound();
    }

    public function test_thung_rac_xoa_tam_khoi_phuc_va_xoa_han(): void
    {
        $admin = $this->admin();
        $kept = $this->policy('Chính sách giao hàng', 'Nội dung.');
        $gone = $this->policy('Chính sách bảo mật', 'Nội dung.');

        $this->actingAs($admin)->delete(route('faq.softDelete', encrypt($kept->faq_id)))->assertSessionHas('message', 'Xóa thành công');
        $this->actingAs($admin)->delete(route('faq.softDelete', encrypt($gone->faq_id)));
        $this->assertSoftDeleted('faq', ['faq_id' => $kept->faq_id]);
        $this->actingAs($admin)->get(route('faq.trashed'))->assertOk()->assertSee('Chính sách giao hàng');

        $this->actingAs($admin)->get(route('faq.restore', encrypt($kept->faq_id)))->assertSessionHas('message', 'Khôi phục thành công');
        $this->assertNotSoftDeleted('faq', ['faq_id' => $kept->faq_id]);

        $this->actingAs($admin)->get(route('faq.delete', encrypt($gone->faq_id)))->assertSessionHas('message', 'Xóa thành công');
        $this->assertDatabaseMissing('faq', ['faq_id' => $gone->faq_id]);
    }

    public function test_khoi_phuc_va_xoa_het_thung_rac(): void
    {
        $admin = $this->admin();
        $this->policy('Chính sách giao hàng', 'Nội dung.')->delete();

        $this->actingAs($admin)->get(route('faq.restoreAll'))->assertSessionHas('message', 'Hoàn tác thành công');
        $this->assertSame(0, FaqModel::onlyTrashed()->count());

        $this->actingAs($admin)->get(route('faq.restoreAll'))->assertSessionHas('message', 'Không có dữ liệu trong thùng rác!');

        FaqModel::query()->first()?->delete();
        $this->actingAs($admin)->get(route('faq.delete.all'))->assertSessionHas('message', 'Xóa dữ liệu thành công!');
        $this->assertSame(0, FaqModel::withTrashed()->count());
    }

    public function test_admin_gioi_thieu_chi_liet_ke_va_sua_trang_gioi_thieu(): void
    {
        $about = $this->aboutPage('Giới thiệu về Sneaker Square', 'Cũ.');
        $this->policy('Chính sách giao hàng', 'Nội dung.');

        $admin = $this->admin(permission: self::ABOUT_PERMISSION);

        $this->actingAs($admin)->get(route('about.index'))
            ->assertOk()
            ->assertSee('Giới thiệu về Sneaker Square')
            ->assertDontSee('Chính sách giao hàng');

        $this->actingAs($admin)->get(route('abouts.edit', encrypt($about->faq_id)))->assertOk();

        $this->actingAs($admin)->put(route('about.update', $about->faq_id), [
            'id' => $about->faq_id,
            'name' => 'Về Sneaker Square',
            'slug' => 've-sneaker-square',
            'editor' => 'Biên tập viên',
            'content' => 'Shop giày chính hãng.',
        ])->assertRedirect('/admin/about')->assertSessionHas('message', 'Chỉnh sửa thành công');

        $about->refresh();
        $this->assertSame('Về Sneaker Square', $about->faq_name);
        $this->assertSame('Shop giày chính hãng.', $about->faq_content);
        $this->assertSame(1, (int) $about->faq_hidden);
        $this->assertSame(1, (int) $about->faq_about);
    }

    private function admin(bool $withPermission = true, string $permission = self::PERMISSION): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        Permission::findOrCreate(self::PERMISSION, 'web');
        Permission::findOrCreate(self::ABOUT_PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function policy(string $name, string $content, bool $visible = true): FaqModel
    {
        return FaqModel::create([
            'faq_name' => $name,
            'faq_slug' => str($name)->slug().'.html',
            'faq_description' => 'Tóm tắt '.$name,
            'faq_content' => $content,
            'faq_hidden' => $visible ? 1 : 0,
            'faq_about' => 0,
        ]);
    }

    private function aboutPage(string $name, string $content): FaqModel
    {
        return FaqModel::create([
            'faq_name' => $name,
            'faq_slug' => str($name)->slug().'.html',
            'faq_description' => '',
            'faq_content' => $content,
            'faq_hidden' => 1,
            'faq_about' => 1,
        ]);
    }
}
