<?php

namespace Tests\Feature\Legacy;

use App\Mail\sendMailPass;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What a signed-in customer can change on "Tài khoản của tôi": name and
 * phone, the e-mail address, and the password, which needs the one-time link
 * from the confirmation mail. Every write lands on the signed-in account only.
 */
class AccountProfileTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const EMAIL = 'khach@gmail.com';

    private const OTHER_EMAIL = 'khachkhac@gmail.com';

    private const NEW_EMAIL = 'diachimoi@gmail.com';

    private const PHONE = '0912345678';

    private UserModel $customer;

    private UserModel $other;

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account; nothing may reach it.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->customer = $this->makeUser(email: self::EMAIL, username: 'khachhang');
        $this->other = $this->makeUser(email: self::OTHER_EMAIL, username: 'khachkhac');
    }

    /**
     * What the profile form posts.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profileForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Trần Thị Cẩm',
            'user_phone' => self::PHONE,
        ], $overrides);
    }

    /**
     * Asks for the password-change mail and returns the token its link carries.
     */
    private function requestChangeLink(): string
    {
        $this->actingAs($this->customer)->post(route('send_mail_pass'));

        $link = '';
        Mail::assertQueued(sendMailPass::class, function (sendMailPass $mail) use (&$link) {
            $link = $mail->link;

            return true;
        });

        return basename((string) parse_url($link, PHP_URL_PATH));
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_khong_vao_duoc_trang_tai_khoan(): void
    {
        $this->get(route('thong-tin-tai-khoan.index'))->assertRedirect(route('user.login'));
        $this->put(route('thong-tin-tai-khoan.update', $this->customer->user_id), $this->profileForm())
            ->assertRedirect(route('user.login'));
        $this->post(route('user.change_email', $this->customer->user_id), ['email' => self::NEW_EMAIL])
            ->assertRedirect(route('user.login'));

        $this->assertSame('Nguyễn Văn A', $this->customer->fresh()->name);
        $this->assertSame(self::EMAIL, $this->customer->fresh()->email);
    }

    // -------------------------------------------------------------- profile

    public function test_cap_nhat_ho_ten_va_so_dien_thoai(): void
    {
        $this->actingAs($this->customer)
            ->from(route('thong-tin-tai-khoan.index'))
            ->put(route('thong-tin-tai-khoan.update', $this->customer->user_id), $this->profileForm())
            ->assertRedirect(route('thong-tin-tai-khoan.index'))
            ->assertSessionHas('message', 'Cập nhật thành công !')
            ->assertSessionHas('iconMessage', 'success');

        $user = $this->customer->fresh();
        $this->assertSame('Trần Thị Cẩm', $user->name);
        $this->assertSame(self::PHONE, $user->user_phone);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_trang_tai_khoan_khong_in_ma_bam_mat_khau(): void
    {
        $this->actingAs($this->customer)->get(route('thong-tin-tai-khoan.index'))
            ->assertOk()
            ->assertDontSee($this->customer->fresh()->password, false);
    }

    public function test_form_thong_tin_khong_doi_duoc_mat_khau(): void
    {
        $this->actingAs($this->customer)
            ->put(route('thong-tin-tai-khoan.update', $this->customer->user_id), $this->profileForm(['password' => 'MatKhauLa@1']));

        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
    }

    public function test_cap_nhat_thong_tin_chi_ghi_vao_tai_khoan_dang_dang_nhap(): void
    {
        $this->actingAs($this->customer)
            ->put(route('thong-tin-tai-khoan.update', $this->other->user_id), $this->profileForm())
            ->assertSessionHasNoErrors();

        $this->assertSame('Nguyễn Văn A', $this->other->fresh()->name);
        $this->assertNull($this->other->fresh()->user_phone);
        $this->assertSame('Trần Thị Cẩm', $this->customer->fresh()->name);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidProfiles(): array
    {
        return [
            'thiếu họ tên' => [['name' => ''], 'name', 'Bạn chưa nhập họ và tên'],
            'họ tên quá ngắn' => [['name' => 'Cẩm'], 'name', 'Họ và tên quá ngắn'],
            'họ tên quá dài' => [['name' => str_repeat('a', 31)], 'name', 'Họ và tên quá dài'],
            'thiếu số điện thoại' => [['user_phone' => ''], 'user_phone', 'Bạn chưa nhập số điện thoại'],
            'số điện thoại sai' => [['user_phone' => '912345678'], 'user_phone', 'Định dạng số điện thoại không đúng. Ví dụ: 0383144668'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidProfiles')]
    public function test_thong_tin_sai_bi_tu_choi(array $overrides, string $field, string $message): void
    {
        $this->actingAs($this->customer)
            ->put(route('thong-tin-tai-khoan.update', $this->customer->user_id), $this->profileForm($overrides))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame('Nguyễn Văn A', $this->customer->fresh()->name);
    }

    /**
     * @return array<string, array{\Closure(): UploadedFile, string}>
     */
    public static function invalidAvatars(): array
    {
        return [
            'không phải ảnh' => [fn () => UploadedFile::fake()->create('ho-so.pdf', 10, 'application/pdf'), 'File phải là một hình ảnh'],
            'ảnh gif' => [fn () => UploadedFile::fake()->image('anh.gif'), 'Định dạng được hỗ trợ: jpeg, png, jpg'],
            'ảnh quá 2MB' => [fn () => UploadedFile::fake()->image('anh.jpg')->size(3000), 'Kích thước ảnh không được vượt quá 2MB'],
        ];
    }

    /**
     * Only refused uploads are sent: an accepted one is moved into the public
     * folder, which a test must not write to.
     *
     * @param  \Closure(): UploadedFile  $file
     */
    #[DataProvider('invalidAvatars')]
    public function test_anh_dai_dien_sai_bi_tu_choi(\Closure $file, string $message): void
    {
        $this->actingAs($this->customer)
            ->put(route('thong-tin-tai-khoan.update', $this->customer->user_id), $this->profileForm(['img__new' => $file()]))
            ->assertSessionHasErrors(['img__new' => $message]);

        $this->assertNull($this->customer->fresh()->user_img);
    }

    // -------------------------------------------------------------- e-mail

    public function test_doi_email_thi_luu_email_moi_va_bo_trang_thai_da_xac_nhan(): void
    {
        $this->customer->email_verified_at = now();
        $this->customer->save();

        $this->actingAs($this->customer)
            ->from(route('thong-tin-tai-khoan.index'))
            ->post(route('user.change_email', $this->customer->user_id), ['email' => self::NEW_EMAIL])
            ->assertRedirect(route('thong-tin-tai-khoan.index'))
            ->assertSessionHas('message', 'Thay đổi email thành công !')
            ->assertSessionHas('iconMessage', 'success');

        $user = $this->customer->fresh();
        $this->assertSame(self::NEW_EMAIL, $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_doi_email_chi_ghi_vao_tai_khoan_dang_dang_nhap(): void
    {
        $this->actingAs($this->customer)
            ->post(route('user.change_email', $this->other->user_id), ['email' => self::NEW_EMAIL])
            ->assertSessionHasNoErrors();

        $this->assertSame(self::OTHER_EMAIL, $this->other->fresh()->email);
        $this->assertSame(self::NEW_EMAIL, $this->customer->fresh()->email);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidEmails(): array
    {
        return [
            'thiếu email' => ['', 'Bạn chưa nhập email'],
            'sai cú pháp' => ['khong-phai-email', 'Nhập email chưa đúng'],
            'không phải gmail' => ['khach@yahoo.com', 'Bạn nhập email sai định dạng'],
            'email của người khác' => [self::OTHER_EMAIL, 'Email này đã tồn tại'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_email_moi_sai_bi_tu_choi(string $email, string $message): void
    {
        $this->actingAs($this->customer)
            ->post(route('user.change_email', $this->customer->user_id), ['email' => $email])
            ->assertSessionHasErrors(['email' => $message]);

        $this->assertSame(self::EMAIL, $this->customer->fresh()->email);
    }

    // -------------------------------------------------------------- password

    public function test_yeu_cau_doi_mat_khau_gui_thu_xac_nhan_cho_chinh_khach(): void
    {
        $this->actingAs($this->customer)
            ->from(route('user.update_pass'))
            ->post(route('send_mail_pass'))
            ->assertRedirect(route('user.update_pass'))
            ->assertSessionHas('message', 'Đã gửi mail')
            ->assertSessionHas('iconMessage', 'success');

        Mail::assertQueued(sendMailPass::class, function (sendMailPass $mail) {
            return $mail->hasTo(self::EMAIL) && str_starts_with($mail->link, url('/thay-doi-mat-khau/'));
        });
        Mail::assertQueuedCount(1);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => self::EMAIL]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => self::OTHER_EMAIL]);
    }

    public function test_khach_chua_dang_nhap_khong_yeu_cau_duoc_thu_doi_mat_khau(): void
    {
        $this->post(route('send_mail_pass'))->assertRedirect(route('user.login'));

        Mail::assertNothingQueued();
    }

    public function test_thu_xac_nhan_doi_mat_khau_co_email_va_link(): void
    {
        $link = route('user.change_pass', 'token-mau');

        $mail = new sendMailPass($this->customer, $link);
        $mail->assertHasSubject('Thư xác nhận đổi mật khẩu');

        $html = $mail->render();
        $this->assertStringContainsString('Tài khoản của bạn đang được thay đổi mật khẩu', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString(self::EMAIL, $html);
        $this->assertStringContainsString($link, $html);
        $this->assertSame([], $mail->attachments());
    }

    public function test_link_doi_mat_khau_hop_le_mo_form_kem_token(): void
    {
        $token = $this->requestChangeLink();

        $this->actingAs($this->customer)
            ->get(route('user.change_pass', $token))
            ->assertOk()
            ->assertSee(route('user.update_pass_post'), false)
            ->assertSee('name="token" value="'.$token.'"', false)
            ->assertSee('name="same_password"', false);
    }

    public function test_link_doi_mat_khau_sai_token_hoac_cua_nguoi_khac_bi_404(): void
    {
        $token = $this->requestChangeLink();

        $this->actingAs($this->customer)->get(route('user.change_pass', 'token-khong-ton-tai'))->assertNotFound();
        $this->actingAs($this->other)->get(route('user.change_pass', $token))->assertNotFound();
    }

    public function test_link_doi_mat_khau_can_dang_nhap(): void
    {
        $token = $this->requestChangeLink();
        $this->app['auth']->forgetGuards();

        $this->get(route('user.change_pass', $token))->assertRedirect(route('user.login'));
    }

    public function test_doi_mat_khau_dung_token_luu_mat_khau_moi_va_link_het_dung_duoc(): void
    {
        $token = $this->requestChangeLink();

        $this->actingAs($this->customer)
            ->put(route('user.update_pass_post'), ['token' => $token, 'password' => 'MatKhauMoi@1', 'same_password' => 'MatKhauMoi@1'])
            ->assertRedirect(route('user.update_pass'))
            ->assertSessionHas('message', 'Thay đổi mật khẩu thành công !');

        $this->assertTrue(Hash::check('MatKhauMoi@1', $this->customer->fresh()->password));
        $this->actingAs($this->customer)->get(route('user.change_pass', $token))->assertNotFound();
    }

    public function test_doi_mat_khau_lam_mat_hieu_luc_cookie_ghi_nho_dang_nhap(): void
    {
        $this->customer->forceFill(['remember_token' => 'cookie-bi-lo'])->save();
        $token = $this->requestChangeLink();

        $this->actingAs($this->customer)
            ->put(route('user.update_pass_post'), ['token' => $token, 'password' => 'MatKhauMoi@1', 'same_password' => 'MatKhauMoi@1']);

        $this->assertNotSame('cookie-bi-lo', $this->customer->fresh()->remember_token);
    }

    public function test_doi_mat_khau_khong_co_token_hop_le_bi_tu_choi(): void
    {
        $this->requestChangeLink();

        foreach (['', 'token-khong-ton-tai'] as $token) {
            $this->actingAs($this->customer)
                ->put(route('user.update_pass_post'), ['token' => $token, 'password' => 'MatKhauMoi@1', 'same_password' => 'MatKhauMoi@1'])
                ->assertRedirect(route('user.update_pass'))
                ->assertSessionHas('message', 'Link đổi mật khẩu đã hết hạn, vui lòng gửi lại mail.');
        }

        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidNewPasswords(): array
    {
        return [
            'thiếu mật khẩu' => [['password' => '', 'same_password' => 'MatKhauMoi@1'], 'password', 'Vui lòng nhập mật khẩu'],
            'không có chữ hoa' => [['password' => 'matkhaumoi@1', 'same_password' => 'matkhaumoi@1'], 'password', 'Mật khẩu phải có chữ in hoa'],
            'nhập lại không khớp' => [['password' => 'MatKhauMoi@1', 'same_password' => 'MatKhauMoi@2'], 'password', 'Mật khẩu không khớp'],
            'thiếu nhập lại' => [['password' => 'MatKhauMoi@1', 'same_password' => ''], 'same_password', 'Bạn chưa nhập lại mật khẩu'],
        ];
    }

    /**
     * @param  array<string, string>  $form
     */
    #[DataProvider('invalidNewPasswords')]
    public function test_mat_khau_moi_sai_quy_tac_bi_tu_choi(array $form, string $field, string $message): void
    {
        $this->actingAs($this->customer)
            ->put(route('user.update_pass_post'), $form)
            ->assertSessionHasErrors([$field => $message]);

        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
    }
}
