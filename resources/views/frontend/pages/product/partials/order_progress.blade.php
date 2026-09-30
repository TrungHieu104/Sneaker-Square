@php
    use App\Enums\OrderStatus;

    $thoiGian = fn ($luc) => $luc ? \Illuminate\Support\Carbon::parse($luc)->format('H:i d/m/Y') : null;
    $hoanThanh = $order->hasStatus(OrderStatus::Completed);
    $daGiao = $hoanThanh || $order->order_delivered_at || $order->order_shipping_status === 'delivered';
    $dangGiao = (int) $order->order_delivery_status === 1;

    // Cash on delivery has nothing to pay at this point, so its second step
    // is the shop taking the order on instead.
    if ($order->isCashOnDelivery()) {
        $buocHai = $order->order_status->isAccepted()
            ? ['done', 'bx-check-shield', 'Đã xác nhận', 'Thanh toán khi nhận hàng']
            : ['current', 'bx-check-shield', 'Chờ xác nhận', 'Thanh toán khi nhận hàng'];
    } elseif ((int) $order->order_payment_status === 1 || $hoanThanh) {
        $buocHai = ['done', 'bx-credit-card', 'Đã thanh toán', $thoiGian($order->order_payment_time)];
    } else {
        $buocHai = ['current warn', 'bx-credit-card', 'Chờ thanh toán', null];
    }
    $buocHaiXong = $buocHai[0] === 'done';
    $choThanhToan = $buocHai[0] === 'current warn';

    $buocBa = match (true) {
        $order->isBeingReturned() => ['current', 'bxs-truck', 'Đang hoàn hàng'],
        (bool) $daGiao => ['done', 'bxs-truck', 'Đã giao hàng'],
        $dangGiao => ['current', 'bxs-truck', 'Đang giao hàng'],
        $buocHaiXong || $order->order_status->isAccepted() => ['current', 'bx-package', 'Đang chuẩn bị hàng'],
        default => ['todo', 'bx-package', 'Chuẩn bị hàng'],
    };

    if ($hoanThanh) {
        $buocBon = ['done', 'Đã nhận hàng', $thoiGian($order->order_completed_at)];
    } elseif ($daGiao) {
        $buocBon = ['current', 'Chờ bạn xác nhận', $order->order_delivered_at ? 'Đã giao '.$thoiGian($order->order_delivered_at) : null];
    } else {
        $buocBon = ['todo', 'Nhận hàng', $order->order_expected_delivery
            ? 'Dự kiến '.\Illuminate\Support\Carbon::parse($order->order_expected_delivery)->format('d/m/Y')
            : null];
    }
@endphp

<ol class="order-steps" aria-label="Tiến trình đơn hàng">
    <li class="order-step done">
        <div class="order-step-dot"><i class="bx bx-receipt"></i></div>
        <div class="order-step-label">Đã đặt hàng</div>
        <div class="order-step-meta">{{ $thoiGian($order->created_at) }}</div>
    </li>

    <li class="order-step {{ $buocHai[0] }}" @if (! $buocHaiXong) aria-current="step" @endif>
        <div class="order-step-dot"><i class="bx {{ $buocHai[1] }}"></i></div>
        <div class="order-step-label">{{ $buocHai[2] }}</div>
        <div class="order-step-meta">
            @if ($choThanhToan)
                @php $thanhToan = app(\App\Services\Payment\OrderPayments::class); @endphp
                @if ($thanhToan->canPay($order))
                    @if ($han = $thanhToan->deadlineFor($order))
                        Hạn {{ $han->format('H:i') }} ·
                    @endif
                    <form action="{{ route('order.pay', $order->order_code) }}" method="post" class="d-inline">
                        @csrf
                        <button type="submit" class="pay-now-link order-step-pay">Thanh toán ngay</button>
                    </form>
                @else
                    <span class="text-danger">Đã quá hạn thanh toán</span>
                @endif
            @else
                {{ $buocHai[3] }}
            @endif
        </div>
    </li>

    <li class="order-step {{ $buocBa[0] }}" @if ($buocBa[0] === 'current') aria-current="step" @endif>
        <div class="order-step-dot"><i class="bx {{ $buocBa[1] }}"></i></div>
        <div class="order-step-label">{{ $buocBa[2] }}</div>
        <div class="order-step-meta">
            @if ($buocBa[0] !== 'todo')
                <a href="#" data-bs-toggle="modal" data-bs-target="#HanhTrinhModal">Xem hành trình</a>
            @endif
        </div>
    </li>

    <li class="order-step {{ $buocBon[0] }}" @if ($buocBon[0] === 'current') aria-current="step" @endif>
        <div class="order-step-dot"><i class="bx bx-home-heart"></i></div>
        <div class="order-step-label">{{ $buocBon[1] }}</div>
        <div class="order-step-meta">{{ $buocBon[2] }}</div>
    </li>
</ol>
