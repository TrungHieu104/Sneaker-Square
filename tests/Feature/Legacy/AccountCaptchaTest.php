<?php

namespace Tests\Feature\Legacy;

use App\Rules\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the reCAPTCHA rule. The google/recaptcha library posts with
 * file_get_contents() rather than Laravel's HTTP client, so Http::fake() cannot
 * stop it: the https stream wrapper is swapped for a fake that answers in
 * Google's place and records what was sent. Nothing leaves the machine.
 */
class AccountCaptchaTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const MESSAGE = 'Vui lòng xác nhận bạn không phải là người máy';

    private const SECRET = 'bi-mat-captcha-trong-test';

    /** @var class-string|null */
    private ?string $google = null;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Http::preventStrayRequests();
        config(['services.captcha.secret' => self::SECRET]);
    }

    protected function tearDown(): void
    {
        if ($this->google !== null) {
            stream_wrapper_restore('https');
            $this->google = null;
        }

        parent::tearDown();
    }

    /**
     * Replaces the https wrapper with one that answers every request with the
     * given siteverify verdict.
     *
     * @return class-string
     */
    private function fakeGoogle(bool $success): string
    {
        $wrapper = new class
        {
            /** @var resource|null */
            public $context;

            public static string $answer = '';

            /** @var list<array{url: string, body: string}> */
            public static array $requests = [];

            private int $position = 0;

            public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
            {
                $options = stream_context_get_options($this->context);
                self::$requests[] = ['url' => $path, 'body' => (string) ($options['http']['content'] ?? '')];

                return true;
            }

            public function stream_read(int $count): string
            {
                $chunk = substr(self::$answer, $this->position, $count);
                $this->position += strlen($chunk);

                return $chunk;
            }

            public function stream_eof(): bool
            {
                return $this->position >= strlen(self::$answer);
            }

            /** @return array<int, int> */
            public function stream_stat(): array
            {
                return [];
            }
        };

        $class = $wrapper::class;
        $class::$answer = json_encode(['success' => $success, 'error-codes' => $success ? [] : ['invalid-input-response']]);
        $class::$requests = [];

        stream_wrapper_unregister('https');
        stream_wrapper_register('https', $class);
        $this->google = $class;

        return $class;
    }

    /**
     * @return list<array{url: string, body: string}>
     */
    private function requestsToGoogle(): array
    {
        return $this->google === null ? [] : $this->google::$requests;
    }

    private function passes(mixed $token): bool
    {
        return Validator::make(['g-recaptcha-response' => $token], ['g-recaptcha-response' => [new Captcha]])->passes();
    }

    public function test_google_xac_nhan_thi_qua(): void
    {
        $this->fakeGoogle(success: true);

        $this->assertTrue($this->passes('token-tu-widget'));
    }

    public function test_gui_dung_secret_trong_config_va_token_cho_google(): void
    {
        $this->fakeGoogle(success: true);

        $this->passes('token-tu-widget');

        $requests = $this->requestsToGoogle();
        $this->assertCount(1, $requests);
        $this->assertSame('https://www.google.com/recaptcha/api/siteverify', $requests[0]['url']);
        parse_str($requests[0]['body'], $sent);
        $this->assertSame(self::SECRET, $sent['secret']);
        $this->assertSame('token-tu-widget', $sent['response']);
        $this->assertSame('127.0.0.1', $sent['remoteip']);
    }

    public function test_google_tu_choi_thi_bao_loi_tieng_viet(): void
    {
        $this->fakeGoogle(success: false);

        $validator = Validator::make(['g-recaptcha-response' => 'token-gia'], ['g-recaptcha-response' => [new Captcha]]);

        $this->assertTrue($validator->fails());
        $this->assertSame(self::MESSAGE, $validator->errors()->first('g-recaptcha-response'));
    }

    public function test_token_rong_bi_tu_choi_ma_khong_goi_google(): void
    {
        // "0" is a non-empty string to the validator but empty() to the library,
        // which then answers "missing input" on its own.
        $this->fakeGoogle(success: true);

        $this->assertFalse($this->passes('0'));
        $this->assertSame([], $this->requestsToGoogle());
    }

    public function test_dang_nhap_sau_nhieu_lan_sai_qua_duoc_khi_captcha_hop_le(): void
    {
        $customer = $this->makeUser(email: 'khach@gmail.com');
        $customer->password = 'MatKhau@123';
        $customer->save();
        $this->fakeGoogle(success: true);

        $this->withSession(['login_attempts' => 4])
            ->post(route('user.login_post'), [
                'email' => 'khach@gmail.com',
                'password' => 'MatKhau@123',
                'g-recaptcha-response' => 'token-tu-widget',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home.page'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_dang_nhap_bi_chan_khi_google_tu_choi_captcha(): void
    {
        $customer = $this->makeUser(email: 'khach@gmail.com');
        $customer->password = 'MatKhau@123';
        $customer->save();
        $this->fakeGoogle(success: false);

        $this->withSession(['login_attempts' => 4])
            ->post(route('user.login_post'), [
                'email' => 'khach@gmail.com',
                'password' => 'MatKhau@123',
                'g-recaptcha-response' => 'token-gia',
            ])
            ->assertSessionHasErrors(['g-recaptcha-response' => self::MESSAGE]);

        $this->assertGuest();
    }
}
