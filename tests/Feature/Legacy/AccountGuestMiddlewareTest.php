<?php

namespace Tests\Feature\Legacy;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the `guest` middleware (RedirectIfAuthenticated). No shop
 * route uses it today, so the test mounts its own throwaway routes.
 */
class AccountGuestMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const DEFAULT_GUARD_URL = '/_kiem-thu/chi-cho-khach';

    private const NAMED_GUARD_URL = '/_kiem-thu/chi-cho-khach-web';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'guest'])->get(self::DEFAULT_GUARD_URL, fn () => 'trang-cho-khach');
        Route::middleware(['web', 'guest:web'])->get(self::NAMED_GUARD_URL, fn () => 'trang-cho-khach');
    }

    public function test_khach_chua_dang_nhap_di_qua_binh_thuong(): void
    {
        $this->get(self::DEFAULT_GUARD_URL)->assertOk()->assertSee('trang-cho-khach');
        $this->get(self::NAMED_GUARD_URL)->assertOk()->assertSee('trang-cho-khach');
    }

    public function test_da_dang_nhap_thi_bi_chuyen_ve_trang_chu_cua_ung_dung(): void
    {
        $customer = $this->makeUser();

        $this->actingAs($customer)->get(self::DEFAULT_GUARD_URL)->assertRedirect(RouteServiceProvider::HOME);
        $this->actingAs($customer)->get(self::NAMED_GUARD_URL)->assertRedirect(RouteServiceProvider::HOME);
    }
}
