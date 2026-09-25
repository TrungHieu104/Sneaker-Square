{{--
    Every return the customer has opened on this order, newest first.

    Folded: the shop only ever has work to do on one of them, so that one is
    open and the settled ones stay one line each until someone asks.

    Rendered outside the order form: every action here is a form of its own.

    @param \App\Models\OrderModel $order
--}}
@php
    use App\Models\OrderReturnModel;
    use App\Services\Returns\OrderReturns;
    use App\Services\Shipping\GhnStatus;

    $mauTrangThai = [
        OrderReturnModel::REQUESTED => 'warning',
        OrderReturnModel::APPROVED => 'info',
        OrderReturnModel::RECEIVED => 'primary',
        OrderReturnModel::REFUNDED => 'success',
        OrderReturnModel::REJECTED => 'secondary',
        OrderReturnModel::CANCELLED => 'secondary',
    ];

    $danhSach = $order->orderReturns;
    $tong = $danhSach->count();
    $dangMo = $danhSach->first(fn (OrderReturnModel $r) => $r->isOpen())?->return_id;
@endphp

<div class="card mb-4 card-border-top" id="yeu-cau-tra-hang">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fs-4 text-primary my-0">Yêu cầu trả hàng</h5>
            <span class="badge bg-label-dark fs-6">{{ $tong }} lần</span>
        </div>

        <div class="accordion accordion-flush" id="dsYeuCauTra">
            @foreach ($danhSach as $return)
                @php
                    $dongTra = $return->items()->with('line')->get();
                    $traMotPhan = $return->isPartial();
                    $tienPhaiHoan = $return->refundDue();
                    $moRong = $return->return_id === $dangMo;
                    $hanhTrinhTra = $return->return_shipping_code
                        ? $order->shipmentEvents->where('shipping_code', $return->return_shipping_code)->sortByDesc('happened_at')
                        : collect();
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $moRong ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#tra-hang-{{ $return->return_id }}"
                            aria-expanded="{{ $moRong ? 'true' : 'false' }}">
                            <span class="d-flex align-items-center gap-3 flex-wrap w-100 pe-3">
                                {{-- Counted from the oldest, so a request keeps its
                                     number when the customer sends the next one. --}}
                                <b>Lần {{ $tong - $loop->index }}</b>
                                <span class="text-muted">{{ $return->created_at->format('H:i d/m/Y') }}</span>
                                <span>
                                    {{ $traMotPhan ? 'Trả một phần' : 'Trả toàn bộ' }}
                                    &middot; {{ $dongTra->sum('quantity') }} sản phẩm
                                    &middot; {{ number_format($tienPhaiHoan, 0, ',', '.') }}đ
                                </span>
                                <span class="badge bg-label-{{ $mauTrangThai[$return->status] ?? 'secondary' }} ms-auto">
                                    {{ $return->statusLabel() }}
                                </span>
                            </span>
                        </button>
                    </h2>

                    <div id="tra-hang-{{ $return->return_id }}"
                        class="accordion-collapse collapse {{ $moRong ? 'show' : '' }}" data-bs-parent="#dsYeuCauTra">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="mb-2"><b>Lý do:</b> {{ $return->reasonLabel() }}</div>
                                    @if ($return->description)
                                        <div class="mb-2"><b>Mô tả:</b> {{ $return->description }}</div>
                                    @endif

                                    <table class="table table-sm mb-2">
                                        <thead>
                                            <tr><th>Sản phẩm khách gửi trả</th><th class="text-center">SL trả / đã mua</th><th class="text-end">Thành tiền</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dongTra as $muc)
                                                <tr>
                                                    <td>{{ $muc->line?->pro_name }} <span class="text-muted">{{ $muc->line?->size }} / {{ $muc->line?->color }}</span></td>
                                                    <td class="text-center">{{ $muc->quantity }} / {{ $muc->line?->quantity }}</td>
                                                    <td class="text-end">{{ number_format($muc->lineTotal(), 0, ',', '.') }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="mb-2">
                                        <b>Tiền phải hoàn:</b> {{ number_format($tienPhaiHoan, 0, ',', '.') }}đ
                                        <span class="text-muted small">
                                            (đã trừ phần mã giảm giá của các dòng này{{ $traMotPhan ? ', không hoàn phí vận chuyển vì đơn vẫn được giao' : ', gồm cả phí vận chuyển' }})
                                        </span>
                                    </div>

                                    @if ($anh = OrderReturns::imageUrls($return))
                                        <div class="d-flex gap-2 flex-wrap mt-2">
                                            @foreach ($anh as $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener">
                                                    <img src="{{ $url }}" alt="Ảnh trả hàng" class="rounded border" style="width: 96px; height: 96px; object-fit: cover;">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="col-lg-6 mb-3">
                                    @switch($return->status)
                                        @case(OrderReturnModel::REQUESTED)
                                            <form action="{{ route('returns.approve', $order->order_id) }}" method="POST" class="mb-3">
                                                @csrf
                                                <button type="submit" class="btn btn-success">Duyệt yêu cầu</button>
                                            </form>
                                            <form action="{{ route('returns.reject', $order->order_id) }}" method="POST">
                                                @csrf
                                                <label for="reject-reason" class="form-label">Hoặc từ chối, kèm lý do gửi khách:</label>
                                                <textarea class="form-control mb-2" id="reject-reason" name="reject_reason" rows="2">{{ old('reject_reason') }}</textarea>
                                                @error('reject_reason') <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror
                                                <button type="submit" class="btn btn-outline-danger">Từ chối</button>
                                            </form>
                                            @break

                                        @case(OrderReturnModel::APPROVED)
                                            @if ($return->return_shipping_code)
                                                <div class="mb-2">
                                                    Vận đơn trả hàng <b>{{ $return->return_shipping_code }}</b>
                                                    @if ($return->return_shipping_status)
                                                        &middot; {{ GhnStatus::label($return->return_shipping_status) }}
                                                    @endif
                                                </div>
                                                @if ($hanhTrinhTra->isNotEmpty())
                                                    <ul class="list-unstyled small text-muted mb-3">
                                                        @foreach ($hanhTrinhTra as $chang)
                                                            <li>{{ $chang->happened_at->format('H:i d/m/Y') }} &middot; {{ GhnStatus::label($chang->status) }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif

                                                <div class="d-flex gap-2 flex-wrap mb-3">
                                                    <form action="{{ route('returns.cancel_shipment', $order->order_id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Huỷ vận đơn trả hàng</button>
                                                    </form>
                                                    <form action="{{ route('returns.detach_shipment', $order->order_id) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">Gỡ mã vận đơn</button>
                                                    </form>
                                                </div>
                                                <div class="form-text mb-3">
                                                    Huỷ vận đơn sẽ báo GHN huỷ rồi nhả mã ra để tạo lại.
                                                    Gỡ mã chỉ xoá mã khỏi yêu cầu này, <b>không huỷ gì ở GHN</b> —
                                                    chỉ dùng khi vận đơn đã được huỷ sẵn trên trang GHN.
                                                </div>
                                            @else
                                                @if (config('services.ghn.create_orders'))
                                                    <form action="{{ route('returns.book', $order->order_id) }}" method="POST" class="mb-2">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary">Tạo vận đơn trả hàng GHN</button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('returns.shipping_code', $order->order_id) }}" method="POST" class="mb-3">
                                                    @csrf @method('PATCH')
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="return_shipping_code"
                                                            placeholder="Hoặc gắn mã đã tạo trên GHN" value="{{ old('return_shipping_code') }}">
                                                        <button class="btn btn-outline-primary" type="submit">Lưu</button>
                                                    </div>
                                                    @error('return_shipping_code') <small class="text-danger">{{ $message }}</small> @enderror
                                                </form>
                                            @endif

                                            <form action="{{ route('returns.receive', $order->order_id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-success">Đã nhận và kiểm tra hàng trả</button>
                                                <div class="form-text">Chỉ cộng lại tồn kho đúng số lượng khách gửi trả ở bảng bên trái.</div>
                                            </form>
                                            @break

                                        @case(OrderReturnModel::RECEIVED)
                                            <div class="mb-2 text-muted small">Nhận hàng lúc {{ $return->received_at->format('H:i d/m/Y') }}</div>
                                            <form action="{{ route('returns.refund', $order->order_id) }}" method="POST">
                                                @csrf
                                                <label for="refund-amount" class="form-label">Số tiền hoàn vào ví khách (đ)</label>
                                                <input type="number" class="form-control mb-2" id="refund-amount" name="refund_amount"
                                                    min="0" max="{{ $tienPhaiHoan }}"
                                                    value="{{ old('refund_amount', $tienPhaiHoan) }}">
                                                @error('refund_amount') <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror
                                                <button type="submit" class="btn btn-success">Hoàn tiền vào ví khách</button>
                                                <div class="form-text">
                                                    Tiền vào ví khách ngay; khách tự dùng để mua đơn khác hoặc gửi yêu cầu rút về ngân hàng.
                                                    Doanh thu của {{ $traMotPhan ? 'các dòng được trả' : 'đơn' }} sẽ được trừ khỏi thống kê.
                                                </div>
                                            </form>
                                            @break

                                        @case(OrderReturnModel::REFUNDED)
                                            <div class="mb-1">Đã hoàn <b>{{ number_format((int) $return->refund_amount, 0, ',', '.') }}đ</b> vào ví khách</div>
                                            <div class="text-muted small">lúc {{ $return->refunded_at->format('H:i d/m/Y') }}</div>
                                            @break

                                        @case(OrderReturnModel::REJECTED)
                                            <div><b>Lý do từ chối:</b> {{ $return->reject_reason }}</div>
                                            @break

                                        @case(OrderReturnModel::CANCELLED)
                                            <div class="text-muted">Khách đã huỷ yêu cầu này lúc {{ $return->decided_at?->format('H:i d/m/Y') }}.</div>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
