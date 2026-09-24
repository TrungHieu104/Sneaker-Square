{{--
    What the shop can do to this order's parcel right now.

    Rendered both by the page and by the event stream, so a parcel cancelled on
    GHN's own dashboard takes its buttons away without a refresh.

    @param \App\Models\OrderModel $order
--}}
@php
    $batTaoVanDon = config('services.ghn.create_orders');
    $moSuaMa = $errors->has('order_shipping_code');
@endphp

@if ($order->isBeingReturned())
    <div class="alert alert-warning py-2 px-3 mb-2" role="alert">
        @if ($order->order_shipping_status === 'return_fail')
            GHN báo hoàn hàng không thành công. Liên hệ GHN để xử lý kiện hàng này.
        @else
            GHN báo: <b>{{ \App\Services\Shipping\GhnStatus::label($order->order_shipping_status) }}</b>.
            Gọi cho khách ({{ $order->order_phone }}) trước khi hàng bị hoàn về.
        @endif
    </div>
@elseif ($order->hasStatus(\App\Enums\OrderStatus::Returned))
    <div class="alert alert-secondary py-2 px-3 mb-2" role="alert">
        Hàng đã hoàn về kho, tồn kho đã được cộng lại.
    </div>
@elseif ($order->order_delivered_at && $order->isAwaitingReceipt())
    <div class="alert alert-info py-2 px-3 mb-2" role="alert">
        Đã giao hàng, chờ khách xác nhận. Tự hoàn thành vào
        {{ $order->order_delivered_at->copy()->addDays(app(\App\Services\ShopSettings::class)->autoCompleteDays())->format('H:i d/m/Y') }}
        nếu khách không bấm.
    </div>
@endif

{{-- An order with a parcel keeps its panel whatever happened afterwards: the
     code is how the shop finds the kiện hàng again. --}}
@if (! $order->isConfirmed() && ! $order->order_shipping_code && ! $order->isHandedOverManually())
    <input type="text" class="form-control" readonly value="Chưa có" />
    <small class="text-muted fst-italic d-block mt-1">
        Chỉ đơn hàng đã xác nhận mới gắn được vận đơn.
    </small>
@elseif ($order->isHandedOverManually())
    <input type="text" class="form-control" readonly value="Đã bàn giao cho đơn vị vận chuyển của shop" />
    <small class="text-muted fst-italic d-block mt-1">
        Đơn này giao thủ công nên không tạo vận đơn GHN. Đơn hoàn thành khi khách bấm đã nhận được hàng.
    </small>
    <button class="btn btn-outline-warning btn-sm mt-2" type="submit" form="form-huy-ban-giao">Huỷ bàn giao</button>
    <small class="text-muted fst-italic d-block mt-1">
        Bỏ đánh dấu đã bàn giao, để chọn lại cách giao cho đơn này.
    </small>
@elseif (! $order->order_shipping_code)
    @if ($batTaoVanDon)
        <button class="btn btn-primary" type="submit" form="form-tao-van-don">Tạo vận đơn GHN</button>
    @else
        <small class="text-muted fst-italic d-block">
            Tạo vận đơn tự động đang tắt (GHN_CREATE_ORDERS).
        </small>
    @endif

    {{-- The other way in: a parcel the shop created on GHN's own dashboard only
         needs its code tying to this order. --}}
    <details class="mt-3" @if ($moSuaMa) open @endif>
        <summary class="text-muted" style="cursor: pointer; font-size: .8125rem;">
            Hoặc gắn mã vận đơn đã tạo sẵn trên GHN
        </summary>
        @include('backend.pages.order.partials.shipping_code_form', ['order' => $order])
    </details>
@else
    <input type="text" class="form-control" readonly
           value="{{ $order->order_expected_delivery
               ? 'Dự kiến giao: '.\Carbon\Carbon::parse($order->order_expected_delivery)->format('d/m/Y')
               : 'Hãng vận chuyển chưa báo ngày giao' }}" />

    <details class="mt-2" @if ($moSuaMa) open @endif>
        <summary class="text-muted" style="cursor: pointer; font-size: .8125rem;">
            Sửa mã vận đơn
        </summary>
        @include('backend.pages.order.partials.shipping_code_form', ['order' => $order])
        <small class="text-muted fst-italic d-block mt-1">
            Để trống rồi bấm Lưu nếu muốn gỡ mã khỏi đơn này.
        </small>
    </details>
@endif
