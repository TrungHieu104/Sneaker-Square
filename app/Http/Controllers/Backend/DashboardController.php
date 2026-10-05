<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ExportStatistic;
use App\Http\Controllers\Controller;
use App\Models\StatisticModel;
use App\Models\UserModel;
use App\Models\VisitorModel;
use App\Services\DashboardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private const DATE_FORMAT = 'd/m/Y';

    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        // Visits by browser.
        $dataTopBrowsers = Analytics::fetchTopBrowsers(Period::days(7));
        // Visits by referring path.
        $dataTopReferrers = Analytics::fetchTopReferrers(Period::days(7), 15);
        // Visits by operating system.
        $dataSystems = Analytics::fetchTopOperatingSystems(Period::days(7));
        // chart account
        $sub7days = Carbon::now()->subDays(7)->toDateString();
        $now = Carbon::now()->toDateString();

        $datatAccountCount = [];
        $dailyDataAccount = UserModel::where('user_role', 0)->whereBetween(DB::raw('DATE(created_at)'), [$sub7days, $now])
            ->select(
                DB::raw('DATE(created_at) as date, COUNT(*) as data_count')
            )->groupBy('date')->orderBy('date', 'ASC')->get();

        $datatAccountCount['date'] = $dailyDataAccount->pluck('date')->map(function ($date) {
            return date(self::DATE_FORMAT, strtotime($date));
        });
        $datatAccountCount['data_count'] = $dailyDataAccount->pluck('data_count');

        View::share(compact('keyword', 'dataTopBrowsers', 'dataTopReferrers', 'dataSystems', 'datatAccountCount'));
    }

    /**
     * The dashboard.
     *
     * Every figure comes from DashboardStatisticsService now. This method used
     * to build them all itself: the rule for "an order that counts" appeared
     * three times written out in full, and the seven-day revenue query was
     * built twice in a row with the second assignment discarding the first.
     */
    public function index(Request $request, DashboardStatisticsService $stats)
    {
        $totalOrder = $stats->newOrderCount();
        $totalOrderToday = $stats->todayOrderCount();
        $revenue = $stats->allRevenueRows();
        $countVisitor = $stats->totalVisitorCount();
        $onlineVisitorCount = $stats->onlineVisitorCount();
        $dataTotal = $stats->totals();
        $dataOrder = $stats->ordersThisMonth();
        $dataTotalCoupon = $stats->coupons();
        $couponName = $dataTotalCoupon['couponPopular']?->coupon_code;
        $chart_data = $stats->revenueChart();

        // Visits, from Google Analytics rather than our own tables.
        $dataVisitor = [];
        $dataTotalVisitor = Analytics::fetchTotalVisitorsAndPageViews(Period::days(7))->sortBy('date');
        $dataVisitor['date'] = $dataTotalVisitor->pluck('date')->map(fn ($date) => $date->format(self::DATE_FORMAT));
        $dataVisitor['activeUsers'] = $dataTotalVisitor->pluck('activeUsers');
        $dataVisitor['screenPageViews'] = $dataTotalVisitor->pluck('screenPageViews');

        return view('backend.pages.dashboard', compact('revenue', 'totalOrder', 'countVisitor', 'chart_data',
            'totalOrderToday', 'dataTotal', 'dataVisitor', 'onlineVisitorCount', 'dataOrder', 'dataTotalCoupon',
            'couponName'));
    }

    public function indexPost(Request $request)
    {
        // Online users counted by IP address.
        $onlineVisitors = VisitorModel::where('visitor_date', '>=', now()->subMinutes(10))->get();
        $onlineVisitorCount = $onlineVisitors->count();
        if ($request->ajax()) {
            return response()->json(['visitorTotal' => $onlineVisitorCount]);
        }

        return response()->json(['error' => 'Không tìm thấy dữ liệu']);
    }

    public function filterVisitor(Request $request)
    {
        $dataFilter = $request->input('dataDate');
        if ($dataFilter) {
            $dataVisitor = [];
            $dataTotalVisitor = Analytics::fetchTotalVisitorsAndPageViews(Period::days($dataFilter))->sortBy('date');
            $dataVisitor['date'] = $dataTotalVisitor->pluck('date')->map(function ($date) {
                return $date->format(self::DATE_FORMAT);
            });
            $dataVisitor['activeUsers'] = $dataTotalVisitor->pluck('activeUsers');
            $dataVisitor['screenPageViews'] = $dataTotalVisitor->pluck('screenPageViews');

            return response()->json($dataVisitor);
        } else {
            return response()->json(['error' => 'Không tìm thấy dữ liệu bạn yêu cầu!']);
        }
    }

    public function support()
    {
        return view('backend.pages.support.support');
    }

    public function statistical()
    {
        return view('backend.pages.statistical.access');
    }

    public function dashboardFilter(Request $request)
    {
        $data = $request->all();
        $thismonth = Carbon::now()->startOfMonth()->toDateString();
        $start_month = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $end_month = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        $sub7days = Carbon::now()->subDays(7)->toDateString();
        $sub365days = Carbon::now()->subDays(365)->toDateString();

        $now = Carbon::now()->toDateString();

        if ($data['dashboard_value'] == '7ngay') {
            $get = StatisticModel::whereBetween('order_date', [$sub7days, $now])
                ->orderBy('order_date', 'ASC')->get();
        } elseif ($data['dashboard_value'] == 'thangtruoc') {
            $get = StatisticModel::whereBetween('order_date', [$start_month, $end_month])
                ->orderBy('order_date', 'ASC')->get();
        } elseif ($data['dashboard_value'] == 'thangnay') {
            $get = StatisticModel::whereBetween('order_date', [$thismonth, $now])
                ->orderBy('order_date', 'ASC')->get();
        } else {
            $get = StatisticModel::whereBetween('order_date', [$sub365days, $now])
                ->orderBy('order_date', 'ASC')->get();
        }

        $chart_data = [];
        foreach ($get as $data) {
            $chart_data[] = [
                'period' => date(self::DATE_FORMAT, strtotime($data->order_date)),
                'sales' => $data->sales,
                'profit' => $data->profit,
            ];
        }

        return response()->json($chart_data);
    }

    public function filterAccountUser(Request $request)
    {
        $data = $request->validate(['dateData' => ['required', 'in:7days,lmonth,tmonth']])['dateData'];

        $now = Carbon::now();
        [$from, $to] = match ($data) {
            '7days' => [$now->copy()->subDays(7), $now],
            'lmonth' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'tmonth' => [$now->copy()->startOfMonth(), $now],
        };

        $dataRes = UserModel::where('user_role', 0)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from->toDateString(), $to->toDateString()])
            ->select(DB::raw('DATE(created_at) as date, COUNT(*) as data_count'))
            ->groupBy('date')->orderBy('date', 'ASC')->get();

        return response()->json([
            'date' => $dataRes->pluck('date')->map(fn ($date) => date(self::DATE_FORMAT, strtotime($date))),
            'data_count' => $dataRes->pluck('data_count'),
        ]);
    }

    public function filterByDate(Request $request)
    {
        $data = $request->all();
        $from_date = $data['from_date'];
        $to_date = $data['to_date'];
        $from_date_converted = date('Y-m-d', strtotime($from_date));
        $to_date_converted = date('Y-m-d', strtotime($to_date));
        $get = StatisticModel::whereBetween('order_date', [$from_date_converted, $to_date_converted])
            ->orderBy('order_date', 'ASC')
            ->get();
        if ($get->isEmpty()) {
            return response()->json(['error' => 'Không tìm thấy dữ liệu bạn yêu cầu!']);
        }

        $chart_data = [];
        foreach ($get as $data) {
            $chart_data[] = [
                'period' => date(self::DATE_FORMAT, strtotime($data->order_date)),
                'sales' => $data->sales,
                'profit' => $data->profit,
            ];
        }

        return response()->json($chart_data);
    }

    public function export_scv()
    {
        return Excel::download(new ExportStatistic, 'Doanh thu.xlsx');
    }

    public function export_scv_day()
    {
        return Excel::download(ExportStatistic::today(), 'Doanh thu ngày.xlsx');
    }

    public function export_scv_week()
    {
        return Excel::download(ExportStatistic::lastSevenDays(), 'Doanh thu tuần.xlsx');
    }

    public function export_scv_month()
    {
        return Excel::download(ExportStatistic::thisMonth(), 'Doanh thu tháng.xlsx');
    }

    public function export_scv_monthprev()
    {
        return Excel::download(ExportStatistic::lastMonth(), 'Doanh thu tháng trước.xlsx');
    }

    public function export_scv_year()
    {
        return Excel::download(ExportStatistic::thisYear(), 'Doanh thu năm.xlsx');
    }

    public function sseNotifications(Request $request)
    {
        $response = new StreamedResponse(function () {
            // Detect if running on single-threaded php built-in server (cli-server)
            $isBuiltInServer = (php_sapi_name() === 'cli-server');
            $maxIterations = $isBuiltInServer ? 1 : 10;

            // Set retry interval for browser reconnection to 3 seconds
            echo "retry: 3000\n\n";

            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $data = app(DashboardStatisticsService::class)->notificationSnapshot();

                echo 'data: '.json_encode($data)."\n\n";
                ob_flush();
                flush();

                if ($maxIterations > 1) {
                    sleep(2);
                }
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    public function revenue(Request $request)
    {
        $perpage = 15;
        [$orderBy, $orderType] = $this->listingSort($request, StatisticModel::class, 'order_date', flip: false);

        $sortOption = $request->input('sort', 'default');
        $query = StatisticModel::orderBy($orderBy, $orderType);

        $thismonth = Carbon::now()->startOfMonth()->toDateString();
        $start_month = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $start_year = Carbon::now()->startOfYear()->toDateString();
        $end_month = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        $sub7days = Carbon::now()->subDays(7)->toDateString();
        $sub365days = Carbon::now()->subDays(365)->toDateString();

        $now = Carbon::now()->toDateString();

        switch ($sortOption) {
            case 'today':
                $query->where('order_date', $now)
                    ->orderBy('order_date', 'asc');
                break;
            case 'week':
                $query->whereBetween('order_date', [$sub7days, $now])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'month':
                $query->whereBetween('order_date', [$thismonth, $now])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'pmonth':
                $query->whereBetween('order_date', [$start_month, $end_month])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'year':
                $query->whereBetween('order_date', [$sub365days, $now])
                    ->orderBy('order_date', 'DESC');
                break;
            default:
                $query->orderBy('order_date', 'DESC');
                break;
        }
        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['order_date'];
        $query = $this->performSearch($query, $keyword, $searchableFields);

        $statistic = $query->paginate($perpage)->withQueryString();

        $revenueday = StatisticModel::where('order_date', $now)->get();
        $revenueweek = StatisticModel::whereBetween('order_date', [$sub7days, $now])->get();
        $revenuemonth = StatisticModel::whereBetween('order_date', [$thismonth, $now])->get();
        $revenuemonthprev = StatisticModel::whereBetween('order_date', [$start_month, $end_month])->get();
        $revenueyear = StatisticModel::whereBetween('order_date', [$start_year, $now])->get();
        $revenueall = StatisticModel::get();

        return view('backend.pages.statistical.revenue', compact('revenueall', 'revenueyear', 'revenuemonthprev', 'revenuemonth', 'revenueday', 'revenueweek', 'statistic', 'orderBy', 'orderType'));
    }
}
