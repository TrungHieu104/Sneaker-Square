<?php

namespace App\Http\Controllers\Backend;

use Exception;
use App\Models\NewsModel;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\CouponModel;
use App\Models\ContactFormModel;
use App\Models\ProductModel;
use App\Models\PromotionModel;
use App\Models\VisitorModel;
use Illuminate\Http\Request;
use Spatie\Analytics\Period;
use App\Models\StatisticModel;
use Illuminate\Support\Carbon;
use App\Exports\ExportStatistic;
use App\Exports\ExportStatisticYear;
use App\Exports\ExportStatisticDay;
use App\Exports\ExportStatisticWeek;
use App\Exports\ExportStatisticMonth;
use App\Exports\ExportStatisticMonthPrev;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Analytics\Facades\Analytics;

class DashboardController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        //truy cập theo trình duyệt
        $dataTopBrowsers = Analytics::fetchTopBrowsers(Period::days(7));
        //truy cập theo đường đẫn
        $dataTopReferrers= Analytics::fetchTopReferrers(Period::days(7), 15);
        //truy cập theo hệ điều hành
        $dataSystems= Analytics::fetchTopOperatingSystems(Period::days(7));
        //chart account
        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $now = Carbon::now('Asia/Ho_Chi_minh')->toDateString();
        
        $datatAccountCount = [];
        $dailyDataAccount = UserModel::where('user_role', 0)->whereBetween(DB::raw('DATE(created_at)'), [$sub7days, $now])
        ->orderBy('created_at','ASC')->select(
            DB::raw('DATE(created_at) as date, COUNT(*) as data_count')
        )->groupBy('date')->orderBy('date')->get();

        $datatAccountCount['date'] = $dailyDataAccount->pluck('date')->map(function ($date) {
            return date('d/m/Y', strtotime($date));
        });
        $datatAccountCount['data_count'] = $dailyDataAccount->pluck('data_count');

        View::share(compact('keyword', 'dataTopBrowsers', 'dataTopReferrers', 'dataSystems', 'datatAccountCount'));
    }

    public function index(Request $request)
    {
        $totalOrder = OrderModel::where('order_status', 0)
        ->where(function ($query) {
            $query->where('order_payment', 'cod')
                ->where('order_payment_status', 0)
                ->orWhere(function ($query) {
                    $query->whereIn('order_payment', ['payUrl', 'redirect'])
                        ->where('order_payment_status', 1);
                });
            })->count();
        $today = Carbon::today();
        $totalOrderToday = OrderModel::where(function ($query) {
            $query->where('order_payment', 'cod')
                ->where('order_payment_status', 0)
                ->orWhere(function ($query) {
                    $query->whereIn('order_payment', ['payUrl', 'redirect'])
                        ->where('order_payment_status', 1);
                });
            })
            ->where('order_date',$today)->count();
        $get = StatisticModel::orderBy('order_date', 'ASC')->get();
        $revenue = StatisticModel::get();
        $countVisitor = VisitorModel::all()->count();

        //thống kê người dùng online theo ip
        $onlineVisitors = VisitorModel::where('visitor_date', '>=', now()->subMinutes(10))->get();
        $onlineVisitorCount = $onlineVisitors->count();

        //thống kê truy cập
        $dataVisitor = []; 
        $dataTotalVisitor = Analytics::fetchTotalVisitorsAndPageViews(Period::days(7))->sortBy('date');
        $dataVisitor['date'] = $dataTotalVisitor->pluck('date')->map(function ($date){
            return $date->format('d/m/Y');
        });
        $dataVisitor['activeUsers'] = $dataTotalVisitor->pluck('activeUsers');
        $dataVisitor['screenPageViews'] = $dataTotalVisitor->pluck('screenPageViews');

        //số lượng dữ liệu theo mục
        $dataTotal = [];
        $dataTotal['totalNews'] = NewsModel::all()->count();
        $dataTotal['totalPro'] = ProductModel::all()->count();
        $dataTotal['totalAccountUser'] = UserModel::where('user_role', 0)->get()->count();
        $dataTotal['totalAccountAdmin'] = UserModel::where('user_role', 1)->get()->count();
        $dataTotal['totalOrder'] = OrderModel::where(function ($query) {
            $query->where('order_payment', 'cod')
                ->where('order_payment_status', 0)
                ->orWhere(function ($query) {
                    $query->whereIn('order_payment', ['payUrl', 'redirect'])
                        ->where('order_payment_status', 1);
                });
            })
        ->count();
        $dataTotal['totalCoupon'] = CouponModel::get()->count();

        // Thống kê mã giảm
        $dataTotalCoupon = [];
        $dataTotalCoupon['totalCoupon'] = CouponModel::get()->count();
        $dataTotalCoupon['stillValid'] = CouponModel::where('coupon_end','>=',$today)->count();
        $dataTotalCoupon['expiredValid'] = CouponModel::where('coupon_end','<',$today)->count();
        $dataTotalCoupon['couponPopular'] = CouponModel::orderBy('coupon_used', 'desc')->limit(1)->first();

        if ($dataTotalCoupon['couponPopular']) {
            $couponName = $dataTotalCoupon['couponPopular']->coupon_code;
            // Sử dụng $couponName theo nhu cầu của bạn
        } else {
            // Xử lý trường hợp không có mã giảm giá
            $couponName = null;
        }

        // Thống kê đơn hàng     
        $dataOrder = [];
        $dataOrder['orderMonth'] = OrderModel::whereMonth('order_date', now()->startOfMonth())->count();
        $dataOrder['revenueOrder'] = StatisticModel::whereMonth('order_date', now()->startOfMonth())->sum('sales');
        $dataOrder['orderFail'] = OrderModel::where('order_status', 2)->whereMonth('order_date', now()->startOfMonth())->count();
        $dataOrder['orderWaitDelivery'] = OrderModel::whereMonth('order_date', now()->startOfMonth())
        ->where('order_status',0)->orwhere('order_status', 1)->where('order_delivery_status', 0)
        ->where('order_status','!=', 2)
        ->count();
        $dataOrder['orderDelivering'] = OrderModel::where('order_status',1)->where('order_delivery_status', 1)
        ->where('order_status','!=', 2)
        ->whereMonth('order_date', now()->startOfMonth())->count();
        $dataOrder['orderDelivered'] = OrderModel::where('order_status', 10)->whereMonth('order_date', now()->startOfMonth())->count();
        
        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $data = StatisticModel::whereBetween('order_date', [$sub7days, $today])
            ->orderBy('order_date', 'ASC')->get();

        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $data = StatisticModel::whereBetween('order_date', [$sub7days, $today])
            ->orderBy('order_date', 'ASC')->get();

        $chart_data = [];
        foreach ($data as $key => $item) {
            $chart_data[] = [
                'period' => date('d/m/Y', strtotime($item->order_date)),
                'total' => $item->total_order,
                'sales' => $item->sales,
                'profit' => $item->profit,
            ];
        }


        return view('backend.pages.dashboard',compact('revenue','totalOrder', 'countVisitor', 'chart_data', 'totalOrderToday',
            'dataTotal', 'dataVisitor', 'onlineVisitorCount', 'dataOrder','dataTotalCoupon','couponName'));
    }

    public function indexPost(Request $request) 
    {
        //thống kê người dùng online theo ip
        $onlineVisitors = VisitorModel::where('visitor_date', '>=', now()->subMinutes(10))->get();
        $onlineVisitorCount = $onlineVisitors->count();
        if($request->ajax()) {
            return response()->json(['visitorTotal' => $onlineVisitorCount]);
        }
        return response()->json(['error' => 'Không tìm thấy dữ liệu']);
    }
    
    public function filterVisitor(Request $request)
    {
        $dataFilter = $request->input('dataDate');
        if($dataFilter) {
            $dataVisitor = []; 
            $dataTotalVisitor = Analytics::fetchTotalVisitorsAndPageViews(Period::days($dataFilter))->sortBy('date');
            $dataVisitor['date'] = $dataTotalVisitor->pluck('date')->map(function ($date) {
                return $date->format('d/m/Y');
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
        $thismonth = Carbon::now('Asia/Ho_Chi_minh')->startOfMonth()->toDateString();
        $start_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->startOfMonth()->toDateString();
        $end_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->endOfMonth()->toDateString();

        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $sub365days = Carbon::now('Asia/Ho_Chi_minh')->subDays(365)->toDateString();

        $now = Carbon::now('Asia/Ho_Chi_minh')->toDateString();

        if($data['dashboard_value']=='7ngay'){
            $get = StatisticModel::whereBetween('order_date',[$sub7days,$now])
            ->orderBy('order_date','ASC')->get();
        } elseif($data['dashboard_value']=='thangtruoc'){
            $get = StatisticModel::whereBetween('order_date',[$start_month,$end_month])
            ->orderBy('order_date','ASC')->get();
        } elseif($data['dashboard_value']=='thangnay'){
            $get = StatisticModel::whereBetween('order_date',[$thismonth,$now])
            ->orderBy('order_date','ASC')->get();
        } else {
            $get = StatisticModel::whereBetween('order_date',[$sub365days,$now])
            ->orderBy('order_date','ASC')->get();
        }

        $chart_data = [];
        foreach ($get as $key => $data) {
            $chart_data[] = [
                'period' => date('d/m/Y', strtotime( $data->order_date)),
                'sales' => $data->sales,
                'profit' => $data->profit,
            ];
        }
        return response()->json($chart_data);
    }

    public function filterAccountUser(Request $request) 
    {
        $data = $request->dateData;

        $now = Carbon::now('Asia/Ho_Chi_minh')->toDateString();
        $data7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $thisMonth = Carbon::now('Asia/Ho_Chi_minh')->startOfMonth()->toDateString();
        $startMonth = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->startOfMonth()->toDateString();
        $endMonth = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->endOfMonth()->toDateString();

        if($data == '7days') {
            $dataRes = UserModel::where('user_role', 0)->whereBetween(DB::raw('DATE(created_at)'), [$data7days, $now])
            ->orderBy('created_at','ASC')->select(
                DB::raw('DATE(created_at) as date, COUNT(*) as data_count')
            )->groupBy('date')->orderBy('date')->get();
        } elseif($data == 'lmonth') {
            $dataRes = UserModel::where('user_role', 0)->whereBetween(DB::raw('DATE(created_at)'), [$startMonth,$endMonth])
            ->orderBy('created_at','ASC')->select(
                DB::raw('DATE(created_at) as date, COUNT(*) as data_count')
            )->groupBy('date')->orderBy('date')->get();
        } elseif($data == 'tmonth') {
            $dataRes = UserModel::where('user_role', 0)->whereBetween(DB::raw('DATE(created_at)'), [$thisMonth,$now])
            ->orderBy('created_at','ASC')->select(
                DB::raw('DATE(created_at) as date, COUNT(*) as data_count')
            )->groupBy('date')->orderBy('date')->get();
        }

        $dataResNew = [];
        $dataResNew['date'] = $dataRes->pluck('date')->map(function ($date) {
            return date('d/m/Y', strtotime($date));
        });

        $dataResNew['data_count'] = $dataRes->pluck('data_count');

        return response()->json($dataResNew);
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
        foreach ($get as $key => $data) {
            $chart_data[] = [
                'period' =>date('d/m/Y', strtotime( $data->order_date)),
                'sales' => $data->sales,
                'profit' => $data->profit,
            ];
        }
        return response()->json($chart_data);
    }

    public function export_scv()
    {
        return Excel::download(new ExportStatistic() , 'Doanh thu.xlsx');
    }

    public function export_scv_day()
    {
        return Excel::download(new ExportStatisticDay() , 'Doanh thu ngày.xlsx');
    }

    public function export_scv_week()
    {
        return Excel::download(new ExportStatisticWeek() , 'Doanh thu tuần.xlsx');
    }

    public function export_scv_month()
    {
        return Excel::download(new ExportStatisticMonth() , 'Doanh thu tháng.xlsx');
    }

    public function export_scv_monthprev()
    {
        return Excel::download(new ExportStatisticMonthPrev() , 'Doanh thu tháng trước.xlsx');
    }

    public function export_scv_year()
    {
        return Excel::download(new ExportStatisticYear() , 'Doanh thu năm.xlsx');
    }

    public function checkNewOrders(Request $request)
    {
        $today = Carbon::today();
        $prevday = Carbon::today()->subDay();
        $newOrderCount = OrderModel::where('order_status', 0)
            ->where(function ($query) {
                $query->where('order_payment', 'cod')
                    ->where('order_payment_status', 0)
                    ->orWhere(function ($query) {
                        $query->whereIn('order_payment', ['payUrl', 'redirect'])
                            ->where('order_payment_status', 1);
                    });
                })->count();
        $returnOrderCount = OrderModel::where('order_status', 3)
            ->where(function ($query) {
                $query->where('order_payment', 'cod')
                    ->where('order_payment_status', 0)
                    ->orWhere(function ($query) {
                        $query->whereIn('order_payment', ['payUrl', 'redirect'])
                            ->where('order_payment_status', 1);
                });
            })->count();
        $sucessOrderCount = OrderModel::where('order_status', 10)->whereDate('updated_at', $today)
            ->where(function ($query) {
                $query->where('order_payment', 'cod')
                    ->where('order_payment_status', 0)
                    ->orWhere(function ($query) {
                        $query->whereIn('order_payment', ['payUrl', 'redirect'])
                            ->where('order_payment_status', 1);
                });
            })->count();
        $couponCount = CouponModel::where('coupon_end','=',$prevday)->pluck('coupon_name')->toArray();
        $slideCount = PromotionModel::where('promotion_end','=',$prevday)->pluck('promotion_name')->toArray();
        $contactCount = ContactFormModel::where('status',0)->count();

        $latestOrder = OrderModel::where('order_status', 0)->latest('created_at')->first();
        $latestReturnOrder = OrderModel::where('order_status', 3)->latest('updated_at')->first();
        $latestSuccessOrder = OrderModel::where('order_status', 10)->latest('updated_at')->first();
        $latestContact = ContactFormModel::where('status', 0)->latest('created_at')->first();
        $latestSlide = PromotionModel::where('promotion_end','=',$prevday)->latest('updated_at')->first();
        $latestCoupon = CouponModel::where('coupon_end','=',$prevday)->latest('updated_at')->first();

        $timestampOrder = $latestOrder ? $latestOrder->created_at->timestamp : now()->timestamp;
        $timestampReturnOrder = $latestReturnOrder ? $latestReturnOrder->updated_at->timestamp : now()->timestamp;
        $timestampSuccessOrder = $latestSuccessOrder ? $latestSuccessOrder->updated_at->timestamp : now()->timestamp;
        $timestampContact = $latestContact ? $latestContact->created_at->timestamp : now()->timestamp;
        $timestampSlide = $latestSlide ? $latestSlide->updated_at->timestamp : now()->timestamp;
        $timestampCoupon = $latestCoupon ? $latestCoupon->updated_at->timestamp : now()->timestamp;

        return response()->json(['newOrderCount' => $newOrderCount,
            'contactCount' => $contactCount,
            'returnOrderCount' => $returnOrderCount, 
            'sucessOrderCount' => $sucessOrderCount,
            'couponCount' => $couponCount, 
            'slideCount' => $slideCount,
            'timestampOrder' => $timestampOrder,
            'timestampReturnOrder' => $timestampReturnOrder,
            'timestampSuccessOrder' => $timestampSuccessOrder,
            'timestampContact' => $timestampContact,
            'timestampSlide' => $timestampSlide,
            'timestampCoupon' => $timestampCoupon,
        ]);
    }

    public function revenue(Request $request)
    {
        $perpage = 15;
        $orderBy = $request->input('sort-by', 'order_date'); 
        $orderType = $request->input('sort-type', 'desc');

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
                $query->where('order_date',$now)
                    ->orderBy('order_date', 'asc');
                break;
            case 'week':
                $query->whereBetween('order_date',[$sub7days,$now])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'month':
                $query->whereBetween('order_date',[$thismonth,$now])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'pmonth':
                $query->whereBetween('order_date',[$start_month,$end_month])
                    ->orderBy('order_date', 'DESC');
                break;
            case 'year':
                $query->whereBetween('order_date',[$sub365days,$now])
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

        $revenueday = StatisticModel::where('order_date',$now)->get();
        $revenueweek = StatisticModel::whereBetween('order_date',[$sub7days,$now])->get();
        $revenuemonth = StatisticModel::whereBetween('order_date',[$thismonth,$now])->get();
        $revenuemonthprev = StatisticModel::whereBetween('order_date',[$start_month,$end_month])->get();
        $revenueyear = StatisticModel::whereBetween('order_date',[$start_year,$now])->get();
        $revenueall = StatisticModel::get();
        return view('backend.pages.statistical.revenue', compact('revenueall','revenueyear','revenuemonthprev','revenuemonth','revenueday','revenueweek','statistic','orderBy', 'orderType'));
    }
}