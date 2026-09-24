<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Services\Returns\OrderReturns;
use App\Services\Returns\ReturnNotAllowed;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * The shop's side of a return: decide, bring the parcel back, refund.
 */
class ReturnAdminController extends Controller
{
    public function __construct(private OrderReturns $returns) {}

    public function approve(string $order_id): RedirectResponse
    {
        return $this->run($order_id, fn (OrderModel $order) => $this->returns->approve($order), 'Đã duyệt yêu cầu trả hàng.');
    }

    public function reject(Request $request, string $order_id): RedirectResponse
    {
        $data = $request->validate(
            ['reject_reason' => ['required', 'string', 'max:500']],
            ['reject_reason.required' => 'Vui lòng nhập lý do từ chối.', 'reject_reason.max' => 'Lý do tối đa :max ký tự.'],
        );

        return $this->run($order_id, fn (OrderModel $order) => $this->returns->reject($order, $data['reject_reason']), 'Đã từ chối yêu cầu trả hàng.');
    }

    public function book(string $order_id, ShippingService $shipping): RedirectResponse
    {
        $order = OrderModel::findOrFail($order_id);
        $return = OrderReturnModel::where('order_id', $order->order_id)->firstOrFail();

        try {
            $booking = $shipping->bookReturn($return);
        } catch (ShippingUnavailable $e) {
            return $this->back($order, 'error', $e->getMessage());
        }

        return $this->back($order, 'success', 'Đã tạo vận đơn trả hàng '.$booking->code.'.');
    }

    /**
     * For a return parcel booked on GHN's own dashboard.
     */
    public function shippingCode(Request $request, string $order_id): RedirectResponse
    {
        $data = $request->validate(
            ['return_shipping_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9]+$/', 'unique:order_returns,return_shipping_code', 'unique:order,order_shipping_code']],
            [
                'return_shipping_code.required' => 'Vui lòng nhập mã vận đơn.',
                'return_shipping_code.regex' => 'Mã vận đơn chỉ gồm chữ và số.',
                'return_shipping_code.unique' => 'Mã vận đơn này đã được dùng.',
            ],
        );

        $order = OrderModel::findOrFail($order_id);
        $return = OrderReturnModel::where('order_id', $order->order_id)->firstOrFail();

        if ($return->status !== OrderReturnModel::APPROVED || $return->return_shipping_code) {
            return $this->back($order, 'error', 'Chỉ gắn được mã cho yêu cầu đã duyệt và chưa có vận đơn.');
        }

        $return->return_shipping_code = strtoupper($data['return_shipping_code']);
        $return->return_shipping_status = null;
        $return->save();

        return $this->back($order, 'success', 'Đã gắn mã vận đơn trả hàng.');
    }

    public function receive(string $order_id): RedirectResponse
    {
        return $this->run($order_id, fn (OrderModel $order) => $this->returns->receive($order), 'Đã nhận hàng trả, tồn kho đã được cộng lại đúng số lượng.');
    }

    public function refund(Request $request, string $order_id): RedirectResponse
    {
        $order = OrderModel::findOrFail($order_id);
        $return = OrderReturnModel::where('order_id', $order->order_id)->firstOrFail();

        // Capped at what the returned lines are worth, not at the order total:
        // a customer who sent one of two pairs back is owed one pair.
        $data = $request->validate(
            ['refund_amount' => ['required', 'integer', 'min:0', 'max:'.$return->refundDue()]],
            [
                'refund_amount.required' => 'Vui lòng nhập số tiền đã hoàn.',
                'refund_amount.integer' => 'Số tiền phải là số nguyên.',
                'refund_amount.min' => 'Số tiền không được âm.',
                'refund_amount.max' => 'Số tiền hoàn không vượt quá giá trị hàng trả (:max đ).',
            ],
        );

        return $this->run($order_id, fn (OrderModel $o) => $this->returns->refund($o, (int) $data['refund_amount']), 'Đã hoàn tiền vào ví khách.');
    }

    private function run(string $order_id, callable $step, string $done): RedirectResponse
    {
        $order = OrderModel::findOrFail($order_id);

        try {
            $step($order);
        } catch (ReturnNotAllowed $e) {
            return $this->back($order, 'error', $e->getMessage());
        }

        return $this->back($order, 'success', $done);
    }

    private function back(OrderModel $order, string $icon, string $message): RedirectResponse
    {
        Session::flash('iconMessage', $icon);

        return redirect()->route('orders.edit', encrypt($order->order_id))->with('message', $message);
    }
}
