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

@if (! $order->isConfirmed())
    <input type="text" class="form-control" readonly
           value="{{ $order->order_shipping_code ?: 'Chưa có' }}" />
    <small class="text-muted fst-italic d-block mt-1">
        Chỉ đơn hàng đã xác nhận mới gắn được vận đơn.
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
