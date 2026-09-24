{{--
    The customer has asked the shop to call this order off.

    Rendered outside the order form: both actions here are forms of their own.

    @param \App\Models\OrderModel $order
--}}
<div class="card mb-4 card-border-top" id="yeu-cau-huy">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fs-4 text-primary my-0">Khách xin huỷ đơn</h5>
            <span class="badge bg-label-danger fs-6">Chờ shop duyệt</span>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="mb-2"><b>Lý do khách đưa ra:</b> {{ $order->order_cancel_reason ?: 'Khách không ghi lý do' }}</div>
                @if ($order->order_shipping_code)
                    <div class="alert alert-warning py-2 px-3 mb-0" role="alert">
                        Đơn đã có vận đơn <b>{{ $order->order_shipping_code }}</b>. Duyệt huỷ sẽ huỷ luôn vận đơn này trên GHN.
                    </div>
                @endif
            </div>

            <div class="col-lg-6 mb-3">
                <form action="{{ route('order.approve_cancel', $order->order_id) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-danger">Duyệt huỷ đơn</button>
                    <div class="form-text">Tồn kho và mã giảm giá sẽ được trả lại.</div>
                </form>

                <form action="{{ route('order.reject_cancel', $order->order_id) }}" method="POST">
                    @csrf
                    <label for="cancel-reject-reason" class="form-label">Hoặc từ chối, kèm lý do gửi khách:</label>
                    <textarea class="form-control mb-2" id="cancel-reject-reason" name="cancel_reject_reason" rows="2">{{ old('cancel_reject_reason') }}</textarea>
                    @error('cancel_reject_reason') <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror
                    <button type="submit" class="btn btn-outline-secondary">Từ chối yêu cầu</button>
                    <div class="form-text">Đơn quay lại trạng thái trước khi khách xin huỷ.</div>
                </form>
            </div>
        </div>
    </div>
</div>
