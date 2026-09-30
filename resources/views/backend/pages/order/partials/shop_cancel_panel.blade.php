{{--
    The shop calling the order off itself, while the goods are still in the
    warehouse. A form of its own, outside the order form.

    @param \App\Models\OrderModel $order
--}}
<div class="card mb-4 card-border-top" id="huy-don">
    <div class="card-body">
        <details @if ($errors->has('shop_cancel_reason')) open @endif>
            <summary class="fs-5 text-danger">Huỷ đơn hàng</summary>
            <form action="{{ route('order.cancel_by_shop', $order->order_id) }}" method="POST" class="mt-3"
                onsubmit="return confirm('Huỷ đơn {{ $order->order_code }}? Việc này không hoàn tác được.')">
                @csrf
                <label for="shop-cancel-reason" class="form-label">Lý do huỷ (khách sẽ thấy trên trang đơn hàng)</label>
                <textarea class="form-control mb-2" id="shop-cancel-reason" name="shop_cancel_reason" rows="2"
                    maxlength="255">{{ old('shop_cancel_reason') }}</textarea>
                @error('shop_cancel_reason') <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror
                <button type="submit" class="btn btn-danger">Huỷ đơn</button>
                <div class="form-text">
                    Tồn kho và mã giảm giá được trả lại. Đơn đã thanh toán được hoàn tiền vào SPay của khách.
                    @if ($order->order_shipping_code)
                        Vận đơn <b>{{ $order->order_shipping_code }}</b> sẽ được huỷ trên GHN.
                    @endif
                </div>
            </form>
        </details>
    </div>
</div>
