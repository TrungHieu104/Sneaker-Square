<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ShippingCodeRequest;
use App\Services\Shipping\ShipmentPulse;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use App\Services\OrderRevenue;
use App\Actions\CancelOrderAction;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $newOrdersCount = OrderModel::where('order_status', OrderStatus::New)
            ->where('created_at', '>=', now()->subDay()) // Orders created in the last 24 hours
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
        $query = OrderModel::orderBy($orderBy, $orderType)->confirmedSale();

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
        $orderNew = $orderNew->where('order_status', OrderStatus::New)
            ->paginate($perpage, ['*'], 'order_new_page')->withQueryString();

        // Waiting on the shop: confirmed but still on the shelf, or packed and
        // waiting for the courier.
        $orderConfirm = clone $query;
        $orderConfirm = $orderConfirm->whereIn('order_status', [OrderStatus::Confirmed, OrderStatus::ReadyToShip])
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderDeli = clone $query;
        $orderDeli = $orderDeli->whereIn('order_status', [OrderStatus::Delivering, OrderStatus::Delivered])
            ->paginate($perpage, ['*'], 'order_new_page')->withQueryString();

        $orderCancelRequest = clone $query;
        $orderCancelRequest = $orderCancelRequest->where('order_status', OrderStatus::CancelRequested)
            ->paginate($perpage, ['*'], 'order_cancel_request')->withQueryString();

        $orderCancel = clone $query;
        $orderCancel = $orderCancel->where('order_status', OrderStatus::Cancelled)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderReturn = clone $query;
        $orderReturn = $orderReturn->whereIn('order_status', OrderStatus::comingBack())
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();

        $orderSuccess = clone $query;
        $orderSuccess = $orderSuccess->where('order_status', OrderStatus::Completed)
            ->paginate($perpage, ['*'], 'order_confirm')->withQueryString();
                
        return view('backend.pages.order.order_list', compact('order', 'orderBy', 'orderType','keyword','orderNew','orderConfirm','orderCancel','orderCancelRequest','orderDeli','orderSuccess','orderReturn'));
    }

    public function printOrder(Request $request,$encryptedOrderId){
        try{
            $order_id = Crypt::decrypt($encryptedOrderId);
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML($this->print_order_convert($order_id));
            return $pdf->stream();
        } catch (DecryptException $e) {
            // The identifier could not be decrypted.
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
            $order = OrderModel::with(['orderDetail', 'Coupon', 'User'])->find($order_id);
    
            if ($order == null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
            }
    
            $orderDetail = OrderDetailModel::with('product')->where('order_id', $order_id)->get();
    
            return view("backend.pages.order.order_detail", compact('order', 'orderDetail'));
        } catch (DecryptException $e) {
            // The identifier could not be decrypted.
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }
    }
    

    /**
     * Hands the parcel to GHN.
     *
     * Behind a config flag rather than a permission: against the production
     * host this books a courier who turns up at the shop, and that is not a
     * mistake anyone should be one click away from.
     */
    /**
     * A parcel leaves the warehouse for a confirmed order and nothing else. A
     * cancelled one has already put its stock back and released its coupon,
     * and a returned one is travelling the other way.
     */
    /**
     * Takes back the manual handover mark, which is all "bàn giao vận chuyển"
     * ever wrote. It corrects a mis-click rather than reversing a step: the
     * order goes back to having neither carrier chosen, the same place
     * cancelling a GHN parcel leaves it.
     */
    public function undoHandover(string $order_id)
    {
        $order = OrderModel::find($order_id);

        if ($order == null) {
            Session::flash('iconMessage', 'info');

            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }

        if (! $order->hasStatus(OrderStatus::Delivering) || ! $order->isHandedOverManually()) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Chỉ đơn đang giao thủ công và khách chưa xác nhận mới huỷ bàn giao được!');
        }

        $order->order_delivery_status = 0;
        $order->moveTo(OrderStatus::Confirmed, OrderStatusLogModel::ACTOR_ADMIN, 'Huỷ bàn giao thủ công');

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã huỷ bàn giao. Chọn lại cách giao cho đơn này.');
    }

    /**
     * The two ways out of the warehouse exclude each other, and hiding the
     * buttons is not enough: whichever was chosen first has to hold.
     */
    private function refuseIfHandedOverManually(OrderModel $order): ?RedirectResponse
    {
        if (! $order->isHandedOverManually()) {
            return null;
        }

        Session::flash('iconMessage', 'error');

        return back()->with('message', 'Đơn này đã bàn giao cho đơn vị vận chuyển của shop, không dùng vận đơn GHN được!');
    }

    private function refuseUnlessConfirmed(OrderModel $order): ?RedirectResponse
    {
        if ($order->isConfirmed()) {
            return null;
        }

        Session::flash('iconMessage', 'error');

        return back()->with('message', 'Chỉ đơn hàng đã xác nhận mới gắn được vận đơn!');
    }

    public function bookShipment(string $order_id, ShippingService $shipping)
    {
        $order = OrderModel::with('orderDetail.product')->find($order_id);

        if ($order == null) {
            Session::flash('iconMessage', 'info');

            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }

        if ($refusal = $this->refuseIfHandedOverManually($order) ?? $this->refuseUnlessConfirmed($order)) {
            return $refusal;
        }

        try {
            $booking = $shipping->book($order);
        } catch (ShippingUnavailable $e) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', $e->getMessage());
        }

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã tạo vận đơn '.$booking->code.'!');
    }

    public function cancelShipment(string $order_id, ShippingService $shipping)
    {
        $order = OrderModel::find($order_id);

        if ($order == null) {
            Session::flash('iconMessage', 'info');

            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }

        try {
            $shipping->cancelBooking($order);
        } catch (ShippingUnavailable $e) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', $e->getMessage());
        }

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã huỷ vận đơn!');
    }

    /**
     * Ties an order to the parcel the shop created on GHN.
     *
     * Until this is filled in a status callback has nothing to match on, so
     * the timeline on the customer's order page stays empty.
     */
    public function updateShippingCode(ShippingCodeRequest $request, string $order_id)
    {
        $order = OrderModel::find($order_id);

        if ($order == null) {
            Session::flash('iconMessage', 'info');

            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }

        if ($refusal = $this->refuseIfHandedOverManually($order) ?? $this->refuseUnlessConfirmed($order)) {
            return $refusal;
        }

        $code = trim((string) $request->input('order_shipping_code'));
        $order->order_shipping_code = $code === '' ? null : $code;
        $order->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Cập nhật mã vận đơn thành công!');
    }

    /**
     * Streams this order's shipment history to an open admin page.
     *
     * The browser never asks again: it opens one EventSource and is written to
     * when GHN's callback moves the marker. The watching happens here, against
     * the cache, so a page left open costs no database work.
     */
    public function shipmentStream(string $order_id, ShipmentPulse $pulse): StreamedResponse
    {
        $order = OrderModel::find($order_id);

        if ($order == null) {
            abort(404);
        }

        $giay = (int) config('services.ghn.stream_seconds', 60);

        return response()->stream(function () use ($order_id, $pulse, $giay) {
            $moc = $pulse->current((int) $order_id);

            $this->sendShipmentEvent($order_id, $moc);

            for ($i = 0; $i < $giay; $i++) {
                if (connection_aborted()) {
                    return;
                }

                sleep(1);
                $moiNhat = $pulse->current((int) $order_id);

                if ($moiNhat !== $moc) {
                    $moc = $moiNhat;
                    $this->sendShipmentEvent($order_id, $moc);

                    continue;
                }

                // A comment line keeps proxies from closing an idle stream and
                // is how a disconnected browser gets noticed.
                echo ": .\n\n";
                $this->flushStream();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, private',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendShipmentEvent(string $order_id, string $moc): void
    {
        // Re-read rather than reuse: the callback that moved the marker wrote
        // the rows this render has to pick up.
        $order = OrderModel::with('shipmentEvents')->find($order_id);

        if ($order == null) {
            return;
        }

        // Both halves, because a parcel cancelled on GHN's dashboard has to take
        // its buttons away too, not merely gain a line of history.
        $khoi = [
            'vandon' => view('backend.pages.order.partials.shipping_actions', [
                'order' => $order,
            ])->render(),
            'hanhtrinh' => view('components.shipment_timeline', [
                'order' => $order,
                'formHuy' => config('services.ghn.create_orders') ? 'form-huy-van-don' : null,
                'hienVanDonCu' => true,
            ])->render(),
        ];

        echo 'event: hanhtrinh'."\n";
        echo 'id: '.$moc."\n";
        echo 'data: '.json_encode($khoi, JSON_UNESCAPED_UNICODE)."\n\n";
        $this->flushStream();
    }

    private function flushStream(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * The one form on the order page, which carries the admin's note and at
     * most one decision. The decision is named rather than being a status
     * number posted from the browser: the page no longer gets to say what the
     * order becomes, only which button was pressed.
     */
    public function update(Request $request, string $order_id, OrderRevenue $revenue)
    {
        $order = OrderModel::find($order_id);

        if ($order == null) {
            Session::flash('iconMessage', 'info');

            return redirect('admin/order')->with('message', 'Đơn hàng không tồn tại');
        }

        $order->note_admin = (string) $request->input('note', '');

        $refusal = match ((string) $request->input('action', '')) {
            'confirm' => $this->confirmOrder($order),
            'handover' => $this->handOverManually($order),
            'refund' => $this->confirmRefund($order, $revenue),
            default => $this->saveNoteOnly($order),
        };

        if ($refusal) {
            return $refusal;
        }

        Session::flash('iconMessage', 'success');

        return redirect('admin/order')->with('message', 'Cảm ơn bạn đã xác nhận');
    }

    private function saveNoteOnly(OrderModel $order): ?RedirectResponse
    {
        $order->save();

        return null;
    }

    private function confirmOrder(OrderModel $order): ?RedirectResponse
    {
        if (! $order->hasStatus(OrderStatus::New)) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Chỉ đơn hàng mới mới cần xác nhận!');
        }

        $order->order_payment_time = now();
        $order->moveTo(OrderStatus::Confirmed, OrderStatusLogModel::ACTOR_ADMIN, 'Xác nhận đơn hàng');

        return null;
    }

    private function handOverManually(OrderModel $order): ?RedirectResponse
    {
        if ($order->usesGhn()) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Đơn này đang đi qua GHN, không bàn giao thủ công được!');
        }

        if (! $order->hasStatus(OrderStatus::Confirmed)) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Chỉ đơn đã xác nhận và còn ở kho mới bàn giao được!');
        }

        $order->order_delivery_status = 1;
        $order->moveTo(OrderStatus::Delivering, OrderStatusLogModel::ACTOR_ADMIN, 'Bàn giao cho đơn vị vận chuyển của shop');

        return null;
    }

    /**
     * The shop has sent the money back for a parcel that came home. Revenue is
     * booked when the customer has the goods, so this is the one place here
     * that takes it back out.
     */
    private function confirmRefund(OrderModel $order, OrderRevenue $revenue): ?RedirectResponse
    {
        if (! $order->hasStatus(OrderStatus::Returned) || $order->orderReturn) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Đơn này không ở trạng thái chờ hoàn tiền!');
        }

        $order->order_refund_required = false;
        $order->moveTo(OrderStatus::Cancelled, OrderStatusLogModel::ACTOR_ADMIN, 'Xác nhận đã hoàn tiền cho khách');

        DB::transaction(function () use ($order, $revenue) {
            $revenue->reverse(OrderModel::where('order_id', $order->order_id)->lockForUpdate()->first());
        });

        return null;
    }

    /**
     * The customer asked the shop to call the order off. Approving it runs the
     * same cancellation as any other, plus releasing the parcel if one was
     * already booked.
     */
    public function approveCancel(string $order_id, CancelOrderAction $cancel, ShippingService $shipping)
    {
        $order = OrderModel::find($order_id);

        if ($order == null || ! $order->hasStatus(OrderStatus::CancelRequested)) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Đơn này không có yêu cầu huỷ đang chờ!');
        }

        if ($order->order_shipping_code) {
            try {
                $shipping->cancelBooking($order);
            } catch (ShippingUnavailable $e) {
                Session::flash('iconMessage', 'error');

                return back()->with('message', 'Không huỷ được vận đơn GHN: '.$e->getMessage());
            }
        }

        $cancel->execute($order->fresh(), allowPaid: true, actor: OrderStatusLogModel::ACTOR_ADMIN, note: 'Duyệt yêu cầu huỷ của khách');

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã duyệt huỷ đơn.');
    }

    public function rejectCancel(Request $request, string $order_id)
    {
        $order = OrderModel::find($order_id);

        if ($order == null || ! $order->hasStatus(OrderStatus::CancelRequested)) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Đơn này không có yêu cầu huỷ đang chờ!');
        }

        $reason = trim((string) $request->validate([
            'cancel_reject_reason' => ['required', 'string', 'max:255'],
        ], [
            'cancel_reject_reason.required' => 'Nhập lý do từ chối để khách biết.',
        ])['cancel_reject_reason']);

        $order->order_cancel_reason = $reason;
        $order->moveTo($order->statusBeforeCancelRequest(), OrderStatusLogModel::ACTOR_ADMIN, 'Từ chối yêu cầu huỷ: '.$reason);

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã từ chối yêu cầu huỷ.');
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
