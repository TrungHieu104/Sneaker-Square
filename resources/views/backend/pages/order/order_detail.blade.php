@extends('backend.index')

@section('title')
    Chi tiết đơn hàng
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('order.index')}}" class="tab-back">Đơn hàng / </a></span> Chi tiết đơn hàng</h4>
    @if ($order->hasStatus(\App\Enums\OrderStatus::CancelRequested))
        @include('backend.pages.order.partials.cancel_request_panel', ['order' => $order])
    @endif
    @if ($order->orderReturn)
        @include('backend.pages.order.partials.return_panel', ['order' => $order, 'return' => $order->orderReturn])
    @endif
    <!-- Bordered Table -->
    <form action="/admin/order/{{$order->order_id}}" method="post">
        @csrf {{method_field('PUT')}}
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Thông tin người nhận</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        @if($order->hasStatus(\App\Enums\OrderStatus::New))
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="btn btn-success px-5 text-white">Xác nhận</button>
                        @elseif($order->hasStatus(\App\Enums\OrderStatus::Returned) && ! $order->orderReturn)
                            <input type="hidden" name="action" value="refund">
                            <button type="submit" class="btn btn-danger px-5 text-white">Xác nhận hoàn tiền</button>
                        {{-- Handing the parcel over by hand and sending it through GHN are the
                             two ways out of the warehouse, and taking one closes the other. --}}
                        @elseif($order->hasStatus(\App\Enums\OrderStatus::Confirmed) && ! $order->usesGhn())
                            <input type="hidden" name="action" value="handover">
                            <button type="submit" class="btn btn-warning px-5 text-white">Bàn giao vận chuyển</button>
                        @else
                            <a href="{{route('order.index')}}" class="btn px-5 text-white btn-warning"><i class='bx bx-arrow-back' ></i></a>
                        @endif
                        @if(! $order->order_status->isFinal() || $order->hasStatus(\App\Enums\OrderStatus::Returned))
                            <a href="{{ route('order.print', ['encryptedOrderId' => encrypt($order->order_id)]) }}" class="btn btn-primary px-5 text-white" target="_blank">In hóa đơn</a>
                        @endif
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="path" class="form-label">Mã đơn hàng</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_code}}"/>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_name}}"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="path" class="form-label">Email</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_email}}"/>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_phone}}"/>
                    </div>   
                </div>
                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <label for="path" class="form-label">Vị trí nhận hàng</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_local}}"/>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <label for="path" class="form-label">Địa chỉ nhận hàng</label>
                        <input type="text" class="form-control" readonly value="{{$order->order_address}}"/>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="content" class="form-label">Phương thức thanh toán: 
                        </label>
                        <input type="text" class="form-control" readonly value="@if ($order->order_payment == 'cod') Thanh toán khi nhận hàng @else @if ($order->order_payment == 'redirect')Thanh toán qua VNPay @else Thanh toán qua Momo @endif @endif" />                                                                                 
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="content" class="form-label">Trạng thái: 
                        </label>
                        {{-- <input type="text" class="form-control" readonly value="{{$order->order_payment_status == 0 ? "Chưa thanh toán" : "Đã thanh toán ".(date('d-m-Y H:m:s', strtotime($order->order_payment_time)))}}" />--}}
                        @php
                            // A returning order says more when it also says how far the
                            // customer's own request has got.
                            $orderStatus = $order->orderReturn
                                ? $order->order_status->label().' · trả hàng: '.$order->orderReturn->statusLabel()
                                : $order->order_status->label();
                        @endphp
                        <input type="text" class="form-control" readonly value="{{ $orderStatus }}" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="content" class="form-label">Vận chuyển: </label>
                        <input type="text" class="form-control" readonly value="{{$order->order_delivery_status == 0 ? "Giao hàng nhanh" : "Đã bàn giao cho đơn vị vận chuyển"}}"/>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="content" class="form-label">Thời gian đặt hàng: </label>
                        <input type="text" class="form-control" readonly value="{{ date('d-m-Y', strtotime($order->order_date))}}"/>
                    </div>
                </div>
                <div class="row">
                    @php
                        $batTaoVanDon = config('services.ghn.create_orders');
                    @endphp
                    <div class="col-lg-6 mb-3">
                        <label for="shipping-code" class="form-label">Vận đơn GHN:</label>
                        <div id="khoi-van-don">
                            @include('backend.pages.order.partials.shipping_actions', ['order' => $order])
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Hành trình vận đơn:</label>
                        <div class="border rounded p-3" id="hanh-trinh-van-don"
                             data-stream="{{ route('order.shipment_stream', $order->order_id) }}">
                            @include('components.shipment_timeline', [
                                'order' => $order,
                                'formHuy' => $batTaoVanDon ? 'form-huy-van-don' : null,
                                'hienVanDonCu' => true,
                            ])
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="content" class="form-label mt-1">Ghi chú của khách hàng: </label>
                        <textarea class="form-control" name="" id="" cols="30" rows="5" readonly>{{$order->note_customer}}</textarea>
                    </div>
                </div>
                
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header card-border-top">
                Chi tiết đơn hàng #{{$order->order_code}}
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-bordered w-100">
                        <thead class="text-center">
                            <tr>
                                <th>STT</th>
                                <th>Tên sản phẩm</th>
                                <th>Size</th>
                                <th>Màu sắc</th>
                                <th>Giá</th >
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $stt=1;
                            @endphp
                                @foreach($orderDetail as $detail)                              
                                    <tr>
                                        <input type="hidden" name="order_product_id[]" value="{{$detail->product->pro_id}}">
                                        <input type="hidden" name="quantity[]" value="{{$detail->quantity}}">
                                        <td class="text-center">{{$stt}}</td>
                                        <td class="pro-name">
                                            {{ $detail->product->pro_name }}
                                        </td>
                                        <td class="text-center">
                                            @if($detail->size == 0)

                                            @else
                                                {{ $detail->size }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{-- @if($detail->color=='Đen')
                                                <div title="Đen" class="centered rounded-circle bg-dark">
                                                </div>
                                            @elseif($detail->color == 'Trắng')
                                                <div title="Trắng" class="colorwhite rounded-circle">
                                                </div>
                                            @elseif($detail->color == 'Đỏ')
                                                <div title="Đỏ" class="colorred rounded-circle">
                                                </div>
                                            @elseif($detail->color == 'Xanh dương')
                                                <div title="Xanh dương" class="colorblue rounded-circle">
                                                </div>
                                            @elseif($detail->color == 'Xám')
                                                <div title="Xám" class="colorgray rounded-circle">
                                                </div>
                                            @elseif($detail->color == 6)
                                                <div title="Hồng" class="colorpink rounded-circle">
                                                </div>
                                            @elseif($detail->color == 7)
                                                <div title="Cam" class="colororange rounded-circle">
                                                </div>
                                            @endif --}}
                                            {{$detail->color}}
                                        </td>
                                        <td class="text-center">{{number_format($detail->price,0,',','.')}}đ</td>
                                        <td class="text-center">{{ $detail->quantity }}</td>
                                        <td class="text-center">{{number_format($detail->price * $detail->quantity,0,',','.')}}đ</td>
                                    </tr>
                                    @php
                                        $stt++;
                                    @endphp
                                @endforeach                      
                        </tbody>
                    </table>
                </div>
                <div class="mb-3 mt-3">
                    <span>Phí vận chuyển: {{number_format($order->order_delivery_fee,0,',','.')}}đ<br></span>
                    @if ($order->Coupon)
                        <span>Mã giảm giá: {{ $order->Coupon->coupon_code }}<br></span>
                        <span class="d-flex">Số tiền giảm: {{ number_format($order->order_coupon_value, 0, ',', '.') }}đ</p></span> <br>
                    @else
                        <span>Mã giảm giá: Không áp dụng<br></span><br>
                        {{-- <span class="d-flex">Số tiền giảm: 0đ</p></span> --}}
                    @endif
                        <input type="hidden" value={{$order->order_coupon_value}} name="cou_val">
                    <span class="d-flex">Tổng tiền:&nbsp; <p class="text-danger">{{number_format($order->order_total,0,',','.')}}đ</p></span>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header card-border-top">
                Ghi chú đơn hàng
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="mb-3">
                        
                        <textarea class="form-control" name="note" id="" cols="30" rows="5">{{$order->note_admin}}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header card-border-top">
                Thông tin tài khoản
            </div>
            @if($order->User)
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="path" class="form-label">Tên khách hàng</label>
                        <input type="text" class="form-control" readonly value="{{$order->User->name}}"/>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Email</label>
                        <input type="text" class="form-control" readonly value="{{$order->User->email}}"/>
                    </div>
                </div>
            </div>
            @else
                <p>Không tồn tại thông tin khách hàng</p>
            @endif
        </div>
    </form>

    {{-- Declared out here on purpose. A <form> inside another <form> is dropped
         by the browser: its fields join the outer form and its submit button
         posts that one instead. --}}
    @if ($order->isConfirmed())
        <form id="form-ma-van-don" action="{{ route('order.shipping_code', $order->order_id) }}" method="POST">
            @csrf {{ method_field('PATCH') }}
        </form>

        <form id="form-huy-ban-giao" action="{{ route('order.undo_handover', $order->order_id) }}" method="POST">
            @csrf
        </form>

        @if (! $order->order_shipping_code && $batTaoVanDon)
            <form id="form-tao-van-don" action="{{ route('order.book_shipment', $order->order_id) }}" method="POST">
                @csrf
            </form>
        @endif
    @endif

    @if ($order->order_shipping_code && $batTaoVanDon)
        <form id="form-huy-van-don" action="{{ route('order.cancel_shipment', $order->order_id) }}" method="POST">
            @csrf
        </form>
    @endif
@endsection

@push('script-backend')
    <script>
        (function () {
            const khoi = document.getElementById('hanh-trinh-van-don');
            const khoiVanDon = document.getElementById('khoi-van-don');

            if (!khoi || !khoiVanDon || typeof EventSource === 'undefined') {
                return;
            }

            // EventSource reconnects on its own when the stream reaches its time
            // limit, so there is nothing here to schedule or retry.
            const nguon = new EventSource(khoi.dataset.stream);

            nguon.addEventListener('hanhtrinh', function (su) {
                const moi = JSON.parse(su.data);
                const dangMo = khoi.querySelector('.ship-past')?.open;

                khoi.innerHTML = moi.hanhtrinh;

                const cu = khoi.querySelector('.ship-past');
                if (cu && dangMo) {
                    cu.open = true;
                }

                // Never yank the field out from under someone typing a code
                // into it; the next event repaints it once they move away.
                if (!khoiVanDon.contains(document.activeElement)) {
                    khoiVanDon.innerHTML = moi.vandon;
                }
            });

            window.addEventListener('beforeunload', function () {
                nguon.close();
            });
        })();
    </script>
@endpush
