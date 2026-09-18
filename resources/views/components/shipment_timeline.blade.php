{{--
    A parcel's history, newest at the top.

    @param \App\Models\OrderModel $order
--}}
@php
    use App\Services\Shipping\GhnStatus;

    // values() because sortByDesc keeps the original keys, and the loop needs
    // position to know which step is the current one.
    $events = $order->currentShipmentEvents()->sortByDesc('happened_at')->values();
    $truocDo = $order->previousShipments();
@endphp

@once
    {{-- Inline rather than pushed to a stack: this block is rendered in both
         the admin layout and the storefront one, and they publish different
         stack names. --}}
    <style>
            .ship-line { position: relative; padding-left: 26px; }
            .ship-line::before {
                content: '';
                position: absolute;
                left: 6px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e4e4e7;
            }
            .ship-step { position: relative; padding-bottom: 18px; }
            .ship-step:last-child { padding-bottom: 0; }
            .ship-step::before {
                content: '';
                position: absolute;
                left: -24px;
                top: 4px;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #c7c7cc;
                box-shadow: 0 0 0 3px #fff;
            }
            .ship-step--now::before { background: #ff6b00; }
            .ship-step--done::before { background: #22c55e; }
            .ship-step--fail::before { background: #ef4444; }
            .ship-step__label { font-weight: 600; }
            .ship-step__meta { font-size: .8125rem; color: #6b7280; }
            .ship-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 12px;
            }
            .ship-past { margin-top: 14px; border-top: 1px solid #e4e4e7; padding-top: 12px; }
            .ship-past > summary { cursor: pointer; font-size: .8125rem; color: #6b7280; }
            .ship-past__parcel { margin-top: 12px; opacity: .7; }
            .ship-past__code { font-size: .8125rem; font-weight: 600; margin-bottom: 6px; }
    </style>
@endonce

<div class="shipment-timeline">
    @if ($order->order_shipping_code)
        <div class="ship-head">
            <span>
                Mã vận đơn <b>{{ $order->order_shipping_code }}</b>
                @if ($order->order_shipping_status)
                    &middot; {{ GhnStatus::label($order->order_shipping_status) }}
                @endif
            </span>
            {{-- The storefront renders this same block and is handed no form to
                 point at, so it gets no way to call the parcel back. A parcel
                 that has already arrived cannot be called back either. --}}
            @if (($formHuy ?? null) && ! GhnStatus::isFinal((string) $order->order_shipping_status))
                <button class="btn btn-sm btn-outline-danger" type="submit" form="{{ $formHuy }}">Huỷ vận đơn</button>
            @endif
        </div>
    @endif

    @if ($events->isEmpty())
        <p class="text-muted fst-italic mb-0">
            @if ($order->order_shipping_code)
                Chưa có cập nhật nào từ đơn vị vận chuyển.
            @else
                Đơn hàng chưa được bàn giao cho đơn vị vận chuyển.
            @endif
        </p>
    @else
        <div class="ship-line">
            @foreach ($events as $index => $event)
                @php
                    $modifier = match (true) {
                        GhnStatus::isFailure($event->status) => 'ship-step--fail',
                        $event->status === 'delivered' => 'ship-step--done',
                        $index === 0 => 'ship-step--now',
                        default => '',
                    };
                @endphp
                <div class="ship-step {{ $modifier }}">
                    <div class="ship-step__label">{{ GhnStatus::label($event->status) }}</div>
                    <div class="ship-step__meta">
                        {{ $event->happened_at->format('H:i d/m/Y') }}
                        @if ($event->warehouse)
                            &middot; {{ $event->warehouse }}
                        @endif
                    </div>
                    @if ($event->description)
                        <div class="ship-step__meta">{{ $event->description }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Shown only where it is asked for: a customer has no use for the parcels
         the shop cancelled before this one. --}}
    @if (($hienVanDonCu ?? false) && $truocDo->isNotEmpty())
        <details class="ship-past">
            <summary>{{ $truocDo->count() }} vận đơn trước đó</summary>

            @foreach ($truocDo as $maVanDon => $cacChang)
                <div class="ship-past__parcel">
                    <div class="ship-past__code">
                        {{ $maVanDon }} &middot; {{ GhnStatus::label($cacChang->first()->status) }}
                    </div>
                    <div class="ship-line">
                        @foreach ($cacChang as $event)
                            <div class="ship-step">
                                <div class="ship-step__label">{{ GhnStatus::label($event->status) }}</div>
                                <div class="ship-step__meta">
                                    {{ $event->happened_at->format('H:i d/m/Y') }}
                                    @if ($event->warehouse)
                                        &middot; {{ $event->warehouse }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </details>
    @endif
</div>
