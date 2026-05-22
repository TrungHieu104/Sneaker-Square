@extends('backend.index')

@section('title')
    Chi tiết đơn hàng
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('order.index')}}" class="tab-back">Đơn hàng / </a></span> Chi tiết đơn hàng</h4>
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
                        @if($order->order_status == 0)
                            <input type="hidden" name="status" value="1">
                            <input type="hidden" name="pay" value="{{now()}}">
                            <button type="submit" class="btn btn-success px-5 text-white">Xác nhận</button>
                            <a href="{{ route('order.print', ['encryptedOrderId' => encrypt($order->order_id)]) }}" class="btn btn-primary px-5 text-white" target="_blank">In hóa đơn</a>
                        @elseif($order->order_status == 3)
                            <input type="hidden" name="status" value="2">
                            <button type="submit" class="btn btn-danger px-5 text-white">Xác nhận hoàn tiền</button>
                            <a href="{{ route('order.print', ['encryptedOrderId' => encrypt($order->order_id)]) }}" class="btn btn-primary px-5 text-white" target="_blank">In hóa đơn</a>
                        @elseif($order->order_status == 1 && $order->order_delivery_status == 0)
                            <input type="hidden" name="deli" value="1">
                            <input type="hidden" name="status" value="1">
                            <button type="submit" class="btn btn-warning px-5 text-white">Bàn giao vận chuyển</button>
                            <a href="{{ route('order.print', ['encryptedOrderId' => encrypt($order->order_id)]) }}" class="btn btn-primary px-5 text-white" target="_blank">In hóa đơn</a>
                        @else
                            <a href="{{route('order.index')}}" class="btn px-5 text-white btn-warning"><i class='bx bx-arrow-back' ></i></a>
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
                            $orderStatus = '';
                            switch ($order->order_status) {
                                case 0:
                                    $orderStatus = 'Đơn hàng mới';
                                    break;
                                case 1:
                                    if ($order->order_delivery_status == 1) {
                                        $orderStatus = 'Vận chuyển';
                                    } else {
                                        $orderStatus = 'Đã xử lý';
                                    }
                                    break;
                                case 2:
                                    $orderStatus = 'Đã hủy';
                                    break;
                                case 3:
                                    $orderStatus = 'Hoàn hàng';
                                    break;
                                case 10:
                                    $orderStatus = 'Thành công';
                                    break;
                                default:
                                    $orderStatus = 'Không xác định';
                            }
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
@endsection
