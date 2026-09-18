{{--
    Ties this order to a parcel GHN is already carrying.

    The form itself is declared outside the order form at the bottom of the page;
    these controls join it by id, because a nested form would be dropped.

    @param \App\Models\OrderModel $order
--}}
<div class="input-group mt-2">
    <input type="text" class="form-control" id="shipping-code" name="order_shipping_code"
           form="form-ma-van-don"
           value="{{ old('order_shipping_code', $order->order_shipping_code) }}"
           placeholder="Ví dụ: LFV3G8" />
    <button class="btn btn-primary" type="submit" form="form-ma-van-don">Lưu</button>
</div>
@foreach ($errors->get('order_shipping_code') as $error)
    <small class="text-danger fst-italic d-block">{{ $error }}</small>
@endforeach
<small class="text-muted fst-italic d-block mt-1">
    Gắn mã để nhận cập nhật trạng thái tự động từ GHN.
</small>
