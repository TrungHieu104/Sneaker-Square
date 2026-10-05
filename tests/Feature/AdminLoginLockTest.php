<?php

namespace Tests\Feature;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin sign-in counts wrong passwords and locks the account for five
 * minutes after the fifth.
 */
class AdminLoginLockTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
    }

    private function signIn(string $password, string $login = 'admin@example.test')
    {
        return $this->post(route('admin.login_check'), ['email' => $login, 'password' => $password]);
    }

    public function test_dung_mat_khau_thi_vao_dashboard_va_xoa_so_lan_sai(): void
    {
        $this->admin->update(['login_attempts' => 3]);

        $this->signIn('secret-password')->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, (int) $this->admin->fresh()->login_attempts);
    }

    public function test_dang_nhap_bang_username_cung_duoc(): void
    {
        $this->signIn('secret-password', 'quantri')->assertRedirect(route('admin.dashboard'));
    }

    public function test_sai_mat_khau_thi_bao_so_lan_con_lai(): void
    {
        $this->signIn('sai')->assertSessionHas('message', 'Sai thông tin đăng nhập. Bạn còn 5 lần thử.');

        $this->assertSame(1, (int) $this->admin->fresh()->login_attempts);
    }

    public function test_sai_den_lan_thu_sau_thi_khoa_5_phut(): void
    {
        $this->admin->update(['login_attempts' => 5]);

        $this->signIn('sai')->assertSessionHas('message', 'Tài khoản của bạn đã bị khóa trong 5 phút. Vui lòng thử lại sau.');

        $this->assertTrue(Carbon::parse($this->admin->fresh()->locked_at)->isFuture());
    }

    public function test_dang_bi_khoa_thi_dung_mat_khau_cung_khong_vao_duoc(): void
    {
        $this->admin->update(['locked_at' => now()->addMinutes(3)]);

        $this->signIn('secret-password')->assertSessionHas('message', fn ($m) => str_starts_with($m, 'Vui lòng thử lại sau 2 phút'));
        $this->assertGuest();

        $this->signIn('sai')->assertSessionHas('message', fn ($m) => str_starts_with($m, 'Vui lòng thử lại sau'));
    }

    public function test_het_thoi_gian_khoa_thi_mo_lai(): void
    {
        $this->admin->update(['locked_at' => now()->subMinute(), 'login_attempts' => 2]);

        $this->signIn('sai')->assertSessionHas('message', 'Sai thông tin đăng nhập!');

        $this->assertNull($this->admin->fresh()->locked_at);
        $this->assertSame(0, (int) $this->admin->fresh()->login_attempts);
    }

    public function test_tai_khoan_khong_ton_tai(): void
    {
        $this->signIn('sai', 'khongco@example.test')->assertSessionHas('message', 'Sai thông tin đăng nhập!');
    }
}
