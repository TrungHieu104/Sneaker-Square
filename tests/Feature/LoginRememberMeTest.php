<?php

namespace Tests\Feature;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Covers what "remember me" leaves in the browser.
 *
 * It used to write the customer's password into a cookie for 24 hours, and the
 * login form printed that cookie straight back into the password field's value
 * attribute. The password was the remember-me token, stored in readable form.
 */
class LoginRememberMeTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PASSWORD = 'SecretPass123!';

    private function makeCustomer(): UserModel
    {
        // LoginRequest only accepts gmail addresses.
        $user = $this->makeUser(email: 'khachhang@gmail.com');
        $user->password = Hash::make(self::PASSWORD);
        $user->save();

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function cookieNames(\Illuminate\Testing\TestResponse $response): array
    {
        return array_map(
            fn ($cookie) => $cookie->getName(),
            $response->headers->getCookies()
        );
    }

    public function test_dang_nhap_khong_de_lai_mat_khau_trong_cookie(): void
    {
        $user = $this->makeCustomer();

        $response = $this->post(route('user.login_post'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'password') {
                // Only ever sent to clear the cookie the old scheme left behind,
                // so it must carry an expiry already in the past.
                $this->assertLessThan(
                    time(),
                    $cookie->getExpiresTime(),
                    'Cookie mật khẩu chỉ được gửi để xóa dấu vết cũ'
                );
            }

            // Cookies are encrypted, so decrypt before looking.
            $value = $cookie->getValue();
            $plain = rescue(fn () => decrypt($value, false), $value, report: false);

            $this->assertStringNotContainsString(
                self::PASSWORD,
                (string) (is_array($plain) ? json_encode($plain) : $plain),
                'Không cookie nào được chứa mật khẩu'
            );
        }
    }

    public function test_ghi_nho_dang_nhap_dung_token_cua_laravel(): void
    {
        $user = $this->makeCustomer();

        $this->post(route('user.login_post'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'remember' => 'on',
        ]);

        $this->assertNotNull($user->fresh()->remember_token, 'Laravel phải sinh remember_token thay cho việc lưu mật khẩu');
    }

    public function test_khong_tick_ghi_nho_thi_khong_sinh_token(): void
    {
        $user = $this->makeCustomer();

        $this->post(route('user.login_post'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_dia_chi_email_van_duoc_nho_de_dien_san(): void
    {
        $user = $this->makeCustomer();

        $response = $this->post(route('user.login_post'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'remember' => 'on',
        ]);

        $emailCookie = collect($response->headers->getCookies())
            ->firstWhere(fn ($cookie) => $cookie->getName() === 'email');

        $this->assertNotNull($emailCookie, 'Vẫn nhớ email để điền sẵn ô đăng nhập');
    }
}
