<?php

namespace Tests\Feature\Legacy;

use App\Models\StatisticModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Analytics\Analytics as AnalyticsService;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the admin dashboard and the charts, filters and exports around
 * it. Google Analytics is mocked in every test: the controller's constructor
 * asks it for three reports on every request, before any middleware runs.
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const NO_DATA = 'Không tìm thấy dữ liệu bạn yêu cầu!';

    private UserModel $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));
        Http::fake();
        Http::preventStrayRequests();
        $this->mockAnalytics();

        $this->staff = $this->makeUser('quantri@example.test', 'quantri', role: 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Swapping a bare mock in first keeps shouldReceive() from resolving the
     * real client, which needs a property id and a credentials file.
     */
    private function mockAnalytics(): void
    {
        Analytics::swap(Mockery::mock(AnalyticsService::class));

        Analytics::shouldReceive('fetchTopBrowsers')->andReturn(collect([
            ['browser' => 'Chrome', 'screenPageViews' => 120],
            ['browser' => 'Safari', 'screenPageViews' => 45],
        ]));
        Analytics::shouldReceive('fetchTopReferrers')->andReturn(collect([
            ['pageReferrer' => 'https://www.google.com/', 'screenPageViews' => 80],
        ]));
        Analytics::shouldReceive('fetchTopOperatingSystems')->andReturn(collect([
            ['operatingSystem' => 'Windows', 'screenPageViews' => 90],
            ['operatingSystem' => 'iOS', 'screenPageViews' => 30],
        ]));
        Analytics::shouldReceive('fetchTotalVisitorsAndPageViews')->andReturn(collect([
            ['date' => Carbon::parse('2026-11-17'), 'activeUsers' => 7, 'screenPageViews' => 21],
            ['date' => Carbon::parse('2026-11-15'), 'activeUsers' => 3, 'screenPageViews' => 9],
        ]));
    }

    private function grant(string $permission): void
    {
        $this->staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    private function revenueRow(string $date, int $sales, int $profit = 0): StatisticModel
    {
        return StatisticModel::create(['order_date' => $date, 'sales' => $sales, 'profit' => $profit, 'order_total' => 1]);
    }

    /**
     * Today, earlier this month, last month, earlier this year, and more than a
     * year ago, so each period picks a different subset.
     */
    private function seedRevenue(): void
    {
        $this->revenueRow('2026-11-18', 1_000_000, 300_000);
        $this->revenueRow('2026-11-05', 2_000_000, 600_000);
        $this->revenueRow('2026-10-20', 3_000_000, 900_000);
        $this->revenueRow('2026-03-01', 4_000_000, 1_200_000);
        $this->revenueRow('2025-06-01', 5_000_000, 1_500_000);
    }

    private function customerJoinedOn(string $email, string $date): void
    {
        $customer = $this->makeUser($email, explode('@', $email)[0]);
        $customer->created_at = Carbon::parse($date.' 09:00:00');
        $customer->save();
    }

    /**
     * @return array<int, string>
     */
    private function periods(string $route, array $data): array
    {
        return collect($this->actingAs($this->staff)->post(route($route), $data)->assertOk()->json())
            ->pluck('period')->all();
    }

    // -------------------------------------------------------------- dashboard

    public function test_dashboard_hien_luot_truy_cap_google_analytics_theo_ngay_tang_dan(): void
    {
        $response = $this->actingAs($this->staff)->get(route('admin.dashboard'))->assertOk();

        $visitor = $response->viewData('dataVisitor');
        $this->assertSame(['15/11/2026', '17/11/2026'], $visitor['date']->all());
        $this->assertSame([3, 7], $visitor['activeUsers']->all());
        $this->assertSame([9, 21], $visitor['screenPageViews']->all());
    }

    public function test_dashboard_dem_khach_moi_dang_ky_trong_7_ngay_theo_ngay(): void
    {
        $this->customerJoinedOn('mot@example.test', '2026-11-16');
        $this->customerJoinedOn('hai@example.test', '2026-11-16');
        $this->customerJoinedOn('ba@example.test', '2026-11-18');
        $this->customerJoinedOn('cu@example.test', '2026-11-01');

        $accounts = $this->actingAs($this->staff)->get(route('admin.dashboard'))->assertOk()->viewData('datatAccountCount');

        // Staff accounts are not customers and are left out.
        $this->assertSame(['16/11/2026', '18/11/2026'], $accounts['date']->all());
        $this->assertSame([2, 1], $accounts['data_count']->map(fn ($count) => (int) $count)->all());
    }

    public function test_ajax_hoi_so_nguoi_dang_online_tra_json(): void
    {
        $this->actingAs($this->staff)->post(route('admin.dashboard.post'), [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['visitorTotal'])
            ->assertJsonMissingPath('error');
    }

    public function test_hoi_so_nguoi_online_khong_qua_ajax_tra_loi(): void
    {
        $this->actingAs($this->staff)->post(route('admin.dashboard.post'))
            ->assertOk()
            ->assertExactJson(['error' => 'Không tìm thấy dữ liệu']);
    }

    // -------------------------------------------------------------- filters

    public function test_loc_luot_truy_cap_theo_so_ngay_tra_bieu_do(): void
    {
        $this->actingAs($this->staff)->get(route('dashboard.filter.visitor', ['dataDate' => 30]))
            ->assertOk()
            ->assertExactJson([
                'date' => ['15/11/2026', '17/11/2026'],
                'activeUsers' => [3, 7],
                'screenPageViews' => [9, 21],
            ]);
    }

    public function test_loc_luot_truy_cap_khong_chon_so_ngay_bao_khong_co_du_lieu(): void
    {
        $this->actingAs($this->staff)->get(route('dashboard.filter.visitor'))
            ->assertOk()
            ->assertExactJson(['error' => self::NO_DATA]);
    }

    public function test_loc_doanh_thu_dashboard_theo_tung_moc_thoi_gian(): void
    {
        $this->seedRevenue();

        $this->assertSame(['18/11/2026'], $this->periods('dashboardFilter', ['dashboard_value' => '7ngay']));
        $this->assertSame(['20/10/2026'], $this->periods('dashboardFilter', ['dashboard_value' => 'thangtruoc']));
        $this->assertSame(['05/11/2026', '18/11/2026'], $this->periods('dashboardFilter', ['dashboard_value' => 'thangnay']));
        $this->assertSame(
            ['01/03/2026', '20/10/2026', '05/11/2026', '18/11/2026'],
            $this->periods('dashboardFilter', ['dashboard_value' => '365ngayqua']),
        );
    }

    public function test_loc_doanh_thu_tra_doanh_so_va_loi_nhuan_tung_ngay(): void
    {
        $this->seedRevenue();

        $this->actingAs($this->staff)->post(route('dashboardFilter'), ['dashboard_value' => '7ngay'])
            ->assertExactJson([['period' => '18/11/2026', 'sales' => 1_000_000, 'profit' => 300_000]]);
    }

    public function test_loc_doanh_thu_theo_khoang_ngay_tu_chon(): void
    {
        $this->seedRevenue();

        $this->assertSame(
            ['20/10/2026', '05/11/2026'],
            $this->periods('filterByDay', ['from_date' => '2026-10-01', 'to_date' => '2026-11-10']),
        );
    }

    public function test_loc_doanh_thu_theo_khoang_ngay_khong_co_don_bao_khong_co_du_lieu(): void
    {
        $this->seedRevenue();

        $this->actingAs($this->staff)->post(route('filterByDay'), ['from_date' => '2024-01-01', 'to_date' => '2024-12-31'])
            ->assertOk()
            ->assertExactJson(['error' => self::NO_DATA]);
    }

    public function test_loc_khach_dang_ky_theo_7_ngay_thang_truoc_thang_nay(): void
    {
        $this->customerJoinedOn('mot@example.test', '2026-11-16');
        $this->customerJoinedOn('hai@example.test', '2026-11-03');
        $this->customerJoinedOn('ba@example.test', '2026-10-12');
        $this->customerJoinedOn('bon@example.test', '2026-10-12');

        $this->actingAs($this->staff)->postJson(route('dashboard.filter.account'), ['dateData' => '7days'])
            ->assertOk()->assertJsonPath('date', ['16/11/2026']);
        $this->actingAs($this->staff)->postJson(route('dashboard.filter.account'), ['dateData' => 'tmonth'])
            ->assertOk()->assertJsonPath('date', ['03/11/2026', '16/11/2026']);

        $lastMonth = $this->actingAs($this->staff)->postJson(route('dashboard.filter.account'), ['dateData' => 'lmonth'])->assertOk();
        $this->assertSame(['12/10/2026'], $lastMonth->json('date'));
        $this->assertSame([2], array_map('intval', $lastMonth->json('data_count')));
    }

    public function test_loc_khach_dang_ky_voi_moc_khong_hop_le_bi_tu_choi(): void
    {
        $this->actingAs($this->staff)->postJson(route('dashboard.filter.account'), ['dateData' => 'nam'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dateData');
        $this->actingAs($this->staff)->postJson(route('dashboard.filter.account'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dateData');
    }

    // -------------------------------------------------------------- exports

    public function test_xuat_excel_doanh_thu_theo_tung_moc(): void
    {
        Excel::fake();
        $this->grant('Thống kê doanh thu');

        $files = [
            'export.scv' => 'Doanh thu.xlsx',
            'export.scvday' => 'Doanh thu ngày.xlsx',
            'export.scvweek' => 'Doanh thu tuần.xlsx',
            'export.scvmonth' => 'Doanh thu tháng.xlsx',
            'export.scvmonthprev' => 'Doanh thu tháng trước.xlsx',
            'export.scvyear' => 'Doanh thu năm.xlsx',
        ];

        foreach ($files as $route => $file) {
            $this->actingAs($this->staff)->post(route($route))->assertOk();
            Excel::assertDownloaded($file);
        }
    }

    public function test_xuat_excel_doanh_thu_can_quyen_thong_ke_doanh_thu(): void
    {
        Excel::fake();
        Permission::findOrCreate('Thống kê doanh thu', 'web');

        $this->actingAs($this->staff)->post(route('export.scv'))->assertForbidden();
    }

    public function test_khach_chua_dang_nhap_khong_goi_google_analytics(): void
    {
        Analytics::swap(Mockery::mock(AnalyticsService::class));
        Analytics::shouldReceive('fetchTopBrowsers', 'fetchTopReferrers', 'fetchTopOperatingSystems', 'fetchTotalVisitorsAndPageViews')->never();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.statistical'))->assertRedirect(route('admin.login'));
    }

    // -------------------------------------------------------------- other pages

    public function test_trang_ho_tro_mo_duoc_cho_nhan_vien(): void
    {
        $this->actingAs($this->staff)->get(route('support'))->assertOk();
    }

    public function test_thong_ke_truy_cap_can_quyen_thong_ke_truy_cap(): void
    {
        Permission::findOrCreate('Thống kê truy cập', 'web');

        $this->actingAs($this->staff)->get(route('admin.statistical'))->assertForbidden();
    }

    public function test_thong_ke_truy_cap_hien_trinh_duyet_he_dieu_hanh_va_nguon_gioi_thieu(): void
    {
        $this->grant('Thống kê truy cập');

        $this->actingAs($this->staff)->get(route('admin.statistical'))
            ->assertOk()
            ->assertSee('Chrome')
            ->assertSee('Safari')
            ->assertSee('Windows')
            ->assertSee('iOS')
            ->assertSee('https://www.google.com/');
    }

    public function test_trang_doanh_thu_can_quyen_thong_ke_doanh_thu(): void
    {
        Permission::findOrCreate('Thống kê doanh thu', 'web');

        $this->actingAs($this->staff)->get(route('admin.revenue'))->assertForbidden();
    }

    public function test_trang_doanh_thu_loc_theo_moc_thoi_gian(): void
    {
        $this->grant('Thống kê doanh thu');
        $this->seedRevenue();

        $dates = fn (string $sort) => $this->actingAs($this->staff)->get(route('admin.revenue', ['sort' => $sort]))
            ->assertOk()->viewData('statistic')->pluck('order_date')->map(fn ($date) => substr((string) $date, 0, 10))->sort()->values()->all();

        $this->assertSame(['2026-11-18'], $dates('today'));
        $this->assertSame(['2026-11-18'], $dates('week'));
        $this->assertSame(['2026-11-05', '2026-11-18'], $dates('month'));
        $this->assertSame(['2026-10-20'], $dates('pmonth'));
        $this->assertSame(['2026-03-01', '2026-10-20', '2026-11-05', '2026-11-18'], $dates('year'));
        $this->assertCount(5, $dates('default'));
    }

    public function test_trang_doanh_thu_tong_hop_theo_ngay_tuan_thang_nam(): void
    {
        $this->grant('Thống kê doanh thu');
        $this->seedRevenue();

        $response = $this->actingAs($this->staff)->get(route('admin.revenue'))->assertOk();

        $this->assertSame(1_000_000, (int) $response->viewData('revenueday')->sum('sales'));
        $this->assertSame(1_000_000, (int) $response->viewData('revenueweek')->sum('sales'));
        $this->assertSame(3_000_000, (int) $response->viewData('revenuemonth')->sum('sales'));
        $this->assertSame(3_000_000, (int) $response->viewData('revenuemonthprev')->sum('sales'));
        $this->assertSame(10_000_000, (int) $response->viewData('revenueyear')->sum('sales'));
        $this->assertSame(15_000_000, (int) $response->viewData('revenueall')->sum('sales'));
    }

    public function test_trang_doanh_thu_tim_theo_ngay(): void
    {
        $this->grant('Thống kê doanh thu');
        $this->seedRevenue();

        $found = $this->actingAs($this->staff)->get(route('admin.revenue', ['keyword' => '2026-10']))
            ->assertOk()->viewData('statistic');

        $this->assertCount(1, $found);
        $this->assertSame(3_000_000, (int) $found->first()->sales);
    }
}
