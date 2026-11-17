<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\PageModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\ResponseCache\Facades\ResponseCache;
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

    private const PERMISSION = 'Quản trị Chính sách & FAQ';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    // ---------------------------------------------------------- storefront

    public function test_trang_chinh_sach_hien_noi_dung_theo_duong_dan(): void
    {
        $this->policy('Chính sách giao hàng', 'Giao toàn quốc qua GHN.');

        $this->get(route('policy.detail', 'chinh-sach-giao-hang.html'))
            ->assertOk()
            ->assertSee('Chính sách giao hàng')
            ->assertSee('Giao toàn quốc qua GHN.');
    }

    public function test_duong_dan_khong_ton_tai_thi_quay_lai_kem_thong_bao(): void
    {
        $this->from(route('home.page'))
            ->get(route('policy.detail', 'khong-co.html'))
            ->assertRedirect(route('home.page'))
            ->assertSessionHas('message', 'Chính sách không tồn tại');
    }

    public function test_chinh_sach_dang_an_tra_404(): void
    {
        $this->policy('Chính sách nháp', 'Chưa đăng.', visible: false);

        $this->get(route('policy.detail', 'chinh-sach-nhap.html'))->assertNotFound();
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

    public function test_sua_an_hoac_xoa_chinh_sach_thi_xoa_cache_storefront(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Nội dung.');
        ResponseCache::spy();

        $this->actingAs($this->admin())->post(route('page.status', $page->page_id), ['page-status' => 0]);
        $this->actingAs($this->admin())->delete(route('page.softDelete', encrypt($page->page_id)));
        $this->actingAs($this->admin())->get(route('page.restore', encrypt($page->page_id)));

        ResponseCache::shouldHaveReceived('clear')->times(3);
    }

    // --------------------------------------------------------------- admin

    public function test_admin_khong_co_quyen_thi_khong_vao_duoc_chinh_sach_va_gioi_thieu(): void
    {
        $admin = $this->admin(withPermission: false);

        $this->actingAs($admin)->get(route('page.index'))->assertForbidden();
        $this->actingAs($admin)->get('/admin/about')->assertForbidden();
    }

    public function test_danh_sach_gom_trang_gioi_thieu_dung_dau_va_loc_theo_loai(): void
    {
        $this->policy('Chính sách giao hàng', 'Nội dung.');
        $this->policy('Chính sách thanh toán', 'Nội dung.');
        $this->aboutPage('Giới thiệu về Sneaker Square', 'Về shop.');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('page.index'))
            ->assertOk()
            ->assertSeeInOrder(['Giới thiệu về Sneaker Square', 'Chính sách thanh toán', 'Chính sách giao hàng']);

        $this->actingAs($admin)->get(route('page.index', ['type' => 'policy']))
            ->assertSee('Chính sách giao hàng')
            ->assertDontSee('Giới thiệu về Sneaker Square');

        $this->actingAs($admin)->get(route('page.index', ['type' => 'about']))
            ->assertSee('Giới thiệu về Sneaker Square')
            ->assertDontSee('Chính sách giao hàng');

        $this->actingAs($admin)->get(route('page.index', ['keyword' => 'thanh toán']))
            ->assertSee('Chính sách thanh toán')
            ->assertDontSee('Chính sách giao hàng');
    }

    public function test_link_cu_cua_chinh_sach_va_gioi_thieu_ve_man_trang(): void
    {
        $this->assertSame(url('/admin/page'), route('page.index'));

        $this->actingAs($this->admin())->get('/admin/policy')->assertRedirect(route('page.index'));
        $this->actingAs($this->admin())->get('/admin/about')->assertRedirect(route('page.index', ['type' => 'about']));
    }

    public function test_them_chinh_sach_gan_duoi_html_va_tac_gia(): void
    {
        $this->actingAs($this->admin())->post(route('page.store'), [
            'name' => 'Chính sách kiểm hàng',
            'slug' => 'chinh-sach-kiem-hang',
            'author' => 'Quản trị viên',
            'description' => 'Mô tả ngắn',
            'content' => 'Được xem hàng trước khi nhận.',
            'key' => 'kiem hang',
            'des' => 'SEO mô tả',
            'status' => '1',
        ])->assertRedirect(route('page.index'))->assertSessionHas('message', 'Thêm thành công');

        $this->assertDatabaseHas('pages', [
            'page_name' => 'Chính sách kiểm hàng',
            'page_slug' => 'chinh-sach-kiem-hang.html',
            'page_description' => 'Mô tả ngắn',
            'page_content' => 'Được xem hàng trước khi nhận.',
            'page_meta_keywords' => 'kiem hang',
            'page_meta_description' => 'SEO mô tả',
            'page_created_by' => 'Quản trị viên',
            'page_hidden' => 1,
            'page_type' => 'policy',
        ]);
    }

    public function test_them_chinh_sach_thieu_hoac_trung_ten_bi_tu_choi(): void
    {
        $this->policy('Chính sách giao hàng', 'Nội dung.');

        $this->actingAs($this->admin())
            ->post(route('page.store'), ['name' => 'Chính sách giao hàng', 'slug' => '', 'content' => 'ab'])
            ->assertSessionHasErrors([
                'name' => 'Tiêu đề đã tồn tại!',
                'slug' => 'Vui lòng nhập đường dẫn!',
                'content' => 'Nội dung quá ngắn!',
            ]);
    }

    public function test_sua_chinh_sach_luu_nguoi_sua_va_an_hien(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Cũ.');

        $this->actingAs($this->admin())->get(route('page.edit', encrypt($page->page_id)))
            ->assertOk()
            ->assertSee('Chính sách giao hàng');

        $this->actingAs($this->admin())->put(route('page.update', $page->page_id), [
            'id' => $page->page_id,
            'name' => 'Chính sách vận chuyển',
            'slug' => 'chinh-sach-van-chuyen',
            'editor' => 'Biên tập viên',
            'description' => 'Mô tả mới',
            'content' => 'Nội dung mới.',
            'status' => '0',
        ])->assertRedirect(route('page.index'))->assertSessionHas('message', 'Chỉnh sửa thành công');

        $page->refresh();
        $this->assertSame('Chính sách vận chuyển', $page->page_name);
        $this->assertSame('chinh-sach-van-chuyen.html', $page->page_slug);
        $this->assertSame('Nội dung mới.', $page->page_content);
        $this->assertSame('Biên tập viên', $page->page_updated_by);
        $this->assertSame(0, (int) $page->page_hidden);
    }

    public function test_trang_sua_chinh_sach_hien_seo_da_luu(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Nội dung.');
        $page->update(['page_meta_keywords' => 'giao hang nhanh', 'page_meta_description' => 'Giao toàn quốc']);

        $this->actingAs($this->admin())->get(route('page.edit', encrypt($page->page_id)))
            ->assertOk()
            ->assertSee('value="giao hang nhanh"', false)
            ->assertSee('Giao toàn quốc');
    }

    public function test_ma_sai_khi_mo_trang_sua_thi_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->get(route('page.edit', 'khong-giai-ma-duoc'))
            ->assertRedirect(route('page.index'))
            ->assertSessionHas('message', 'Không tồn tại nội dung!');
    }

    public function test_bat_tat_hien_thi_chinh_sach(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Nội dung.');

        $this->actingAs($this->admin())->post(route('page.status', $page->page_id), ['page-status' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $page->refresh()->page_hidden);

        $this->actingAs($this->admin())->post(route('page.status', 999), ['page-status' => 1])->assertNotFound();
    }

    public function test_admin_khong_co_quyen_khong_bat_tat_duoc_chinh_sach(): void
    {
        $page = $this->policy('Chính sách giao hàng', 'Nội dung.');

        $this->actingAs($this->admin(withPermission: false))
            ->post(route('page.status', $page->page_id), ['page-status' => 0])
            ->assertForbidden();
        $this->assertSame(1, (int) $page->refresh()->page_hidden);
    }

    public function test_thung_rac_xoa_tam_khoi_phuc_va_xoa_han(): void
    {
        $admin = $this->admin();
        $kept = $this->policy('Chính sách giao hàng', 'Nội dung.');
        $gone = $this->policy('Chính sách bảo mật', 'Nội dung.');

        $this->actingAs($admin)->delete(route('page.softDelete', encrypt($kept->page_id)))->assertSessionHas('message', 'Xóa thành công');
        $this->actingAs($admin)->delete(route('page.softDelete', encrypt($gone->page_id)));
        $this->assertSoftDeleted('pages', ['page_id' => $kept->page_id]);
        $this->actingAs($admin)->get(route('page.trashed'))->assertOk()->assertSee('Chính sách giao hàng');

        $this->actingAs($admin)->get(route('page.restore', encrypt($kept->page_id)))->assertSessionHas('message', 'Khôi phục thành công');
        $this->assertNotSoftDeleted('pages', ['page_id' => $kept->page_id]);

        $this->actingAs($admin)->get(route('page.delete', encrypt($gone->page_id)))->assertSessionHas('message', 'Xóa thành công');
        $this->assertDatabaseMissing('pages', ['page_id' => $gone->page_id]);
    }

    public function test_khoi_phuc_va_xoa_het_thung_rac(): void
    {
        $admin = $this->admin();
        $this->policy('Chính sách giao hàng', 'Nội dung.')->delete();

        $this->actingAs($admin)->get(route('page.restoreAll'))->assertSessionHas('message', 'Hoàn tác thành công');
        $this->assertSame(0, PageModel::onlyTrashed()->count());

        $this->actingAs($admin)->get(route('page.restoreAll'))->assertSessionHas('message', 'Không có dữ liệu trong thùng rác!');

        PageModel::query()->first()?->delete();
        $this->actingAs($admin)->get(route('page.delete.all'))->assertSessionHas('message', 'Xóa dữ liệu thành công!');
        $this->assertSame(0, PageModel::withTrashed()->count());
    }

    public function test_khoi_phuc_het_thung_rac_thi_xoa_cache_storefront(): void
    {
        $this->policy('Chính sách giao hàng', 'Nội dung.')->delete();
        ResponseCache::spy();

        $this->actingAs($this->admin())->get(route('page.restoreAll'));

        ResponseCache::shouldHaveReceived('clear');
    }

    public function test_trang_gioi_thieu_sua_duoc_nhung_khong_an_va_khong_xoa_duoc(): void
    {
        $about = $this->aboutPage('Giới thiệu về Sneaker Square', 'Cũ.');
        $slug = $about->page_slug;
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('page.edit', encrypt($about->page_id)))
            ->assertOk()
            ->assertSee('Giới thiệu về Sneaker Square')
            ->assertDontSee('name="status"', false);

        $this->actingAs($admin)->put(route('page.update', $about->page_id), [
            'id' => $about->page_id,
            'name' => 'Về Sneaker Square',
            'description' => 'Giày chính hãng',
            'content' => 'Shop giày chính hãng.',
            'editor' => 'Biên tập viên',
            'status' => '0',
            'slug' => 'doi-duong-dan',
        ])->assertRedirect(route('page.index'))->assertSessionHas('message', 'Chỉnh sửa thành công');

        $this->actingAs($admin)->post(route('page.status', $about->page_id), ['page-status' => 0])->assertNotFound();
        $this->actingAs($admin)->delete(route('page.softDelete', encrypt($about->page_id)))->assertSessionHas('message', 'Không tồn tại chính sách');

        $about->refresh();
        $this->assertSame('Về Sneaker Square', $about->page_name);
        $this->assertSame('Shop giày chính hãng.', $about->page_content);
        $this->assertSame('Giày chính hãng', $about->page_description);
        $this->assertSame($slug, $about->page_slug);
        $this->assertSame(1, (int) $about->page_hidden);
        $this->assertSame(PageType::About, $about->page_type);
        $this->assertNotSoftDeleted('pages', ['page_id' => $about->page_id]);
    }

    public function test_thung_rac_chinh_sach_khong_dung_toi_trang_gioi_thieu_da_xoa(): void
    {
        $about = $this->aboutPage('Giới thiệu về Sneaker Square', 'Cũ.');
        $about->delete();
        $policy = $this->policy('Chính sách giao hàng', 'Nội dung.');
        $policy->delete();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('page.trashed'))->assertOk()->assertDontSee('Giới thiệu về Sneaker Square');
        $this->actingAs($admin)->get(route('page.restore', encrypt($about->page_id)))->assertNotFound();
        $this->actingAs($admin)->get(route('page.delete', encrypt($about->page_id)))->assertNotFound();

        $this->actingAs($admin)->get(route('page.restoreAll'))->assertSessionHas('message', 'Hoàn tác thành công');
        $this->assertNotSoftDeleted('pages', ['page_id' => $policy->page_id]);
        $this->assertSoftDeleted('pages', ['page_id' => $about->page_id]);

        $policy->delete();
        $this->actingAs($admin)->get(route('page.delete.all'))->assertSessionHas('message', 'Xóa dữ liệu thành công!');
        $this->assertDatabaseMissing('pages', ['page_id' => $policy->page_id]);
        $this->assertSoftDeleted('pages', ['page_id' => $about->page_id]);

        $this->actingAs($admin)->get(route('page.delete.all'))->assertSessionHas('message', 'Không có dữ liệu trong thùng rác!');
    }

    private function admin(bool $withPermission = true): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        Permission::findOrCreate(self::PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo(self::PERMISSION);
        }

        return $admin;
    }

    private function policy(string $name, string $content, bool $visible = true): PageModel
    {
        return PageModel::create([
            'page_name' => $name,
            'page_slug' => str($name)->slug().'.html',
            'page_description' => 'Tóm tắt '.$name,
            'page_content' => $content,
            'page_hidden' => $visible ? 1 : 0,
            'page_type' => 'policy',
        ]);
    }

    private function aboutPage(string $name, string $content): PageModel
    {
        return PageModel::create([
            'page_name' => $name,
            'page_slug' => str($name)->slug().'.html',
            'page_description' => '',
            'page_content' => $content,
            'page_hidden' => 1,
            'page_type' => PageType::About,
        ]);
    }
}
