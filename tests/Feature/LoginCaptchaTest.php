<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Covers the reCAPTCHA gate on the login form.
 *
 * `'g-recaptcha-response' => new Captcha()` looks like a check but was not one.
 * Laravel skips a rule object when the field is absent, so a client that simply
 * did not send the field passed every time — including after the widget had
 * appeared, which is precisely when the shop wants the check.
 *
 * No test here sends a token, so none of them calls Google.
 */
class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const CREDENTIALS = [
        'email' => 'khong-ton-tai@gmail.com',
        'password' => 'SaiMatKhau123!',
    ];

    public function test_lan_dang_nhap_dau_khong_can_captcha(): void
    {
        // The widget is not on the page yet, so nothing should demand a token.
        $this->post(route('user.login_post'), self::CREDENTIALS)
            ->assertSessionHasNoErrors();
    }

    public function test_sau_nhieu_lan_that_bai_thi_bat_buoc_co_captcha(): void
    {
        $this->withSession(['login_attempts' => 4])
            ->post(route('user.login_post'), self::CREDENTIALS)
            ->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_khong_the_bo_qua_captcha_bang_cach_khong_gui_field(): void
    {
        // The bypass itself: the field left out entirely.
        $this->withSession(['login_attempts' => 10])
            ->post(route('user.login_post'), self::CREDENTIALS)
            ->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_nguong_hien_captcha_lay_tu_config(): void
    {
        config(['services.captcha.after_failed_attempts' => 1]);

        $this->withSession(['login_attempts' => 2])
            ->post(route('user.login_post'), self::CREDENTIALS)
            ->assertSessionHasErrors('g-recaptcha-response');
    }
}
