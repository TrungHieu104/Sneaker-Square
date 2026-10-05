<?php

namespace Tests\Feature;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Signing in through Google or Facebook: a first visit makes the account, a
 * later one finds it, and an email already used another way is turned away.
 */
class SocialLoginTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private function provider(string $driver, string $id, string $email, string $name = 'Trần Văn B'): void
    {
        $user = (new SocialUser)->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://example.test/a.png']);

        $socialite = Mockery::mock(AbstractProvider::class);
        $socialite->shouldReceive('stateless')->andReturnSelf();
        $socialite->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with($driver)->andReturn($socialite);
    }

    public function test_lan_dau_dang_nhap_google_thi_tao_tai_khoan(): void
    {
        $this->provider('google', 'g-123', 'moi@example.test');

        $this->get('/auth/google/callback')->assertRedirect(route('home.page'));

        $user = UserModel::where('email', 'moi@example.test')->firstOrFail();
        $this->assertSame('g-123', $user->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_lan_sau_dang_nhap_facebook_thi_vao_dung_tai_khoan_cu(): void
    {
        $existing = $this->makeUser(email: 'cu@example.test');
        $existing->facebook_id = 'f-9';
        $existing->save();
        $this->provider('facebook', 'f-9', 'cu@example.test', 'Tên Mới');

        $this->get('/auth/facebook/callback')->assertRedirect(route('home.page'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('Tên Mới', $existing->fresh()->name);
        $this->assertSame(1, UserModel::count());
    }

    public function test_email_da_dang_ky_cach_khac_thi_khong_cho_dang_nhap(): void
    {
        $this->makeUser(email: 'trung@example.test');
        $this->provider('google', 'g-555', 'trung@example.test');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('user.login'))
            ->assertSessionHas('message', 'Tài khoản Google đã tồn tại');

        $this->assertGuest();
    }

    public function test_nha_cung_cap_loi_thi_bao_khong_dang_nhap_duoc(): void
    {
        Socialite::shouldReceive('driver')->with('facebook')->andThrow(new \RuntimeException('boom'));

        $this->get('/auth/facebook/callback')
            ->assertRedirect(route('user.login'))
            ->assertSessionHas('message', 'Không đăng nhập được bằng Facebook, vui lòng thử lại.');
    }
}
