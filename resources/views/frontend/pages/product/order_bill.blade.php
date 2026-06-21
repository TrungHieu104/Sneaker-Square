@extends('frontend.index')

@section('title')
    Thông tin đơn hàng {{ $order->order_code }}
@endsection

@section('banner')
    <!-- Start banner Area -->
    
    <!-- End banner Area -->
@endsection

@section('content')
    <style>
        .progress-bar {

            background-color: var(--color-success);
        }

        .h-100 {
            height: 100% !important;
        }

        .w-h-2rem {
            width: 2rem !important;
            height: 2rem !important;
        }

        .w-progress-bar {
            width: 90%;
            height: 150px;
        }

        .w-text-130px {
            width: 130px;
        }

        @media screen and (max-width:1290px) {
            .w-progress-bar {
                width: 85%;
            }
        }

        @media screen and (max-width:768px) {
            .mb-rps-dfl {
                display: block !important;
            }

            .mb-rps-dfl .form-floating {
                margin-bottom: 20px !important;
            }
        }

        @media screen and (max-width:680px) {
            .w-progress-bar {
                font-size: .7rem !important;
            }

            .mx-5 {
                margin-left: 2rem !important;
                margin-right: 2rem !important;
            }

            .mb-rps-dfl {
                display: block !important;
            }

            .mb-rps-dfl .form-floating {
                margin-bottom: 20px !important;
            }

            .rps-back {
                left: 8%;
                top: 0% !important;
            }
        }

        @media screen and (max-width:376px) {
            .w-progress-bar {
                width: 90%;
            }

            .mx-5 {
                margin-left: .5rem !important;
                margin-right: .5rem !important;
            }

            .l-progress-bar-1 {
                margin-top: 0px !important;
                left: 0% !important;
                top: -80px;
            }

            .l-progress-bar-2 {
                margin-top: 10px;
                margin-left: 20px;
                left: 0%;
                left: 0px;
                right: 0px;
                width: 110px !important;
            }

            .l-progress-bar-3 {
                left: -60%;
                text-align: center;
                top: -75px;
            }

            .w-text-130px {
                width: 120px;
            }
        }
    </style>
    <!-- Start Content Area -->
    <section class="container-fluid p-0 overflow-hidden">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center position-relative">
                <div class="col-lg-6 text-center" data-aos="fade-right" data-aos-duration="2000">
                    <div class="section-title">
                        <h1>Thông tin đơn hàng</h1>
                        <p>
                            Cảm ơn vì đã tin tưởng lựa chọn sản phẩm tại cửa hàng.
                            Sản phẩm sẽ được chúng tôi giao tới cho bạn sớm nhất!
                        </p>
                    </div>
                </div>
            </div>
            <div class="container-fluid px-0">
                <div class="container-xxl box__shadow p-3 rounded">
                    <div class="row">
                        <div class="container-fluid mt-3 d-flex justify-content-center">
                            @if ($order->order_status == 2)
                                <h4 class="fw-bold mb-3">Đã hủy đơn</h4>
                            @else
                                @if ($order->order_status == 3)
                                    <h4 class="fw-bold mb-3">Đã gửi yêu cầu trả hàng</h4>
                                @else
                                    <div class="position-relative w-progress-bar">
                                        <div class="progress" role="progressbar" aria-label="Progress"
                                            aria-valuenow="@if ($order->order_status == 10) 100
                                @else
                                    @if ($order->order_delivery_status == 1 || $order->order_status == 1)
                                        50
                                    @else
                                        25 @endif
                                @endif"
                                            aria-valuemin="0" aria-valuemax="100" style="height: 2px;">
                                            <div class="progress-bar"
                                                style="width: @if ($order->order_status == 10) 100%
                                    @else
                                        @if ($order->order_delivery_status == 1 || $order->order_status == 1)
                                            65%
                                        @else
                                            30% @endif
                                    @endif">
                                            </div>
                                        </div>

                                        <div
                                            class="position-absolute top-0 start-0 translate-middle  bg-success rounded-pill w-h-2rem">
                                            <div
                                                class="text-white text-center d-flex justify-content-center align-items-center h-100">
                                                1
                                            </div>
                                            <div class="position-relative">
                                                <div class="position-absolute text-dark text-capitalize w-text-130px l-progress-bar-1"
                                                    style="margin-top:10px; left: -45%;">
                                                    ngày đặt hàng <br>
                                                    <sub>
                                                        {{ date('H:i d/m/Y', strtotime($order->created_at)) }}
                                                    </sub>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="position-absolute top-0 translate-middle w-h-2rem rounded-pill
                                    @if (
                                        ($order->order_payment == 'cod' && $order->order_status == 1) ||
                                            ($order->order_payment == 'cod' && $order->order_status == 10)) bg-success 
                                    @else
                                        @if ($order->order_payment == 'cod' && $order->order_status == 0)
                                            bg-secondary @endif
                                    @endif
                                    @if (
                                        ($order->order_payment == 'payUrl' && $order->order_payment_status == 1) ||
                                            ($order->order_payment == 'payUrl' && $order->order_status == 10)) bg-success 
                                    @else
                                        @if ($order->order_payment == 'payUrl' && $order->order_payment_status == 0)
                                            bg-secondary @endif
                                    @endif
                                    @if (
                                        ($order->order_payment == 'redirect' && $order->order_payment_status == 1) ||
                                            ($order->order_payment == 'redirect' && $order->order_status == 10)) bg-success 
                                    @else
                                        @if ($order->order_payment == 'redirect' && $order->order_payment_status == 0)
                                            bg-secondary @endif
                                    @endif
                                    "
                                            style="left:30%">
                                            <div
                                                class="text-white text-center d-flex justify-content-center align-items-center h-100">
                                                2
                                            </div>
                                            <div class="position-relative">

                                                <div class="position-absolute text-dark text-capitalize l-progress-bar-2 w-text-130px"
                                                    style="margin-top:10px; left: -15px;">
                                                    {{-- check status paymen COD --}}
                                                    @if (
                                                        ($order->order_payment == 'cod' && $order->order_status == 1) ||
                                                            ($order->order_payment == 'cod' && $order->order_status == 10))
                                                        Đã xác nhận thông tin thanh toán
                                                        <br>
                                                        <sub>
                                                            {{ date('H:i d/m/Y', strtotime($order->order_payment_time)) }}
                                                        </sub>
                                                    @else
                                                        @if ($order->order_payment == 'cod' && $order->order_status == 0)
                                                            Đang chờ xác nhận từ Sneaker Square
                                                        @endif
                                                    @endif
                                                    {{-- check status payment MOMO --}}
                                                    @if (
                                                        ($order->order_payment == 'payUrl' && $order->order_payment_status == 1) ||
                                                            ($order->order_payment == 'payUrl' && $order->order_status == 10))
                                                        Đã thanh toán Momo
                                                        <br>
                                                        <sub>
                                                            {{ date('H:i d/m/Y', strtotime($order->order_payment_time)) }}
                                                        </sub>
                                                    @else
                                                        @if ($order->order_payment == 'payUrl' && $order->order_payment_status == 0)
                                                            Chưa thanh toán Momo
                                                            <br>
                                                            <sub>
                                                                @if ($order->order_payment_url !== null)
                                                                    <a href="{{ $order->order_payment_url }}"
                                                                        target="_blank">Thanh
                                                                        toán
                                                                        ngay</a>
                                                                @else
                                                                    Không tìm thấy link thanh toán, Hãy tạo yêu cầu hủy đơn
                                                                @endif
                                                            </sub>
                                                        @endif
                                                    @endif

                                                    {{-- check status paymen VNP --}}
                                                    @if (
                                                        ($order->order_payment == 'redirect' && $order->order_payment_status == 1) ||
                                                            ($order->order_payment == 'redirect' && $order->order_status == 10))

                                                        Đã thanh toán VNPay
                                                        <br>
                                                        <sub>
                                                            {{ date('H:i d/m/Y', strtotime($order->order_payment_time)) }}
                                                        </sub>
                                                    @else
                                                        @if ($order->order_payment == 'redirect' && $order->order_payment_status == 0)
                                                            Chưa thanh toán VNPay
                                                            <br>
                                                            <sub>
                                                                @if ($order->order_payment_url !== null)
                                                                    <a href="{{ $order->order_payment_url }}"
                                                                        target="_blank">Thanh
                                                                        toán
                                                                        ngay</a>
                                                                @else
                                                                    Không tìm thấy link thanh toán, Hãy tạo yêu cầu hủy đơn
                                                                @endif
                                                            </sub>
                                                        @endif
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="position-absolute top-0 translate-middle rounded-pill w-h-2rem
                                        @if ($order->order_delivery_status == 1) bg-success
                                        @else bg-secondary @endif" style="left: 65%;">
                                                        <div
                                                class="text-white text-center d-flex justify-content-center align-items-center h-100">
                                                3
                                            </div>
                                            <div class="position-relative">

                                                <div class="position-absolute text-dark text-capitalize l-progress-bar-3 w-text-130px"
                                                    style="margin-top:10px; left: -10px;">
                                                    @if ($order->order_delivery_status == 1)
                                                        Đang vận chuyển<br>
                                                    @else
                                                        Đang chuẩn bị<br>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="position-absolute top-0 start-100 translate-middle rounded-pill w-h-2rem
                                    @if ($order->order_status == 10) bg-success
                                    @else
                                        bg-secondary @endif">
                                            <div
                                                class="text-white text-center d-flex justify-content-center align-items-center h-100">
                                                4
                                            </div>
                                            <div class="position-relative">

                                                <div class="position-absolute text-dark text-capitalize w-text-130px"
                                                    style="margin-top:10px; left: -90%;">
                                                    @if ($order->order_status == 10)
                                                        Đã nhận hàng<br>
                                                    @else
                                                        Đợi nhận hàng<br>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="vtrWey"></div>
                            <h4 class="text-center fw-bold mt-3 mb-4 text-uppercase">Đơn hàng của bạn</h4>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="d-flex gap-1">
                                        <h6 class="text-uppercase">Mã đơn hàng: </h6>
                                        <h6 class="fw-normal">{{ $order->order_code }}</h6>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="d-flex gap-1">
                                        <h6 class="text-uppercase">Ngày đặt hàng: </h6>
                                        <h6 class="fw-normal">{{ date('d/m/Y', strtotime($order->order_date)) }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="bt_hr my-2"></div>
                            <div class="row">
                                <h6 class="fw-bold text-uppercase my-3">Địa chỉ nhận hàng</h6>
                                <div class="col-lg-12">
                                    <div class="container-fluid px-0">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 mb-3 bt_hr">
                                                <div class="bg-light p-3 rounded mb-3 flex-wrap">
                                                    <div class="d-flex flex-wrap">
                                                        <strong>{{ $order->order_name }}</strong> |
                                                        <b>{{ $order->order_phone }}</b> |
                                                        <b>{{ $order->order_email }}</b>
                                                    </div>
                                                    <div class="d-flex">
                                                        <span>{{ $order->order_address }},</span>
                                                        <span>{{ $order->order_local }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-8 mb-3">
                                                <label for="floatingTextarea2" class="form-label">Lưu ý dành cho người bán</label>
                                                <textarea class="form-control" cols="5" rows="4" disabled placeholder="Nhập nội dung tại đây..." id="floatingTextarea2">{{ $order->note_customer == null ? 'Không có ghi chú' : $order->note_customer }}</textarea>
                                            </div>
                                            <div class="col-lg-4 mb-3 h-100">
                                                <label for="" class="form-label fw-bold">Đơn vị vận chuyển</label>
                                                <div class="bg-light p-3 h-100 rounded bd_hr d-flex flex-column">
                                                    <span class="text-dvvc fw-bold">Giao hàng nhanh</span>
                                                    <small>Nhận hàng vào 22 Th10 - 22 Th10</small>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <a href="{{ route('user.order') }}"
                                                    class="btn-del__cart btn-add__pro">
                                                    <span class="button__text">Quay lại</span>
                                                    <span class="button__icon">
                                                        <i class='bx bx-left-arrow-alt fs-5'></i>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <h4 class="text-center fw-bold mt-3 mb-4 text-uppercase">Sản phẩm đã mua</h4>
                            @php
                                $giatri_donhang = 0;
                                $sosanpham = 0;
                            @endphp
                            @foreach ($orderDetail as $oD)
                                @php
                                    $pro = DB::table('products')
                                        ->where('pro_id', '=', $oD->pro_id)
                                        ->first();
                                @endphp
                                <div class="col-12">
                                    <div class="d-flex gap-3 mb-3">
                                        <img src="{{ asset($pro->pro_img) }}" onerror="this.src='/uploads/img_error.jpg'" class="img-payment__new rounded" alt="{{ $pro->pro_name }}">
                                        <div>
                                            <h6 class="text__truncate mb-1 fw-bold">{{ $oD->pro_name }}</h6>
                                            <div class="d-flex gap-3 flex-wrap">
                                                @if ($oD->size !== null)
                                                    <span class="fw-bold">Size: <span class="fw-normal">{{ $oD->size }}</span></span>
                                                @endif
                                                @if ($oD->color !== null)
                                                    <span class="fw-bold">Màu: <span class="fw-normal">{{ $oD->color }}</span></span>
                                                @endif
                                                <span class="fw-bold">SL: <span class="fw-normal">{{ $oD->quantity }}</span></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($oD->price*$oD->quantity, 0, ',', '.') }} VNĐ</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $giatri_donhang += $oD->price*$oD->quantity;
                                    $sosanpham++;
                                @endphp
                            @endforeach
                            <div class="cart__bottom mt-4">
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase">Giá trị sản phẩm <small class="fw-normal">({{ $sosanpham }} sản phẩm):</small></h6>
                                    <label class="font__size">{{ number_format($giatri_donhang, 0, ',', '.') }} VNĐ</label>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase">Phí vận chuyển:</h6>
                                    <label class="font__size">{{ number_format($order->order_delivery_fee, 0, ',', '.') }} VNĐ</label>
                                </div>
        
                                @isset($coupon_data)
                                    <div class="mb-3 d-flex justify-content-between align-items-center gap-1">
                                        <label class="fw-bold text-black font__size">Mã giảm giá:
                                            @if ($coupon_data->coupon_condition == 0)
                                                {{ '(' . $coupon_data->coupon_value . '%)' }}
                                            @endif
                                        </label>
                                        <label class="font__size">
                                            @php
                                                if ($coupon_data->coupon_condition == 0) {
                                                    $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                                }
                                            @endphp
                                            {{ $coupon_data->coupon_condition == 1
                                                ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.') . '     VNĐ'
                                                : '- ' . number_format($discount, 0, ',', '.') . ' VNĐ' }}
                                        </label>
                                    </div>
                                @endisset
        
                                <hr class="mb-3">
                                <div class="mb-3 d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase mb-0">Thành tiền: </h6>
                                    <h4 class="fw-bold mb-0">
                                        {{ number_format($order->order_total, 0, ',', '.') }} VNĐ
                                    </h4>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6 class="fw-bold text-uppercase mb-2">HÌNH THỨC THANH TOÁN</h6>
                                <div class="bg-light p-3 rounded" style="border: 1px dashed rgba(0, 0, 0, .09);">
                                    <span class="fw-bold">
                                        @if ($order->order_payment == 'cod')
                                            Thanh toán khi nhận hàng
                                        @else
                                            @if ($order->order_payment == 'payUrl')
                                                Thanh toán qua MoMo
                                            @else
                                                Thanh toán qua VNPay
                                            @endif
                                        @endif
                                    </span>
                                </div>
                            </div>
                            {{--  --}}
                            <div class="row">
                                @if ($order->order_status != 2 && $order->order_status != 3)
                                    <div class="col-lg-12 mb-3">
                                        @if ($order->order_delivery_status == 0 || $order->order_status == 10)
                                            <button disabled
                                                class="w-100 custom-btn bgc-o text-white bgc-o-disabled p-2 rounded btn-order">Đã nhận
                                                hàng</button>
                                        @else
                                            <form action="{{ route('success.order') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="order_code" value="{{ $order->order_code }}">
                                                <button class="w-100 custom-btn bgc-o text-white p-2 rounded btn-order">Đã nhận
                                                    hàng</button>
                                            </form>
                                        @endif
                                    </div>
                                    @if ($order->order_status == 0)
                                        <div class="col-lg-12 mb-3">
                                            <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                data-bs-toggle="modal" data-bs-target="#cancelOrder">Hủy đơn / Hoàn
                                                tiền</button>
                                        </div>
                                    @else
                                        @if ($order->order_status == 10)
                                            <div class="col-lg-12 mb-3">
                                                <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                    data-bs-toggle="modal" data-bs-target="#returnOrder">Yêu cầu trả hàng / Hoàn
                                                    tiền</button>
                                            </div>
                                        @else
                                            <div class="col-lg-12 mb-3">
                                                <button disabled
                                                    class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                    data-bs-toggle="modal" data-bs-target="#returnOrder">Yêu cầu trả hàng / Hoàn
                                                    tiền</button>
                                            </div>
                                        @endif
                                    @endif
                                @endif                                      
                            </div>
                            {{--  --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- return Order --}}
    <div class="modal fade" id="returnOrder" tabindex="-1" aria-labelledby="returnOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="returnOrderLabel">Trả hàng / Hoàn tiền</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="d-flex justify-content-center my-3">
                            <img src="/frontend/img/giphyno.gif" class="rounded-circle" alt="" width="150px" height="150px">
                        </div>
                        <div>
                            <div class="mb-3">
                                <label for="reasonReturnOrder" class="form-label">Hãy cho chúng tôi biết lí do bạn muốn trả hàng?</label>
                                <textarea class="form-control" placeholder="Nhập nội dung tại đây..." id="reasonReturnOrder" rows="4"
                                    onkeyup="updateNoteReturn()"></textarea>
                                <i id="errorSpanReturn" class="error" style="color:red;display: none;">Vui lòng nhập lý
                                    do trả
                                    đơn hàng.</i>
                            </div>
                            <div class="d-flex justify-content-center m-auto my-3">
                                <form action="{{ route('return.order', $order->order_code) }}" method="post">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="inputReturnOrder" value="" id="inputReturnOrder">
                                    <button type="submit" class="custom-btn bgc-o text-white"
                                        onclick="validateFormReturn(event)">Xác nhận trả hàng</button>
                                </form>
                                <button type="button" class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                    data-bs-toggle="modal">Không trả hàng</button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
        function updateNoteReturn() {
            var noteValue = document.getElementById("reasonReturnOrder").value;
            document.getElementById("inputReturnOrder").value = noteValue;
        }

        function validateFormReturn(event) {
            var noteValue = document.getElementById("reasonReturnOrder").value;
            var errorSpanReturn = document.getElementById("errorSpanReturn");

            if (noteValue.trim() === "") {
                errorSpanReturn.style.display = "block";
                event.preventDefault(); // Ngăn chặn việc submit form
            } else {
                errorSpanReturn.style.display = "none";
                // Form được submit tự động nếu đã nhập lý do
            }
        }
    </script>
    {{-- cancel Order --}}
    <div class="modal fade" id="cancelOrder" tabindex="-1" aria-labelledby="cancelOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="cancelOrderLabel">Hủy đơn / Hoàn tiền</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="d-flex justify-content-center my-3">
                            <img src="/frontend/img/giphyno.gif" class="rounded-circle" alt="" width="150px" height="150px">
                        </div>
                        <div>
                            <div class="mb-3">
                                <label for="reasonCancelOrder" class="form-label">Hãy cho chúng tôi biết lí do bạn muốn hủy?</label>
                                <textarea class="form-control" placeholder="Nhập nội dung tại đây..." id="reasonCancelOrder" rows="4"
                                    onkeyup="updateNote()"></textarea>
                                <i id="errorSpan" class="error" style="color:red;display: none;">Vui lòng nhập lý do hủy
                                    đơn hàng.</i>
                            </div>
                            <div class="d-flex justify-content-center m-auto my-3">
                                <form action="{{ route('cancelOrder', $order->order_code) }}" method="post">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="inputCancelOrder" value="" id="inputCancelOrder">
                                    <button type="submit" class="custom-btn bgc-o text-white"
                                        onclick="validateForm(event)">Xác nhận hủy</button>
                                </form>
                                <button type="button" class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                    data-bs-toggle="modal">Không hủy</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateNote() {
            var noteValue = document.getElementById("reasonCancelOrder").value;
            document.getElementById("inputCancelOrder").value = noteValue;
        }

        function validateForm(event) {
            var noteValue = document.getElementById("reasonCancelOrder").value;
            var errorSpan = document.getElementById("errorSpan");

            if (noteValue.trim() === "") {
                errorSpan.style.display = "block";
                event.preventDefault(); // Ngăn chặn việc submit form
            } else {
                errorSpan.style.display = "none";
                // Form được submit tự động nếu đã nhập lý do
            }
        }
    </script>
@endsection