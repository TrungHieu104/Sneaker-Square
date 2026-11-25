<?php

namespace Tests\Feature\Legacy;

use App\Enums\CustomerSegment;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the admin account screens: staff accounts, the customer list,
 * a staff member's own profile and password, and who is let in at all.
 */
class AccountAdminTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private const ADMINS = 'Quản trị Tài khoản (Quản trị viên)';

    private const CUSTOMERS = 'Quản trị Tài khoản (Khách hàng)';

    private const NOT_ALLOWED = 'Không được phép truy cập';

    private const UPDATED = 'Cập nhập thành công!';

    private const USER_NOT_FOUND = 'Không tồn tại thông tin user';

    private const ACCOUNT_NOT_FOUND = 'Không tồn tại tài khoản!';

    private const NEW_EMAIL = 'nhanvienmoi@gmail.com';

    private const PASSWORD = 'MatKhau@123';

    private UserModel $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account; nothing may reach it.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->manager = $this->staff('quanly@gmail.com', 'quanly', self::ADMINS, self::CUSTOMERS);
    }

    private function staff(string $email, string $username, string ...$permissions): UserModel
    {
        $staff = $this->makeUser(email: $email, username: $username, role: 1);

        foreach ($permissions as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $staff;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function newStaffForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lê Văn Cường',
            'username' => 'nhanvienmoi',
            'email' => self::NEW_EMAIL,
            'passwords' => self::PASSWORD,
            'confirm-pass' => self::PASSWORD,
            'hid' => '1',
            'per' => '1',
        ], $overrides);
    }

    /**
     * The edit forms post the account id in a hidden field as well as the URL.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function editForm(UserModel $user, array $overrides = []): array
    {
        return array_merge([
            'id' => $user->user_id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'hid' => '1',
        ], $overrides);
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_bi_day_ve_trang_dang_nhap_quan_tri(): void
    {
        $this->get(route('account.index'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', self::NOT_ALLOWED);

        $this->post(route('account.store'), $this->newStaffForm())->assertRedirect(route('admin.login'));
        $this->assertDatabaseMissing('users', ['email' => self::NEW_EMAIL]);
    }

    public function test_tai_khoan_khach_hang_khong_vao_duoc_trang_quan_tri_tai_khoan(): void
    {
        $customer = $this->makeUser(email: 'khach@gmail.com', username: 'khachhang');

        $this->actingAs($customer)->get(route('account.index'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', self::NOT_ALLOWED);
        $this->actingAs($customer)->post(route('account.store'), $this->newStaffForm())
            ->assertRedirect(route('admin.login'));
        $this->actingAs($customer)->post('/admin/insert_roles/'.$customer->user_id, ['role' => 'Quản trị viên'])
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('users', ['email' => self::NEW_EMAIL]);
        $this->assertFalse($customer->fresh()->hasRole('Quản trị viên'));
    }

    public function test_nhan_vien_bi_vo_hieu_hoa_khong_vao_duoc(): void
    {
        $this->manager->user_status = 0;
        $this->manager->save();

        $this->actingAs($this->manager)->get(route('account.index'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', self::NOT_ALLOWED);
    }

    public function test_nhan_vien_khong_co_quyen_khach_hang_khong_xem_duoc_danh_sach_khach(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvien');
        $customer = $this->makeUser(email: 'khach@gmail.com', username: 'khachhang');

        $this->actingAs($staff)->get(route('account.user'))->assertForbidden();
        $this->actingAs($staff)->get(route('account.edituser', Crypt::encrypt($customer->user_id)))->assertForbidden();
    }

    // -------------------------------------------------------------- staff list

    public function test_danh_sach_quan_tri_chi_co_tai_khoan_nhan_vien(): void
    {
        $this->staff('nhanvien@gmail.com', 'nhanvienkho');
        $this->makeUser(email: 'khach@gmail.com', username: 'khachhangle');

        $this->actingAs($this->manager)->get(route('account.index'))
            ->assertOk()
            ->assertSee('nhanvienkho')
            ->assertDontSee('khachhangle');
    }

    public function test_tim_nhan_vien_theo_ten_dang_nhap(): void
    {
        $this->staff('nhanvien@gmail.com', 'nhanvienkho');
        $this->staff('banhang@gmail.com', 'nhanvienban');
        $this->staff('ketoan@gmail.com', 'ketoantruong');

        $this->actingAs($this->manager)->get(route('account.index', ['keyword' => 'nhanvien']))
            ->assertOk()
            ->assertSee('nhanvienkho')
            ->assertSee('nhanvienban')
            ->assertDontSee('ketoantruong');
    }

    // -------------------------------------------------------------- create

    public function test_trang_them_tai_khoan_mo_duoc(): void
    {
        $this->actingAs($this->manager)->get(route('account.create'))
            ->assertOk()
            ->assertSee('name="confirm-pass"', false);
    }

    public function test_them_tai_khoan_quan_tri_hop_le(): void
    {
        $this->actingAs($this->manager)->post(route('account.store'), $this->newStaffForm())
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('message', 'Thêm mới thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $user = UserModel::where('email', self::NEW_EMAIL)->firstOrFail();
        $this->assertSame('Lê Văn Cường', $user->name);
        $this->assertSame('nhanvienmoi', $user->username);
        $this->assertSame(1, (int) $user->user_role);
        $this->assertSame(1, (int) $user->user_status);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_tai_khoan_vua_them_dang_nhap_trang_quan_tri_duoc(): void
    {
        $this->actingAs($this->manager)->post(route('account.store'), $this->newStaffForm());
        auth()->logout();

        $this->post(route('admin.login_check'), ['email' => 'nhanvienmoi', 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_them_tai_khoan_khach_hang_va_khoa_ngay(): void
    {
        $this->actingAs($this->manager)->post(route('account.store'), $this->newStaffForm(['hid' => '0', 'per' => '0']));

        $user = UserModel::where('email', self::NEW_EMAIL)->firstOrFail();
        $this->assertSame(0, (int) $user->user_role);
        $this->assertSame(0, (int) $user->user_status);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidNewStaff(): array
    {
        return [
            'thiếu họ tên' => [['name' => ''], 'name', 'Vui lòng nhập họ tên!'],
            'họ tên quá ngắn' => [['name' => 'Lê'], 'name', 'Họ tên quá ngắn!'],
            'họ tên quá dài' => [['name' => str_repeat('a', 51)], 'name', 'Họ tên quá dài!'],
            'thiếu tên đăng nhập' => [['username' => ''], 'username', 'Vui lòng nhập tên đăng nhập!'],
            'tên đăng nhập quá ngắn' => [['username' => 'nvmo'], 'username', 'Tên đăng nhập quá ngắn!'],
            'tên đăng nhập quá dài' => [['username' => str_repeat('a', 51)], 'username', 'Tên đăng nhập quá dài!'],
            'tên đăng nhập đã có' => [['username' => 'quanly'], 'username', 'Tên đăng nhập đã tồn tại!'],
            'thiếu email' => [['email' => ''], 'email', 'Bạn chưa nhập email!'],
            'email sai cú pháp' => [['email' => 'khong-phai-email'], 'email', 'Email nhập không đúng định dạng!'],
            'email không phải gmail' => [['email' => 'nhanvien@yahoo.com'], 'email', 'Email phải có đuôi là @gmail.com!'],
            'email đã có' => [['email' => 'quanly@gmail.com'], 'email', 'Email đã được đăng ký!'],
            'thiếu mật khẩu' => [['passwords' => ''], 'passwords', 'Vui lòng nhập mật khẩu!'],
            'mật khẩu quá ngắn' => [['passwords' => 'Ab@1', 'confirm-pass' => 'Ab@1'], 'passwords', 'Mật khẩu ít nhất 8 kí tự!'],
            'mật khẩu quá dài' => [['passwords' => 'Ab@1'.str_repeat('a', 27), 'confirm-pass' => 'Ab@1'.str_repeat('a', 27)], 'passwords', 'Mật khẩu tối đa 30 kí tự!'],
            'mật khẩu thiếu ký tự đặc biệt' => [['passwords' => 'MatKhau123', 'confirm-pass' => 'MatKhau123'], 'passwords', 'Mật khẩu bao gồm chữ thường, hoa và ít nhất một kí tự đặc biệt!'],
            'thiếu nhập lại' => [['confirm-pass' => ''], 'confirm-pass', 'Vui lòng nhập lại mật khẩu!'],
            'nhập lại quá ngắn' => [['confirm-pass' => 'Ab@1'], 'confirm-pass', 'Mật khẩu ít nhất 8 kí tự'],
            'nhập lại không khớp' => [['confirm-pass' => 'MatKhau@124'], 'confirm-pass', 'Hai mật khẩu không khớp!'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidNewStaff')]
    public function test_form_them_tai_khoan_sai_bi_tu_choi(array $overrides, string $field, string $message): void
    {
        $this->actingAs($this->manager)
            ->post(route('account.store'), $this->newStaffForm($overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame(1, UserModel::count());
    }

    // -------------------------------------------------------------- edit staff

    public function test_trang_sua_tai_khoan_quan_tri_mo_duoc(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)->get(route('account.edits', Crypt::encrypt($staff->user_id)))
            ->assertOk()
            ->assertSee('value="nhanvienkho"', false)
            ->assertSee('name="id" value="'.$staff->user_id.'"', false);
    }

    public function test_sua_tai_khoan_khong_ton_tai_thi_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->manager)->get(route('account.edits', Crypt::encrypt(9999)))
            ->assertRedirect('admin/account')
            ->assertSessionHas('message', self::USER_NOT_FOUND)
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_cap_nhat_tai_khoan_quan_tri_hop_le(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)
            ->put(route('account.update', $staff->user_id), $this->editForm($staff, [
                'name' => 'Phạm Thị Dung',
                'username' => 'nhanviendung',
                'email' => 'dung@gmail.com',
                'hid' => '0',
            ]))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('message', self::UPDATED);

        $staff->refresh();
        $this->assertSame('Phạm Thị Dung', $staff->name);
        $this->assertSame('nhanviendung', $staff->username);
        $this->assertSame('dung@gmail.com', $staff->email);
        $this->assertSame(0, (int) $staff->user_status);
        $this->assertTrue(Hash::check('secret-password', $staff->password));
    }

    public function test_cap_nhat_giu_nguyen_ten_dang_nhap_va_email_cu_thi_duoc(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)
            ->put(route('account.update', $staff->user_id), $this->editForm($staff, ['name' => 'Phạm Thị Dung']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Phạm Thị Dung', $staff->fresh()->name);
    }

    public function test_cap_nhat_tai_khoan_khong_ton_tai_thi_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->manager)
            ->put(route('account.update', 9999), [
                'id' => 9999, 'name' => 'Phạm Thị Dung', 'username' => 'nhanviendung', 'email' => 'dung@gmail.com',
            ])
            ->assertRedirect('admin/account')
            ->assertSessionHas('message', self::USER_NOT_FOUND);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidStaffEdits(): array
    {
        return [
            'thiếu họ tên' => [['name' => ''], 'name', 'Vui lòng nhập họ tên!'],
            'họ tên quá ngắn' => [['name' => 'Lê'], 'name', 'Tên quá ngắn!'],
            'họ tên quá dài' => [['name' => str_repeat('a', 51)], 'name', 'Tên quá dài!'],
            'thiếu tên đăng nhập' => [['username' => ''], 'username', 'Vui lòng nhập tên đăng nhập!'],
            'tên đăng nhập quá ngắn' => [['username' => 'nvmo'], 'username', 'Tên đăng nhập quá ngắn!'],
            'tên đăng nhập quá dài' => [['username' => str_repeat('a', 51)], 'username', 'Tên đăng nhập quá dài!'],
            'tên đăng nhập của người khác' => [['username' => 'quanly'], 'username', 'Tên đăng nhập đã tồn tại!'],
            'thiếu email' => [['email' => ''], 'email', 'Bạn chưa nhập email!'],
            'email sai cú pháp' => [['email' => 'khong-phai-email'], 'email', 'Email nhập không đúng định dạng!'],
            'email không phải gmail' => [['email' => 'nhanvien@yahoo.com'], 'email', 'Email phải có đuôi là @gmail.com!'],
            'email của người khác' => [['email' => 'quanly@gmail.com'], 'email', 'Email đã được đăng ký!'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidStaffEdits')]
    public function test_form_sua_tai_khoan_quan_tri_sai_bi_tu_choi(array $overrides, string $field, string $message): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)
            ->put(route('account.update', $staff->user_id), $this->editForm($staff, $overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame('nhanvienkho', $staff->fresh()->username);
        $this->assertSame('nhanvien@gmail.com', $staff->fresh()->email);
    }

    // -------------------------------------------------------------- own profile

    public function test_trang_thong_tin_ca_nhan_mo_duoc(): void
    {
        $this->actingAs($this->manager)->get(route('account.info', Crypt::encrypt($this->manager->user_id)))
            ->assertOk()
            ->assertSee('value="quanly"', false)
            ->assertSee(route('change.password'), false);
    }

    public function test_thong_tin_ca_nhan_ma_id_khong_giai_ma_duoc_thi_ve_dashboard(): void
    {
        $this->actingAs($this->manager)->get(route('account.info', 'khong-phai-ma-hoa'))
            ->assertRedirect('admin/dashboard')
            ->assertSessionHas('message', self::ACCOUNT_NOT_FOUND);
    }

    public function test_thong_tin_ca_nhan_cua_nguoi_khac_bi_tu_choi(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)->get(route('account.info', Crypt::encrypt($staff->user_id)))->assertForbidden();
        $this->actingAs($this->manager)->get(route('account.info', Crypt::encrypt(9999)))->assertForbidden();
    }

    public function test_cap_nhat_thong_tin_ca_nhan(): void
    {
        $info = route('account.info', Crypt::encrypt($this->manager->user_id));

        $this->actingAs($this->manager)->from($info)
            ->post(route('account.updateinfo', $this->manager->user_id), $this->editForm($this->manager, [
                'name' => 'Quản Lý Mới',
                'username' => 'quanlymoi',
                'email' => 'quanlymoi@gmail.com',
            ]))
            ->assertRedirect($info)
            ->assertSessionHas('message', self::UPDATED);

        $this->manager->refresh();
        $this->assertSame('Quản Lý Mới', $this->manager->name);
        $this->assertSame('quanlymoi', $this->manager->username);
        $this->assertSame('quanlymoi@gmail.com', $this->manager->email);
    }

    public function test_cap_nhat_thong_tin_cua_nguoi_khac_bi_tu_choi(): void
    {
        $staff = $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)
            ->post(route('account.updateinfo', $staff->user_id), $this->editForm($staff, ['name' => 'Bị Sửa Hộ']))
            ->assertForbidden();

        $this->assertNotSame('Bị Sửa Hộ', $staff->fresh()->name);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidOwnInfo(): array
    {
        return [
            'thiếu họ tên' => [['name' => ''], 'name', 'Vui lòng nhập họ tên!'],
            'họ tên quá ngắn' => [['name' => 'Lê'], 'name', 'Tên quá ngắn!'],
            'họ tên quá dài' => [['name' => str_repeat('a', 51)], 'name', 'Tên quá dài!'],
            'thiếu tên đăng nhập' => [['username' => ''], 'username', 'Vui lòng nhập tên đăng nhập!'],
            'tên đăng nhập quá ngắn' => [['username' => 'qlmo'], 'username', 'Tên đăng nhập quá ngắn!'],
            'tên đăng nhập quá dài' => [['username' => str_repeat('a', 51)], 'username', 'Tên đăng nhập quá dài!'],
            'tên đăng nhập của người khác' => [['username' => 'nhanvienkho'], 'username', 'Tên đăng nhập đã tồn tại!'],
            'thiếu email' => [['email' => ''], 'email', 'Bạn chưa nhập email!'],
            'email sai cú pháp' => [['email' => 'khong-phai-email'], 'email', 'Email nhập không đúng định dạng!'],
            'email không phải gmail' => [['email' => 'quanly@yahoo.com'], 'email', 'Email phải có đuôi là @gmail.com!'],
            'email của người khác' => [['email' => 'nhanvien@gmail.com'], 'email', 'Email đã được đăng ký!'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidOwnInfo')]
    public function test_form_thong_tin_ca_nhan_sai_bi_tu_choi(array $overrides, string $field, string $message): void
    {
        $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)
            ->post(route('account.updateinfo', $this->manager->user_id), $this->editForm($this->manager, $overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame('quanly', $this->manager->fresh()->username);
    }

    // -------------------------------------------------------------- own password

    public function test_doi_mat_khau_quan_tri_tu_form_dung_mat_khau_hien_tai(): void
    {
        $this->actingAs($this->manager)
            ->post(route('change.password'), [
                'password' => 'secret-password',
                'new-pass' => 'MatKhauMoi@1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message', 'Mật khẩu đã được thay đổi!')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertTrue(Hash::check('MatKhauMoi@1', $this->manager->fresh()->password));
    }

    public function test_doi_mat_khau_quan_tri_sai_mat_khau_hien_tai(): void
    {
        $this->actingAs($this->manager)
            ->post(route('change.password'), [
                'password' => 'sai-mat-khau',
                'new-pass' => 'MatKhauMoi@1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message', 'Mật khẩu hiện tại không đúng.')
            ->assertSessionHas('iconMessage', 'info');

        $this->assertTrue(Hash::check('secret-password', $this->manager->fresh()->password));
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidPasswordChanges(): array
    {
        return [
            'thiếu mật khẩu hiện tại' => [['password' => ''], 'password', 'Vui lòng nhập mật khẩu hiện tại!'],
            'thiếu mật khẩu mới' => [['new-pass' => ''], 'new-pass', 'Vui lòng nhập mật khẩu mới!'],
            'mật khẩu mới quá ngắn' => [['new-pass' => 'Ab@1'], 'new-pass', 'Mật khẩu ít nhất 8 kí tự!'],
            'mật khẩu mới quá dài' => [['new-pass' => 'Ab@1'.str_repeat('a', 27)], 'new-pass', 'Mật khẩu tối đa 30 kí tự!'],
            'mật khẩu mới thiếu ký tự đặc biệt' => [['new-pass' => 'MatKhau123'], 'new-pass', 'Mật khẩu bao gồm chữ thường, hoa và ít nhất một kí tự đặc biệt!'],
        ];
    }

    /**
     * @param  array<string, string>  $overrides
     */
    #[DataProvider('invalidPasswordChanges')]
    public function test_form_doi_mat_khau_quan_tri_sai_bi_tu_choi(array $overrides, string $field, string $message): void
    {
        $this->actingAs($this->manager)
            ->post(route('change.password'), array_merge([
                'password' => 'secret-password',
                'new-pass' => 'MatKhauMoi@1',
            ], $overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertTrue(Hash::check('secret-password', $this->manager->fresh()->password));
    }

    // -------------------------------------------------------------- customers

    public function test_danh_sach_khach_hang_chi_co_khach(): void
    {
        $this->makeUser(email: 'khach@gmail.com', username: 'khachhang');
        $this->staff('nhanvien@gmail.com', 'nhanvienkho');

        $this->actingAs($this->manager)->get(route('account.user'))
            ->assertOk()
            ->assertSee('khach@gmail.com')
            ->assertDontSee('nhanvien@gmail.com');
    }

    public function test_loc_khach_chua_mua_va_theo_phan_khuc(): void
    {
        $this->makeUser(email: 'chuamua@gmail.com', username: 'chuamua');
        $loyal = $this->makeUser(email: 'thanthiet@gmail.com', username: 'thanthiet');
        $this->inSegment($loyal, CustomerSegment::Loyal);

        $this->actingAs($this->manager)->get(route('account.user', ['phan-khuc' => 'chua-mua']))
            ->assertOk()
            ->assertSee('chuamua@gmail.com')
            ->assertDontSee('thanthiet@gmail.com');

        $this->actingAs($this->manager)->get(route('account.user', ['phan-khuc' => CustomerSegment::Loyal->value]))
            ->assertOk()
            ->assertSee('thanthiet@gmail.com')
            ->assertDontSee('chuamua@gmail.com');
    }

    public function test_trang_chi_tiet_khach_hang_mo_duoc(): void
    {
        $customer = $this->makeUser(email: 'khach@gmail.com', username: 'khachhang');

        $this->actingAs($this->manager)->get(route('account.edituser', Crypt::encrypt($customer->user_id)))
            ->assertOk()
            ->assertSee('value="khach@gmail.com"', false);
    }

    public function test_chi_tiet_khach_hang_khong_ton_tai_hoac_ma_sai(): void
    {
        $this->actingAs($this->manager)->get(route('account.edituser', Crypt::encrypt(9999)))
            ->assertRedirect('admin/account-user')
            ->assertSessionHas('message', 'Không tồn tại tài khoản');

        $this->actingAs($this->manager)->get(route('account.edituser', 'khong-phai-ma-hoa'))
            ->assertRedirect('admin/account-user')
            ->assertSessionHas('message', self::ACCOUNT_NOT_FOUND);
    }

    public function test_khoa_va_mo_lai_tai_khoan_khach(): void
    {
        $customer = $this->makeUser(email: 'khach@gmail.com', username: 'khachhang');

        $this->actingAs($this->manager)->put(route('account.updateuser', $customer->user_id), ['id' => $customer->user_id, 'hid' => '0'])
            ->assertRedirect(route('account.user'))
            ->assertSessionHas('message', self::UPDATED);
        $this->assertSame(0, (int) $customer->fresh()->user_status);

        $this->actingAs($this->manager)->put(route('account.updateuser', $customer->user_id), ['id' => $customer->user_id, 'hid' => '1']);
        $this->assertSame(1, (int) $customer->fresh()->user_status);
    }

    public function test_khoa_tai_khoan_khach_khong_ton_tai(): void
    {
        $this->actingAs($this->manager)->put(route('account.updateuser', 9999), ['hid' => '0'])
            ->assertRedirect('admin/account')
            ->assertSessionHas('message', self::USER_NOT_FOUND);
    }

    public function test_quyen_khach_hang_khong_khoa_hay_xem_duoc_tai_khoan_quan_tri(): void
    {
        $staff = $this->makeUser(email: 'cskh@example.test', username: 'chamsockhach', role: 1);
        $staff->givePermissionTo(Permission::findOrCreate('Quản trị Tài khoản (Khách hàng)', 'web'));

        $this->actingAs($staff)->put(route('account.updateuser', $this->manager->user_id), ['hid' => '0'])
            ->assertSessionHas('message', self::USER_NOT_FOUND);
        $this->assertSame(1, (int) $this->manager->fresh()->user_status);

        $this->actingAs($staff)->get(route('account.edituser', Crypt::encrypt($this->manager->user_id)))
            ->assertRedirect('admin/account-user');
    }

    // -------------------------------------------------------------- export & logout

    public function test_xuat_excel_khach_hang_va_quan_tri(): void
    {
        Excel::fake();

        $this->actingAs($this->manager)->post(route('exportus.scv'))->assertOk();
        $this->actingAs($this->manager)->post(route('exportad.scv'))->assertOk();

        Excel::assertDownloaded('Khách hàng.xlsx');
        Excel::assertDownloaded('Quản trị.xlsx');
    }

    public function test_dang_xuat_quan_tri(): void
    {
        $this->actingAs($this->manager)->get(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
