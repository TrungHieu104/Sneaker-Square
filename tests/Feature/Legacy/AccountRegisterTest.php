<?php

namespace Tests\Feature\Legacy;

use App\Mail\RegisterMail;
use App\Models\UserModel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises customer self-registration: what a valid form stores, the
 * welcome mail it queues, and every validation message the form can show.
 */
class AccountRegisterTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const EMAIL = 'khachmoi@gmail.com';

    private const PASSWORD = 'MatKhau@123';

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account; nothing may reach it.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn Bình',
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
            'same_password' => self::PASSWORD,
            'terms' => 'on',
        ], $overrides);
    }

    public function test_trang_dang_ky_mo_duoc(): void
    {
        $this->get(route('user.register'))
            ->assertOk()
            ->assertSee('name="same_password"', false)
            ->assertSee('name="terms"', false);
    }

    public function test_dang_ky_hop_le_tao_tai_khoan_khach_va_bam_mat_khau(): void
    {
        $this->post(route('user.register_post'), $this->form())
            ->assertRedirect(route('user.login'))
            ->assertSessionHas('message', 'Đăng ký thành công mời bạn đăng nhập')
            ->assertSessionHas('iconMessage', 'success');

        $user = UserModel::where('email', self::EMAIL)->firstOrFail();
        $this->assertSame('Nguyễn Văn Bình', $user->name);
        $this->assertSame(0, (int) $user->user_role);
        $this->assertSame(1, (int) $user->user_status);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_dang_ky_xong_dang_nhap_duoc_bang_mat_khau_vua_tao(): void
    {
        $this->post(route('user.register_post'), $this->form());

        $this->post(route('user.login_post'), ['email' => self::EMAIL, 'password' => self::PASSWORD])
            ->assertRedirect(route('home.page'));
        $this->assertAuthenticatedAs(UserModel::where('email', self::EMAIL)->firstOrFail());
    }

    public function test_ho_ten_bi_bo_the_html_va_khoang_trang(): void
    {
        $this->post(route('user.register_post'), $this->form(['name' => '  <b>Nguyễn Văn Bình</b> ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => self::EMAIL, 'name' => 'Nguyễn Văn Bình']);
    }

    public function test_dang_ky_gui_thu_chao_mung_toi_dung_dia_chi(): void
    {
        $this->post(route('user.register_post'), $this->form());

        $user = UserModel::where('email', self::EMAIL)->firstOrFail();
        Mail::assertQueued(RegisterMail::class, function (RegisterMail $mail) use ($user) {
            return $mail->hasTo(self::EMAIL) && $mail->user->is($user)
                && str_contains($mail->link, '/verified-email-register/'.$user->user_id.'?expires=');
        });
        Mail::assertQueuedCount(1);
    }

    public function test_thu_chao_mung_co_tieu_de_thong_tin_va_link_xac_nhan(): void
    {
        $user = $this->makeUser(email: self::EMAIL);

        $mail = new RegisterMail($user, RegisterMail::verifyLink($user));
        $mail->assertHasSubject('Đăng ký thành công tài khoản Sneaker Square ');

        $html = $mail->render();
        $this->assertStringContainsString('Chúc mừng bạn đã đăng ký thành công', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString(self::EMAIL, $html);
        $this->assertStringContainsString($user->created_at->format('d/m/Y'), $html);
        $this->assertStringContainsString(route('user.login'), $html);
        $this->assertStringContainsString(route('verifed_register', $user->user_id), $html);
        $this->assertSame([], $mail->attachments());
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidForms(): array
    {
        return [
            'thiếu họ tên' => [['name' => ''], 'name', 'Bạn chưa nhập họ và tên'],
            'họ tên quá ngắn' => [['name' => 'Bình'], 'name', 'Họ và tên quá ngắn'],
            'họ tên quá dài' => [['name' => str_repeat('a', 31)], 'name', 'Họ và tên quá dài'],
            'thiếu email' => [['email' => ''], 'email', 'Bạn chưa nhập email'],
            'email sai cú pháp' => [['email' => 'khong-phai-email'], 'email', 'Nhập email chưa đúng'],
            'email không phải gmail' => [['email' => 'khach@yahoo.com'], 'email', 'Bạn nhập email sai định dạng'],
            'thiếu mật khẩu' => [['password' => ''], 'password', 'Vui lòng nhập mật khẩu'],
            'mật khẩu không có chữ hoa' => [['password' => 'matkhau@123', 'same_password' => 'matkhau@123'], 'password', 'Mật khẩu phải có chữ in hoa'],
            'mật khẩu không có số' => [['password' => 'MatKhau@abc', 'same_password' => 'MatKhau@abc'], 'password', 'Mật khẩu phải có số'],
            'mật khẩu không có ký tự đặc biệt' => [['password' => 'MatKhau1234', 'same_password' => 'MatKhau1234'], 'password', 'Mật khẩu phải có ký tự đặc biệt'],
            'mật khẩu nhập lại không khớp' => [['same_password' => 'MatKhau@124'], 'password', 'Mật khẩu không khớp'],
            'thiếu mật khẩu nhập lại' => [['same_password' => ''], 'same_password', 'Bạn chưa nhập lại mật khẩu'],
            'mật khẩu nhập lại quá ngắn' => [['same_password' => 'Ab@1'], 'same_password', 'Mật khẩu nhập lại cùng từ 8 ký tự trở lên'],
            'chưa đồng ý điều khoản' => [['terms' => null], 'terms', 'Vui lòng chấp nhận điều khoản & chính sách'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidForms')]
    public function test_form_dang_ky_sai_bi_tu_choi_voi_thong_bao_tieng_viet(array $overrides, string $field, string $message): void
    {
        $this->from(route('user.register'))
            ->post(route('user.register_post'), $this->form($overrides))
            ->assertRedirect(route('user.register'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseMissing('users', ['email' => self::EMAIL]);
        Mail::assertNothingQueued();
    }

    public function test_email_da_ton_tai_bi_tu_choi(): void
    {
        $this->makeUser(email: self::EMAIL);

        $this->post(route('user.register_post'), $this->form())
            ->assertSessionHasErrors(['email' => 'Email này đã tồn tại']);

        $this->assertSame(1, UserModel::where('email', self::EMAIL)->count());
        Mail::assertNothingQueued();
    }

    public function test_link_xac_nhan_email_phai_co_chu_ky(): void
    {
        $user = $this->makeUser(email: self::EMAIL);

        $this->get(route('verifed_register', $user->user_id))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);

        $this->get(URL::temporarySignedRoute('verifed_register', now()->addDay(), ['user' => $user->user_id]))
            ->assertRedirect()
            ->assertSessionHas('message', 'Xác nhận email thành công');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_link_xac_nhan_email_qua_han_bi_tu_choi(): void
    {
        $user = $this->makeUser(email: self::EMAIL);
        $link = URL::temporarySignedRoute('verifed_register', now()->addDays(RegisterMail::LINK_DAYS), ['user' => $user->user_id]);
        $this->travel(RegisterMail::LINK_DAYS + 1)->days();

        $this->get($link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_gui_lai_mail_xac_nhan_can_dang_nhap(): void
    {
        $this->post(route('verification.send'))->assertRedirect(route('user.login'));
    }

    public function test_gui_lai_mail_xac_nhan_khi_da_dang_nhap(): void
    {
        Notification::fake();
        $user = $this->makeUser(email: self::EMAIL);
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_thu_chao_mung_mang_link_xac_nhan_co_chu_ky(): void
    {
        $user = $this->makeUser(email: self::EMAIL);

        $html = (new RegisterMail($user, RegisterMail::verifyLink($user)))->render();

        preg_match('/href="([^"]*verified-email-register[^"]*)"/', $html, $found);
        $this->get(html_entity_decode($found[1]))->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
