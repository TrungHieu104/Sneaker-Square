<?php

namespace Tests\Feature\Legacy;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use Spatie\Analytics\Analytics as AnalyticsService;
use Spatie\Analytics\Facades\Analytics;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises AuthAdminMiddleware on the dashboard: only an active, unlocked
 * staff account gets through, everyone else is sent to the admin login.
 */
class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const DENIED = 'Không được phép truy cập';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));
        Http::fake();
        Http::preventStrayRequests();

        // DashboardController asks Google Analytics for reports in its
        // constructor, which Laravel builds before the route middleware runs.
        Analytics::swap(Mockery::mock(AnalyticsService::class));
        foreach (['fetchTopBrowsers', 'fetchTopReferrers', 'fetchTopOperatingSystems', 'fetchTotalVisitorsAndPageViews'] as $report) {
            Analytics::shouldReceive($report)->andReturn(collect());
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function staff(): UserModel
    {
        return $this->makeUser('quantri@example.test', 'quantri', role: 1);
    }

    private function assertTurnedAway(string $message = self::DENIED): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', $message)
            ->assertSessionHas('iconMessage', 'error');
    }

    public function test_khach_chua_dang_nhap_bi_dua_ve_trang_dang_nhap_admin(): void
    {
        $this->assertTurnedAway();
    }

    public function test_tai_khoan_khach_hang_khong_vao_duoc_admin(): void
    {
        $this->actingAs($this->makeUser());

        $this->assertTurnedAway();
    }

    public function test_nhan_vien_bi_vo_hieu_hoa_khong_vao_duoc_admin(): void
    {
        $staff = $this->staff();
        $staff->user_status = 0;
        $staff->save();

        $this->actingAs($staff);

        $this->assertTurnedAway();
    }

    public function test_nhan_vien_dang_bi_khoa_thi_bao_thoi_gian_con_lai(): void
    {
        $staff = $this->staff();
        $staff->locked_at = now()->addMinutes(3)->addSeconds(30);
        $staff->save();

        $this->actingAs($staff);

        $this->assertTurnedAway('Vui lòng thử lại sau 3 phút 30 giây!');
    }

    public function test_nhan_vien_het_thoi_gian_khoa_thi_vao_duoc_dashboard(): void
    {
        $staff = $this->staff();
        $staff->locked_at = now()->subMinute();
        $staff->save();

        $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_nhan_vien_dang_hoat_dong_vao_duoc_dashboard(): void
    {
        $this->actingAs($this->staff())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('backend.pages.dashboard');
    }
}
