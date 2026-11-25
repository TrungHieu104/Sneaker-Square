<?php

namespace Tests\Feature\Legacy;

use App\Models\MenuModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin screens for the storefront menu: a tree of
 * root items and their children, ordered by position.
 */
class ContentMenuAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Menu';

    private const LIST_URL = 'admin/menus';

    private const EMPTY_TRASH = 'Không có dữ liệu trong thùng rác!';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_khach_chua_dang_nhap_bi_chuyen_ve_dang_nhap_admin(): void
    {
        $this->get(route('menus.index'))->assertRedirect(route('admin.login'));
        $this->get(route('menu.trashed'))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_menu_bi_tu_choi(): void
    {
        $staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
        $menu = $this->menu('Sản phẩm');

        $this->actingAs($staff)->get(route('menus.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('menus.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('menus.store'), ['name' => 'Mới', 'slug' => '/moi', 'parent_id' => $menu->menu_id])->assertForbidden();
        $this->actingAs($staff)->post(route('menu.updatePositions'), ['positions' => [['id' => $menu->menu_id, 'position' => 9]]])->assertForbidden();
        $this->actingAs($staff)->delete(route('menu.softDelete', $menu->menu_id))->assertForbidden();

        $this->assertDatabaseCount('menu', 1);
        $this->assertSame(1, (int) $menu->refresh()->menu_position);
        $this->assertNotSoftDeleted($menu);
    }

    public function test_danh_sach_menu_tach_goc_va_con_theo_vi_tri(): void
    {
        $products = $this->menu('Sản phẩm', position: 2);
        $home = $this->menu('Trang chủ', position: 1);
        $this->menu('Giày nam', parent: $products, position: 2);
        $this->menu('Giày nữ', parent: $products, position: 1);

        $response = $this->actingAs($this->admin())->get(route('menus.index'))
            ->assertOk()
            ->assertViewIs('backend.pages.menus.menus_list')
            ->assertSee('Giày nữ');

        $this->assertSame([$home->menu_id, $products->menu_id], $response->viewData('rootMenus')->pluck('menu_id')->all());
        $this->assertSame(['Giày nữ', 'Giày nam'], $response->viewData('childrenByParent')[$products->menu_id]->pluck('menu_name')->all());
    }

    public function test_trang_them_menu_dua_cay_menu_dang_hien_va_vi_tri_tiep_theo(): void
    {
        $products = $this->menu('Sản phẩm', position: 4);
        $this->menu('Giày nam', parent: $products, position: 1);
        $this->menu('Menu ẩn', position: 2, visible: false);

        $response = $this->actingAs($this->admin())->get(route('menus.create'))
            ->assertOk()
            ->assertViewIs('backend.pages.menus.menus_create')
            ->assertViewHas('nextPosition', 5);

        $tree = collect($response->viewData('menu'));
        $this->assertSame(['Sản phẩm', 'Giày nam'], $tree->pluck('menu_name')->all());
        $this->assertSame([0, 1], $tree->pluck('level')->all());
    }

    public function test_them_menu_con_hop_le_tu_dien_vi_tri_cuoi(): void
    {
        $products = $this->menu('Sản phẩm', position: 3);

        $this->actingAs($this->admin())->post(route('menus.store'), [
            'name' => 'Giày nam', 'slug' => '/giay-nam', 'parent_id' => $products->menu_id,
        ])
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Thêm thành công')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertDatabaseHas('menu', [
            'menu_name' => 'Giày nam', 'menu_link' => '/giay-nam', 'menu_parent_id' => $products->menu_id,
            'menu_position' => 4, 'menu_hidden' => 1,
        ]);
    }

    public function test_them_menu_con_voi_vi_tri_va_trang_thai_chon_san(): void
    {
        $products = $this->menu('Sản phẩm');

        $this->actingAs($this->admin())->post(route('menus.store'), [
            'name' => 'Giày nữ', 'slug' => '/giay-nu', 'parent_id' => $products->menu_id, 'position' => '7', 'status' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menu', ['menu_name' => 'Giày nữ', 'menu_position' => 7, 'menu_hidden' => 0]);
    }

    public function test_them_menu_sai_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->menu('Sản phẩm');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('menus.store'), [])
            ->assertSessionHasErrors(['name' => 'Vui lòng nhập tiêu đề!', 'slug' => 'Vui lòng nhập đường dẫn!']);
        $this->actingAs($admin)->post(route('menus.store'), ['name' => 'Sản phẩm', 'slug' => '/sp', 'position' => 'abc'])
            ->assertSessionHasErrors(['name' => 'Tiêu đề đã tồn tại!', 'position' => 'Số thứ tự không được nhập chữ!']);
        $this->actingAs($admin)->post(route('menus.store'), ['name' => 'Mới', 'slug' => '/moi', 'position' => '1.5'])
            ->assertSessionHasErrors(['position' => 'Vui lòng nhập số nguyên']);
        $this->actingAs($admin)->post(route('menus.store'), ['name' => 'Mới', 'slug' => '/moi', 'position' => '-1'])
            ->assertSessionHasErrors(['position' => 'Vui lòng nhập số thứ tự lớn hơn 0!']);
        $this->actingAs($admin)->post(route('menus.store'), ['name' => 'Mới', 'slug' => '/moi', 'position' => '100000001'])
            ->assertSessionHasErrors(['position' => 'Số thứ tự quá lớn!']);

        $this->assertDatabaseCount('menu', 1);
    }

    public function test_trang_sua_menu_mo_dung_menu(): void
    {
        $menu = $this->menu('Sản phẩm');
        $this->menu('Giày nam', parent: $menu);

        $response = $this->actingAs($this->admin())->get(route('menus.edit', $menu->menu_id))
            ->assertOk()
            ->assertViewIs('backend.pages.menus.menus_edit')
            ->assertSee('Sản phẩm');

        $this->assertSame($menu->menu_id, $response->viewData('menus')->menu_id);
        $this->assertCount(2, $response->viewData('menu'));

        $this->actingAs($this->admin())->get(route('menus.edit', 999))
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Không tồn tại menu');
    }

    public function test_cap_nhat_menu_con_luu_thong_tin_moi(): void
    {
        $products = $this->menu('Sản phẩm');
        $sale = $this->menu('Khuyến mãi', position: 2);
        $child = $this->menu('Giày nam', parent: $products, position: 6);

        $this->actingAs($this->admin())->put(route('menus.update', $child->menu_id), [
            'name' => 'Giày nam', 'slug' => '/giay-nam-moi', 'parent_id' => $sale->menu_id,
        ])
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Chỉnh sửa thành công!');

        $child->refresh();
        $this->assertSame('/giay-nam-moi', $child->menu_link);
        $this->assertSame($sale->menu_id, (int) $child->menu_parent_id);
        // No position sent keeps the old one; no status sent hides the item.
        $this->assertSame(6, (int) $child->menu_position);
        $this->assertSame(0, (int) $child->menu_hidden);

        $this->actingAs($this->admin())->put(route('menus.update', $child->menu_id), [
            'name' => 'Giày nam', 'slug' => '/giay-nam', 'parent_id' => $sale->menu_id, 'position' => '2', 'status' => '1',
        ])->assertSessionHasNoErrors();
        $child->refresh();
        $this->assertSame(2, (int) $child->menu_position);
        $this->assertSame(1, (int) $child->menu_hidden);
    }

    public function test_cap_nhat_menu_trung_ten_menu_khac_hoac_khong_ton_tai(): void
    {
        $products = $this->menu('Sản phẩm');
        $child = $this->menu('Giày nam', parent: $products);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('menus.update', $child->menu_id), ['name' => 'Sản phẩm', 'slug' => ''])
            ->assertSessionHasErrors(['name' => 'Tiêu đề đã tồn tại!', 'slug' => 'Vui lòng nhập đường dẫn!']);
        $this->assertSame('Giày nam', $child->refresh()->menu_name);

        $this->actingAs($admin)->put(route('menus.update', 999), ['name' => 'Không có', 'slug' => '/x'])
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Không tồn tại menu');
    }

    public function test_keo_tha_cap_nhat_vi_tri_menu(): void
    {
        $first = $this->menu('Trang chủ', position: 1);
        $second = $this->menu('Sản phẩm', position: 2);

        $this->actingAs($this->admin())->postJson(route('menu.updatePositions'), ['positions' => [
            ['id' => $first->menu_id, 'position' => 2],
            ['id' => $second->menu_id, 'position' => 1],
        ]])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Cập nhật vị trí thành công']);

        $this->assertSame(2, (int) $first->refresh()->menu_position);
        $this->assertSame(1, (int) $second->refresh()->menu_position);
    }

    public function test_an_hien_menu_tra_json(): void
    {
        $menu = $this->menu('Sản phẩm');

        $this->actingAs($this->admin())->post(route('menu.status', $menu->menu_id), ['m-status' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $menu->refresh()->menu_hidden);

        $this->actingAs($this->admin())->post(route('menu.status', 999), ['m-status' => 1])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_khong_xoa_duoc_menu_cha_con_menu_con(): void
    {
        $products = $this->menu('Sản phẩm');
        $this->menu('Giày nam', parent: $products);

        $this->actingAs($this->admin())->from(route('menus.index'))->delete(route('menu.softDelete', $products->menu_id))
            ->assertRedirect(route('menus.index'))
            ->assertSessionHas('message', 'Không thể xóa menu cha!')
            ->assertSessionHas('iconMessage', 'error');

        $this->assertNotSoftDeleted($products);
    }

    public function test_xoa_menu_khong_ton_tai_quay_lai_trang_truoc(): void
    {
        $this->actingAs($this->admin())->from(route('menus.index'))->delete(route('menu.softDelete', 999))
            ->assertRedirect(route('menus.index'))
            ->assertSessionHas('message', 'Không tồn tại thông tin!');
    }

    public function test_thung_rac_menu_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $products = $this->menu('Sản phẩm');
        $first = $this->menu('Giày nam', parent: $products);
        $second = $this->menu('Giày nữ', parent: $products);
        $admin = $this->admin();
        $from = route('menu.trashed');

        $this->actingAs($admin)->delete(route('menu.softDelete', $first->menu_id))
            ->assertRedirect('/admin/menus')
            ->assertSessionHas('message', 'Xóa thành công!');
        $this->assertSoftDeleted($first);

        $trash = $this->actingAs($admin)->get(route('menu.trashed', ['keyword' => 'nam']))
            ->assertOk()->assertViewIs('backend.pages.menus.menus_trash')->assertSee('Giày nam')
            ->viewData('menuTrash');
        $this->assertSame([$first->menu_id], collect($trash->items())->pluck('menu_id')->all());

        $this->actingAs($admin)->from($from)->patch(route('menu.restore', $first->menu_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('menu.restore', 999))->assertNotFound();

        $this->actingAs($admin)->from($from)->patch(route('menu.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', self::EMPTY_TRASH)->assertSessionHas('iconMessage', 'info');
        $this->actingAs($admin)->from($from)->delete(route('menus.delete.all'))
            ->assertRedirect($from)->assertSessionHas('message', self::EMPTY_TRASH);

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->from($from)->patch(route('menu.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertSame(0, MenuModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($from)->delete(route('menu.delete', $first->menu_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa thành công!');
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('menu.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->from($from)->delete(route('menus.delete.all'))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa menu thành công!');
        $this->assertModelMissing($second);
    }

    public function test_xoa_vinh_vien_menu_cha_con_menu_con_trong_thung_rac(): void
    {
        $admin = $this->admin();
        $parent = $this->menu('Sản phẩm');
        $child = $this->menu('Giày nam', $parent);
        $child->delete();
        $parent->delete();

        $this->actingAs($admin)->delete(route('menu.delete', $parent->menu_id))
            ->assertSessionHas('message', 'Menu còn menu con, kể cả trong thùng rác. Hãy xóa vĩnh viễn các menu con trước!');
        $this->assertSoftDeleted($parent);

        // Emptying the whole trash takes the child first, then the parent.
        $this->actingAs($admin)->delete(route('menus.delete.all'))
            ->assertSessionHas('message', 'Xóa menu thành công!');
        $this->assertSame(0, MenuModel::withTrashed()->count());
    }

    // -------------------------------------------------------------- helpers

    private function admin(): UserModel
    {
        $admin = UserModel::where('username', 'quantri')->first()
            ?? $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::PERMISSION, 'web'));

        return $admin;
    }

    /**
     * Root items carry a null parent, the way MenuSeeder stores them.
     */
    private function menu(string $name, ?MenuModel $parent = null, int $position = 1, bool $visible = true): MenuModel
    {
        return MenuModel::create([
            'menu_name' => $name,
            'menu_link' => '/'.str($name)->slug(),
            'menu_parent_id' => $parent?->menu_id,
            'menu_position' => $position,
            'menu_hidden' => $visible ? 1 : 0,
        ]);
    }

    public function test_menu_cha_khong_ton_tai_bi_tu_choi(): void
    {
        $this->actingAs($this->admin())->post(route('menus.store'), ['name' => 'Khuyến mãi', 'slug' => '/khuyen-mai', 'parent_id' => 9999])
            ->assertSessionHasErrors(['parent_id' => 'Menu cha không tồn tại!']);

        $this->assertSame(0, MenuModel::count());
    }

    public function test_khong_dat_menu_lam_con_cua_chinh_no_hoac_menu_con(): void
    {
        $admin = $this->admin();
        $parent = $this->menu('Sản phẩm');
        $child = $this->menu('Giày nam', $parent);

        foreach ([$parent, $child] as $target) {
            $this->actingAs($admin)->put(route('menus.update', $parent->menu_id), ['name' => 'Sản phẩm', 'slug' => '/san-pham', 'parent_id' => $target->menu_id])
                ->assertSessionHasErrors(['parent_id' => 'Không thể chọn chính menu này hoặc menu con của nó làm menu cha!']);
        }

        $this->assertNull($parent->fresh()->menu_parent_id);
    }

    public function test_them_menu_goc_khong_chon_menu_cha(): void
    {
        $this->actingAs($this->admin())->post(route('menus.store'), ['name' => 'Khuyến mãi', 'slug' => '/khuyen-mai', 'parent_id' => ''])
            ->assertRedirect(self::LIST_URL)
            ->assertSessionHas('message', 'Thêm thành công');

        $this->assertNull(MenuModel::where('menu_name', 'Khuyến mãi')->value('menu_parent_id'));
    }

    public function test_sua_menu_con_thanh_menu_goc(): void
    {
        $products = $this->menu('Sản phẩm');
        $child = $this->menu('Giày nam', $products);

        $this->actingAs($this->admin())->put(route('menus.update', $child->menu_id), ['name' => 'Giày nam', 'slug' => '/giay-nam', 'parent_id' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($child->fresh()->menu_parent_id);
    }
}
