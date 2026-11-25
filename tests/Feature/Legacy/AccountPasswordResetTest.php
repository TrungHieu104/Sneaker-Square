<?php

namespace Tests\Feature\Legacy;

use App\Mail\ResetPassword;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The "forgot password" flow: the mail with a one-time link that expires,
 * the reset form behind that link and the new password it stores.
 */
class AccountPasswordResetTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const EMAIL = 'khach@gmail.com';

    private const NEW_PASSWORD = 'MatKhauMoi@1';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account; nothing may reach it.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->customer = $this->makeUser(email: self::EMAIL);
    }

    /**
     * Asks for a reset link and returns the one the mail carries.
     */
    private function requestReset(): string
    {
        $this->post(route('user.forgot_post'), ['email' => self::EMAIL]);

        $link = '';
        Mail::assertQueued(ResetPassword::class, function (ResetPassword $mail) use (&$link) {
            $link = $mail->link;

            return true;
        });

        return $link;
    }

    private function tokenOf(string $link): string
    {
        return basename((string) parse_url($link, PHP_URL_PATH));
    }

    private function resetWith(string $link, string $password, ?string $again = null): TestResponse
    {
        return $this->post(route('user.reset_pass_post', $this->tokenOf($link)), [
            'email' => self::EMAIL,
            'password' => $password,
            'same_password' => $again ?? $password,
        ]);
    }

    public function test_trang_quen_mat_khau_mo_duoc(): void
    {
        $this->get(route('user.forgot'))
            ->assertOk()
            ->assertSee(route('user.forgot_post'), false);
    }

    public function test_email_ton_tai_thi_gui_thu_kem_link_dat_lai(): void
    {
        $this->from(route('user.forgot'))
            ->post(route('user.forgot_post'), ['email' => self::EMAIL])
            ->assertRedirect(route('user.forgot'))
            ->assertSessionHas('message', 'Đã gửi mail')
            ->assertSessionHas('iconMessage', 'success');

        Mail::assertQueued(ResetPassword::class, function (ResetPassword $mail) {
            return $mail->hasTo(self::EMAIL)
                && str_starts_with($mail->link, url('/doi-mat-khau/'))
                && str_contains($mail->link, 'email='.urlencode(self::EMAIL))
                && $mail->mailDate instanceof Carbon;
        });
        Mail::assertQueuedCount(1);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => self::EMAIL]);
    }

    public function test_yeu_cau_dat_lai_khong_dung_toi_token_ghi_nho_dang_nhap(): void
    {
        $this->customer->forceFill(['remember_token' => 'token-ghi-nho-dang-nhap'])->save();

        $this->requestReset();

        $this->assertSame('token-ghi-nho-dang-nhap', $this->customer->fresh()->remember_token);
    }

    public function test_moi_lan_yeu_cau_lai_thi_link_cu_mat_hieu_luc(): void
    {
        $first = $this->requestReset();
        Mail::fake();
        $second = $this->requestReset();

        $this->assertNotSame($first, $second);
        $this->get($first)->assertNotFound();
        $this->get($second)->assertOk();
    }

    public function test_gui_yeu_cau_dat_lai_qua_nhieu_lan_bi_chan(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post(route('user.forgot_post'), ['email' => self::EMAIL])->assertRedirect();
        }

        $this->post(route('user.forgot_post'), ['email' => self::EMAIL])->assertStatus(429);
        Mail::assertQueuedCount(3);
    }

    public function test_email_khong_ton_tai_thi_khong_gui_thu_va_bao_loi(): void
    {
        $this->post(route('user.forgot_post'), ['email' => 'nguoi-la@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('iconMessage', 'error');

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Mail::assertNothingQueued();
    }

    public function test_thu_dat_lai_mat_khau_co_ten_thoi_diem_va_link(): void
    {
        $sentAt = Carbon::create(2026, 11, 25, 9, 30, 15);
        $link = route('user.reset_pass', ['token' => 'token-mau', 'email' => self::EMAIL]);

        $mail = new ResetPassword($this->customer, $sentAt, $link);
        $mail->assertHasSubject('Đặt Lại Mật Khẩu');

        $html = $mail->render();
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString('Chúng tôi nhận được yêu cầu khôi phục lại mật khẩu', $html);
        $this->assertStringContainsString('09:30:15 25/11/2026', $html);
        $this->assertStringContainsString(e($link), $html);
        $this->assertSame([], $mail->attachments());
    }

    public function test_link_dat_lai_hop_le_mo_form(): void
    {
        $link = $this->requestReset();

        $this->get($link)
            ->assertOk()
            ->assertSee(route('user.reset_pass_post', $this->tokenOf($link)), false)
            ->assertSee('name="email" value="'.self::EMAIL.'"', false)
            ->assertSee('name="same_password"', false);
    }

    public function test_link_dat_lai_sai_token_hoac_sai_email_bi_404(): void
    {
        $link = $this->requestReset();

        $this->get(route('user.reset_pass', ['token' => 'token-khong-ton-tai', 'email' => self::EMAIL]))->assertNotFound();
        $this->get(route('user.reset_pass', ['token' => $this->tokenOf($link), 'email' => 'nguoi-khac@gmail.com']))->assertNotFound();
        $this->get(route('user.reset_pass', $this->tokenOf($link)))->assertNotFound();
    }

    public function test_link_dat_lai_qua_60_phut_thi_het_han(): void
    {
        $link = $this->requestReset();
        $this->travel(61)->minutes();

        $this->get($link)->assertNotFound();
        $this->resetWith($link, self::NEW_PASSWORD)
            ->assertRedirect(route('user.forgot'))
            ->assertSessionHas('message', 'Link đặt lại mật khẩu đã hết hạn, vui lòng gửi lại yêu cầu.');
        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
    }

    public function test_dat_lai_mat_khau_hop_le_luu_mat_khau_moi(): void
    {
        $link = $this->requestReset();

        $this->resetWith($link, self::NEW_PASSWORD)
            ->assertRedirect(route('user.login'))
            ->assertSessionHas('message', 'Đổi mật khẩu thành công')
            ->assertSessionHas('iconMessage', 'success');

        $user = $this->customer->fresh();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->password));
        $this->assertFalse(Hash::check('secret-password', $user->password));

        $this->post(route('user.login_post'), ['email' => self::EMAIL, 'password' => self::NEW_PASSWORD])
            ->assertRedirect(route('home.page'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_dat_lai_mat_khau_lam_mat_hieu_luc_cookie_ghi_nho_dang_nhap(): void
    {
        $this->customer->forceFill(['remember_token' => 'cookie-bi-lo'])->save();

        $this->resetWith($this->requestReset(), self::NEW_PASSWORD);

        $this->assertNotSame('cookie-bi-lo', $this->customer->fresh()->remember_token);
    }

    public function test_link_da_dung_de_dat_lai_thi_khong_dung_lai_duoc(): void
    {
        $link = $this->requestReset();
        $this->resetWith($link, self::NEW_PASSWORD);

        $this->get($link)->assertNotFound();
        $this->resetWith($link, 'MatKhauKhac@2')->assertRedirect(route('user.forgot'));
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $this->customer->fresh()->password));
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'thiếu mật khẩu' => [['password' => '', 'same_password' => self::NEW_PASSWORD], 'password', 'Vui lòng nhập mật khẩu'],
            'không có chữ hoa' => [['password' => 'matkhaumoi@1', 'same_password' => 'matkhaumoi@1'], 'password', 'Mật khẩu phải có chữ in hoa'],
            'không có số' => [['password' => 'MatKhauMoi@x', 'same_password' => 'MatKhauMoi@x'], 'password', 'Mật khẩu phải có số'],
            'không có ký tự đặc biệt' => [['password' => 'MatKhauMoi12', 'same_password' => 'MatKhauMoi12'], 'password', 'Mật khẩu phải có ký tự đặc biệt'],
            'nhập lại không khớp' => [['password' => self::NEW_PASSWORD, 'same_password' => 'MatKhauMoi@2'], 'password', 'Mật khẩu không khớp'],
            'thiếu nhập lại' => [['password' => self::NEW_PASSWORD, 'same_password' => ''], 'same_password', 'Bạn chưa nhập lại mật khẩu'],
            'nhập lại quá ngắn' => [['password' => self::NEW_PASSWORD, 'same_password' => 'Ab@1'], 'same_password', 'Mật khẩu nhập lại phải có ít nhất 8 ký tự'],
        ];
    }

    /**
     * @param  array<string, string>  $form
     */
    #[DataProvider('invalidPasswords')]
    public function test_mat_khau_moi_sai_quy_tac_bi_tu_choi(array $form, string $field, string $message): void
    {
        $link = $this->requestReset();

        $this->post(route('user.reset_pass_post', $this->tokenOf($link)), $form + ['email' => self::EMAIL])
            ->assertSessionHasErrors([$field => $message]);

        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
        $this->get($link)->assertOk();
    }
}
