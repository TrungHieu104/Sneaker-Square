<?php

namespace Tests\Feature\Legacy;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the role and permission screens: giving a staff member a
 * role, opening the permission page for that role and adding a permission.
 */
class AccountRolePermissionTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const ADMINS = 'Quản trị Tài khoản (Quản trị viên)';

    private const ADMIN_ROLE = 'Quản trị viên';

    private const STAFF_ROLE = 'Nhân viên';

    private const ACCOUNT_NOT_FOUND = 'Không tồn tại tài khoản!';

    private UserModel $manager;

    private UserModel $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account; nothing may reach it.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->manager = $this->makeUser(email: 'quanly@gmail.com', username: 'quanly', role: 1);
        $this->manager->givePermissionTo(Permission::findOrCreate(self::ADMINS, 'web'));
        $this->staff = $this->makeUser(email: 'nhanvien@gmail.com', username: 'nhanvienkho', role: 1);

        Role::findOrCreate(self::ADMIN_ROLE, 'web');
        Role::findOrCreate(self::STAFF_ROLE, 'web');
    }

    // -------------------------------------------------------------- roles

    public function test_trang_phan_vai_tro_liet_ke_cac_vai_tro(): void
    {
        $this->actingAs($this->manager)->get(route('assign_role', Crypt::encrypt($this->staff->user_id)))
            ->assertOk()
            ->assertSee('value="'.self::ADMIN_ROLE.'"', false)
            ->assertSee('value="'.self::STAFF_ROLE.'"', false)
            ->assertSee(url('admin/insert_roles', [$this->staff->user_id]), false);
    }

    public function test_trang_phan_vai_tro_danh_dau_vai_tro_dang_co(): void
    {
        $this->staff->assignRole(self::STAFF_ROLE);

        $html = $this->actingAs($this->manager)->get(route('assign_role', Crypt::encrypt($this->staff->user_id)))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/checked\s+name=\'role\'[^>]*value="'.self::STAFF_ROLE.'"/u', $html);
    }

    public function test_trang_phan_vai_tro_cho_tai_khoan_khong_ton_tai_hoac_ma_sai(): void
    {
        $this->actingAs($this->manager)->get(route('assign_role', Crypt::encrypt(9999)))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('message', self::ACCOUNT_NOT_FOUND);

        $this->actingAs($this->manager)->get(route('assign_role', 'khong-phai-ma-hoa'))
            ->assertRedirect('admin/account')
            ->assertSessionHas('message', self::ACCOUNT_NOT_FOUND);
    }

    public function test_cap_vai_tro_thay_vai_tro_cu(): void
    {
        $this->staff->assignRole(self::ADMIN_ROLE);
        $page = route('assign_role', Crypt::encrypt($this->staff->user_id));

        $this->actingAs($this->manager)->from($page)
            ->post('/admin/insert_roles/'.$this->staff->user_id, ['role' => self::STAFF_ROLE])
            ->assertRedirect($page)
            ->assertSessionHas('message', 'Cấp vai trò thành công')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSame([self::STAFF_ROLE], $this->staff->fresh()->getRoleNames()->all());
    }

    // -------------------------------------------------------------- permissions

    public function test_trang_phan_quyen_can_tai_khoan_da_co_vai_tro(): void
    {
        $this->actingAs($this->manager)->get(route('assign_permission', Crypt::encrypt($this->staff->user_id)))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('message', 'Vui lòng phân vai trò cho user!')
            ->assertSessionHas('iconMessage', 'error');
    }

    public function test_trang_phan_quyen_liet_ke_quyen_va_danh_dau_quyen_cua_vai_tro(): void
    {
        $menu = Permission::findOrCreate('Quản trị Menu', 'web');
        $slide = Permission::findOrCreate('Quản trị Slide', 'web');
        Role::findByName(self::STAFF_ROLE, 'web')->givePermissionTo($menu);
        $this->staff->assignRole(self::STAFF_ROLE);

        $response = $this->actingAs($this->manager)->get(route('assign_permission', Crypt::encrypt($this->staff->user_id)))
            ->assertOk()
            ->assertSee(self::STAFF_ROLE)
            ->assertSee('Quản trị Menu')
            ->assertSee('Quản trị Slide')
            ->assertSee(url('admin/insert_permission', [$this->staff->user_id]), false);

        $this->assertSame([$menu->id], $response->viewData('get_permission_via_role')->pluck('id')->all());
        $this->assertContains($slide->id, $response->viewData('permission')->pluck('id')->all());
    }

    public function test_trang_phan_quyen_cho_tai_khoan_khong_ton_tai_hoac_ma_sai(): void
    {
        $this->actingAs($this->manager)->get(route('assign_permission', Crypt::encrypt(9999)))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('message', 'Không tồn tại user!');

        $this->actingAs($this->manager)->get(route('assign_permission', 'khong-phai-ma-hoa'))
            ->assertRedirect('admin/account')
            ->assertSessionHas('message', 'Thông tin người dùng không tồn tại!');
    }

    public function test_luu_phan_quyen_khong_chon_quyen_nao_bi_tu_choi(): void
    {
        $menu = Permission::findOrCreate('Quản trị Menu', 'web');
        Role::findByName(self::STAFF_ROLE, 'web')->givePermissionTo($menu);
        $this->staff->assignRole(self::STAFF_ROLE);

        $this->actingAs($this->manager)
            ->post('/admin/insert_permission/'.$this->staff->user_id, [])
            ->assertRedirect()
            ->assertSessionHas('message', 'Vui lòng chọn ít nhất một quyền.')
            ->assertSessionHas('iconMessage', 'error');

        $this->assertTrue(Role::findByName(self::STAFF_ROLE, 'web')->hasPermissionTo('Quản trị Menu'));
    }

    // -------------------------------------------------------------- new permission

    public function test_them_quyen_moi(): void
    {
        $this->actingAs($this->manager)
            ->post('/admin/insert_permission', ['permission' => 'Quản trị Kho mới'])
            ->assertRedirect()
            ->assertSessionHas('message', 'Thêm quyền thành công')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertDatabaseHas('permissions', ['name' => 'Quản trị Kho mới', 'guard_name' => 'web']);
    }

    public function test_ten_quyen_trung_hoac_qua_ngan_bi_tu_choi(): void
    {
        Permission::findOrCreate('Quản trị Menu', 'web');

        $this->actingAs($this->manager)
            ->post('/admin/insert_permission', ['permission' => 'Quản trị Menu'])
            ->assertSessionHasErrors(['permission' => 'Tên quyền đã tồn tại!']);

        $this->actingAs($this->manager)
            ->post('/admin/insert_permission', ['permission' => 'ab'])
            ->assertSessionHasErrors(['permission' => 'Tiêu đề quá ngắn!']);

        $this->actingAs($this->manager)
            ->post('/admin/insert_permission', [])
            ->assertSessionHasErrors(['permission' => 'Vui lòng nhập tên quyền!']);

        $this->assertSame(2, Permission::count());
    }

    public function test_luu_phan_quyen_tu_form_cap_dung_cac_quyen_da_chon(): void
    {
        $menu = Permission::findOrCreate('Quản trị Menu', 'web');
        $slide = Permission::findOrCreate('Quản trị Slide', 'web');
        $this->staff->assignRole(self::STAFF_ROLE);

        // The form posts permission ids as strings.
        $this->actingAs($this->manager)
            ->post('/admin/insert_permission/'.$this->staff->user_id, ['permission' => [(string) $menu->id, (string) $slide->id]])
            ->assertRedirect()
            ->assertSessionHas('message', 'Cấp quyền thành công');

        $role = Role::findByName(self::STAFF_ROLE, 'web');
        $this->assertTrue($role->hasPermissionTo('Quản trị Menu'));
        $this->assertTrue($role->hasPermissionTo('Quản trị Slide'));
    }

    public function test_luu_phan_quyen_cho_tai_khoan_chua_co_vai_tro_bao_loi(): void
    {
        $menu = Permission::findOrCreate('Quản trị Menu', 'web');

        $this->actingAs($this->manager)
            ->post('/admin/insert_permission/'.$this->staff->user_id, ['permission' => [(string) $menu->id]])
            ->assertRedirect()
            ->assertSessionHas('message', 'Vui lòng phân vai trò cho user!')
            ->assertSessionHas('iconMessage', 'error');
    }

    public function test_cap_vai_tro_khong_chon_hoac_vai_tro_la_bi_tu_choi(): void
    {
        $this->actingAs($this->manager)->post('/admin/insert_roles/'.$this->staff->user_id, [])
            ->assertSessionHasErrors(['role' => 'Vui lòng chọn một vai trò.']);
        $this->actingAs($this->manager)->post('/admin/insert_roles/'.$this->staff->user_id, ['role' => 'Không có'])
            ->assertSessionHasErrors(['role' => 'Vai trò không tồn tại.']);
        $this->actingAs($this->manager)->post('/admin/insert_roles/9999', ['role' => self::STAFF_ROLE])->assertNotFound();

        $this->assertFalse($this->staff->fresh()->hasAnyRole([self::STAFF_ROLE, self::ADMIN_ROLE]));
    }
}
