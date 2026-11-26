<?php

namespace Tests\Feature;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Spatie\Analytics\Analytics as AnalyticsService;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin pages that show Google Analytics figures, when Google cannot be
 * reached. The rest of the dashboard comes from our own tables and must still
 * open.
 */
class DashboardWithoutGoogleTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const NOTICE = 'Chưa lấy được số liệu từ Google Analytics';

    private UserModel $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));
        Http::fake();
        Http::preventStrayRequests();

        $this->staff = $this->makeUser('quantri@example.test', 'quantri', role: 1);
        // The dashboard draws its cards only for a staff role.
        $this->staff->assignRole(Role::findOrCreate('Quản trị viên', 'web'));
        $this->staff->givePermissionTo(Permission::findOrCreate('Thống kê truy cập', 'web'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The server as first deployed: a property id but no key file.
     */
    private function withoutKeyFile(): void
    {
        config([
            'analytics.property_id' => '123456789',
            'analytics.service_account_credentials_json' => storage_path('app/google/khong-co-file-nay.json'),
        ]);
    }

    public function test_thieu_file_key_google_thi_dashboard_van_mo_va_bao_thieu_so_lieu(): void
    {
        $this->withoutKeyFile();

        $this->actingAs($this->staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(self::NOTICE)
            ->assertViewHas('dataVisitor', fn (array $visits) => $visits['date']->isEmpty());
    }

    public function test_thieu_file_key_google_thi_trang_thong_ke_truy_cap_van_mo(): void
    {
        $this->withoutKeyFile();

        $this->actingAs($this->staff)->get(route('admin.statistical'))
            ->assertOk()
            ->assertSee(self::NOTICE);
    }

    public function test_thieu_file_key_google_thi_loc_luot_truy_cap_tra_bieu_do_rong(): void
    {
        $this->withoutKeyFile();

        $this->actingAs($this->staff)->get(route('dashboard.filter.visitor', ['dataDate' => 14]))
            ->assertOk()
            ->assertExactJson(['date' => [], 'activeUsers' => [], 'screenPageViews' => [], 'unavailable' => true]);
    }

    public function test_google_loi_lan_dau_thi_khong_hoi_tiep_trong_cung_luot_tai(): void
    {
        Analytics::swap(Mockery::mock(AnalyticsService::class));
        Analytics::shouldReceive('fetchTopBrowsers')->once()->andThrow(new RuntimeException('deadline exceeded'));
        Analytics::shouldReceive('fetchTopReferrers', 'fetchTopOperatingSystems', 'fetchTotalVisitorsAndPageViews')->never();
        Log::shouldReceive('warning')->once()->with('Google Analytics unavailable', Mockery::type('array'));

        $this->actingAs($this->staff)->get(route('admin.dashboard'))->assertOk()->assertSee(self::NOTICE);
    }

    public function test_google_tra_so_lieu_binh_thuong_thi_khong_hien_canh_bao(): void
    {
        Analytics::swap(Mockery::mock(AnalyticsService::class));
        Analytics::shouldReceive('fetchTopBrowsers', 'fetchTopReferrers', 'fetchTopOperatingSystems')->andReturn(collect());
        Analytics::shouldReceive('fetchTotalVisitorsAndPageViews')->andReturn(collect([
            ['date' => Carbon::parse('2026-11-17'), 'activeUsers' => 7, 'screenPageViews' => 21],
        ]));

        $this->actingAs($this->staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(self::NOTICE);
    }
}
