<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use App\Models\StatisticModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use App\Exports\ExportOrder;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\View;
use Illuminate\Contracts\Encryption\DecryptException;

class OrderAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        $isNewOrder = $this->checkForNewOrders();
        View::share(compact('keyword','isNewOrder'));
    }
    public function checkForNewOrders()
    {
        $newOrdersCount = OrderModel::where('order_status', 0)
            ->where('created_at', '>=', now()->subDay()) // Đơn hàng tạo trong 24 giờ qua
            ->count();

        return response()->json(['newOrders' => $newOrdersCount]);
    }

    public function index(Request $request)
    {
        $perpage = 30;
        $orderBy = $request->input('sort-by', 'order_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['order_name','order_code','order_date'];
        
        $sortOption = $request->input('sort', 'default');
        $query = OrderModel::orderBy($orderBy, $orderType)->where(function ($query) {
            $query->where('order_payment', 'cod')
                    ->where('order_payment_status', 0)
                    ->orWhere(function ($query) {
                        $query->whereIn('order_payment', ['payUrl', 'redirect'])
                            ->where('order_payment_status', 1);
            });
        });

        $thismonth = Carbon::now('Asia/Ho_Chi_minh')->startOfMonth()->toDateString();
        $start_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->startOfMonth()->toDateString();
        $end_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->endOfMonth()->toDateString();

        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        $sub365days = Carbon::now('Asia/Ho_Chi_minh')->subDays(365)->toDateString();

        $now = Carbon::now('Asia/Ho_Chi_minh')->toDateString();

        switch ($sortOption) {
            case 'today':
                $query->where('order_date',$now)
                    ->orderBy('order_id', 'asc');
                break;
            case 'week':
                $query->whereBetween('order_date',[$sub7days,$now])
                    ->orderBy('order_id', 'DESC');
                break;
            case 'month':
                $query->whereBetween('order_date',[$thismonth,$now])
                    ->orderBy('order_id', 'DESC');
                break;
            case 'pmonth':
                $query->whereBetween('order_date',[$start_month,$end_month])
                    ->orderBy('order_id', 'DESC');
                break;
            case 'year':
                $query->whereBetween('order_date',[$sub365days,$now])
                    ->orderBy('order_id', 'DESC');
                break;
            default:
                $query->orderBy('order_id', 'DESC');
                break;
        }

        $query = $this->performSearch($query, $keyword, $searchableFields);

        $order = $query->paginate($perpage, ['*'], 'order_page')->withQueryString();   
        
        $orderNew = clone $query;  
        $orderNew = $orderNew->where('order_status', 0)
            ->paginate($perpage, ['*'], 'order_new_page')->withQueryString();

        $orderConfirm = clone $query;
        $orderConfirm = $orderConfirm->where('order_status', 1)
            ->where('order_delivery_status', 0)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderDeli = clone $query;
        $orderDeli = $orderDeli->where('order_status', 1)
            ->where('order_delivery_status', 1)
            ->paginate($perpage, ['*'], 'order_new_page')->withQueryString();
        
        $orderCancel = clone $query;
        $orderCancel = $orderCancel->where('order_status', 2)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderReturn = clone $query;
        $orderReturn = $orderReturn->where('order_status', 3)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderSuccess = clone $query;
        $orderSuccess = $orderSuccess->where('order_status', 10)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();
                
        return view('backend.pages.order.order_list', compact('order', 'orderBy', 'orderType','keyword','orderNew','orderConfirm','orderCancel','orderDeli','orderSuccess','orderReturn'));
    }

    public function printOrder(Request $request,$encryptedOrderId){
        try{
            $order_id = Crypt::decrypt($encryptedOrderId);
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML($this->print_order_convert($order_id));
            return $pdf->stream();
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }
    }

    public function print_order_convert($order_id){
        $order = OrderModel::where('order_id', $order_id)->first();
        $od = OrderDetailModel::where('order_id', $order->order_id)->get();
        return view('backend.pages.order.pdf.print_bill',compact('od','order'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $encryptedOrderId)
    {
        try {
            $order_id = Crypt::decrypt($encryptedOrderId);
            $order = OrderModel::with(['orderDetail', 'Coupon'], ['orderDetail', 'User'])->find($order_id);
    
            if ($order == null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
            }
    
            $orderDetail = OrderDetailModel::where('order_id', $order_id)->get();
    
            return view("backend.pages.order.order_detail", compact('order', 'orderDetail'));
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $order_id)
    {
        $arr = $request->post();
        $note = ($request->has('note'))? $arr['note']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $deli = ($request->has('deli'))? (int)$arr['deli']:"0";
        $order_product_id = ($request->has('order_product_id')) ? (array)$arr['order_product_id']:"0";
        $order = OrderModel::find($order_id);
        if ($order ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }
        if ($request->has('status')) {
            if ($order->order_status == 0) {
                $order->order_payment_time = now();
            }
        }
        $order->note_admin = $note;
        $order->order_status = $status;
        $order->order_delivery_status = $deli;
        $order->save();
        $order_date = Carbon::parse($order->order_date)->format('Y-m-d');
        $statistic = StatisticModel::where('order_date',$order_date)->get();
        if($statistic){
            $statistic_count = $statistic->count();
        } else {
            $statistic_count = 0;
        }
        if($order->order_status==1 && $order->order_delivery_status== 1){
            $salesplus = 0;
            $profitplus = 0;
            $orderTotal = 0;
            $order_product_id = $request->input('order_product_id', []);
            $cou_val = $request->input('cou_val');
            // dd($cou_val);
            foreach ($order_product_id as $pro_id) {
                $product = ProductModel::find($pro_id);
                $product_sale = $product->pro_price_sale;
                $product_price = $product->pro_price;
                $capital = $product->capital_price;
                if($product_sale==0){
                    $capital_price = $product_price - $capital;
                    $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
                    
                    $quantitys = OrderDetailModel::where('pro_id', $pro_id)->get();
                    foreach ($quantitys as $detail) {
                        $qty = $detail->quantity;
                    }
                    $profitplus += $capital_price * $qty;
                    $salesplus += $product_price * $qty;
                    $orderTotal++;
                } else{
                    $capital_price = $product_sale - $capital;
                    $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
                    
                    $quantitys = OrderDetailModel::where('pro_id', $pro_id)->get();
                    foreach ($quantitys as $detail) {
                        $qty = $detail->quantity;
                    }
                    $profitplus += $capital_price * $qty;
                    $salesplus += $product_sale * $qty;
                    $orderTotal++;
                }
                $profit = $profitplus - $cou_val;
                $sales = $salesplus - $cou_val;
                if($profit < 0){
                    $profit = 0;
                }
            }            
            if($statistic_count>0){
                $statistic_update = StatisticModel::where('order_date',$order_date)->first();
                $statistic_update->sales = $statistic_update->sales + $sales;
                $statistic_update->profit = $statistic_update->profit + $profit;
                $statistic_update->order_total = $statistic_update->order_total + $orderTotal;
                $statistic_update->save();
            }else{
                $statistic_new = new StatisticModel();
                $statistic_new->order_date = $order_date;
                $statistic_new->sales = $sales;
                $statistic_new->profit = $profit;
                $statistic_new->order_total = $orderTotal;
                $statistic_new->save();
            }
        }
        elseif($order->order_status==2){
            $salesplus = 0;
            $profitplus = 0;
            $orderTotal = 0;
            $order_product_id = $request->input('order_product_id', []);
            $cou_val = $request->input('cou_val');
            foreach ($order_product_id as $pro_id) {
                $product = ProductModel::find($pro_id);
                $product_sale = $product->pro_price_sale;
                $product_price = $product->pro_price;
                $capital = $product->capital_price;
                if($product_sale==0){
                    $capital_price = $product_price - $capital;
                    $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
                    $quantitys = OrderDetailModel::where('pro_id', $pro_id)->get();
                    foreach ($quantitys as $detail) {
                        $qty = $detail->quantity;
                    }
                    $profitplus -= $capital_price * $qty;
                    $salesplus -= $product_price * $qty;
                    $orderTotal--;
                } else{
                    $capital_price = $product_sale - $capital;
                    $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
                    
                    $quantitys = OrderDetailModel::where('pro_id', $pro_id)->get();
                    foreach ($quantitys as $detail) {
                        $qty = $detail->quantity;
                    }
                    $profitplus -= $capital_price * $qty;
                    $salesplus -= $product_sale * $qty;
                    $orderTotal--;
                }
                $profit = $profitplus + $cou_val;
                $sales = $salesplus + $cou_val;
                if($profit >= 0){
                    $profit = 0;
                }
            }            
            if($statistic_count>0){
                $statistic_update = StatisticModel::where('order_date',$order_date)->first();
                $statistic_update->sales = $statistic_update->sales + $sales;
                $statistic_update->profit = $statistic_update->profit + $profit;
                $statistic_update->order_total = $statistic_update->order_total + $orderTotal;
                $statistic_update->save();
            }else{
                $statistic_new = new StatisticModel();
                $statistic_new->order_date = $order_date;
                $statistic_new->sales = $sales;
                $statistic_new->profit = $profit;
                $statistic_new->order_total = $orderTotal;
                $statistic_new->save();
            }
        }
        Session::flash('iconMessage', 'success');
        return redirect('admin/order')->with('message', 'Cảm ơn bạn đã xác nhận');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function update_order_qty(Request $request){
        $data = $request->all();
        $order = OrderModel::find($data['order_id']);
        $order->order_status = $data['order_status'];
        $order->save();

        $order_date = $order->order_date;
        $statistic = StatisticModel::where('order_date',$order_date)->get();
        if($statistic){
            $statistic_count = $statistic->count();
        } else {
            $statistic_count = 0;
        }
        if($order->order_status==1){
            $total_order = 0;
            $sales = 0;
            $profit = 0;
            $quantity = 0;
            foreach($data['order_product_id'] as $key => $pro_id){
                $product = ProductModel::find($pro_id);
                $product_quantity = $product->product_quantity;
                $product_sold = $product->product_sold;

                $product_price = $product->product_price;
                $now = Carbon::now('Asia/Ho Chi Minh')->toDateString();
                foreach($data['quantity'] as $key2 => $qty){
                    if($key == $key2){
                        $pro_remain = $product_quantity - $qty;
                        $product->product_quantity = $pro_remain;
                        $product->product_sold = $product_sold + $qty;
                        $product->save();
                        $quantity+=$qty;
                        $total_order+=1;
                        $sales+=$product_price*$qty;
                        $profit = $sales-1000;
                    }

                }
            }
            if($statistic_count>0){
                $statistic_update = StatisticModel::where('order_date',$order_date)->first();
                $statistic_update->sales = $statistic_update->sales + $sales;
                $statistic_update->profit = $statistic_update->profit + $profit;
                $statistic_update->quantity = $statistic_update->quantity + $quantity;
                $statistic_update->total_order = $statistic_update->total_order + $total_order;
                $statistic_update->save();
            }else{
                $statistic_new = new StatisticModel();
                $statistic_new->order_date = $order_date;
                $statistic_new->sales = $sales;
                $statistic_new->profit = $profit;
                $statistic_new->quantity = $quantity;
                $statistic_new->total_order = $total_order;
                $statistic_new->save();
            }
        }
    }

    public function exportorder_scv(){
        return Excel::download(new ExportOrder() , 'Đơn hàng.xlsx');
    }
}
