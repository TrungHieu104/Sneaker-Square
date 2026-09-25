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
                            @if ($order->hasStatus(\App\Enums\OrderStatus::Cancelled))
                                <h4 class="fw-bold mb-3">Đã hủy đơn</h4>
                            @else
                                @if ($order->orderReturn)
                                    <div class="text-center mb-3">
                                        <h4 class="fw-bold mb-1">Yêu cầu trả hàng: {{ $order->orderReturn->statusLabel() }}</h4>
                                        <div class="text-muted small">
                                            @switch($order->orderReturn->status)
                                                @case(\App\Models\OrderReturnModel::REQUESTED)
                                                    Cửa hàng đang xem xét yêu cầu của bạn.
                                                    @break
                                                @case(\App\Models\OrderReturnModel::APPROVED)
                                                    Vui lòng đóng gói sản phẩm, nhân viên GHN sẽ đến lấy hàng tại địa chỉ nhận hàng của đơn.
                                                    @if ($order->orderReturn->return_shipping_code)
                                                        Mã vận đơn trả hàng: <b>{{ $order->orderReturn->return_shipping_code }}</b>.
                                                    @endif
                                                    @break
                                                @case(\App\Models\OrderReturnModel::RECEIVED)
                                                    Cửa hàng đã nhận được hàng trả và sẽ hoàn tiền cho bạn.
                                                    @break
                                                @case(\App\Models\OrderReturnModel::REJECTED)
                                                    Cửa hàng đã từ chối yêu cầu này. Còn trong thời hạn thì bạn vẫn gửi lại được.
                                                    @break

                                                @case(\App\Models\OrderReturnModel::CANCELLED)
                                                    Bạn đã huỷ yêu cầu này. Còn trong thời hạn thì bạn vẫn gửi lại được.
                                                    @break

                                                @case(\App\Models\OrderReturnModel::REFUNDED)
                                                    Đã hoàn {{ number_format((int) $order->orderReturn->refund_amount, 0, ',', '.') }} VNĐ.
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                @elseif ($order->hasStatus(\App\Enums\OrderStatus::Returned))
                                    <h4 class="fw-bold mb-3">Giao hàng không thành công, đơn đã được hoàn về cửa hàng</h4>
                                @elseif ($order->order_status->isComingBack())
                                    <h4 class="fw-bold mb-3">Đã gửi yêu cầu trả hàng</h4>
                                @else
                                    <div class="position-relative w-progress-bar">
                                        <div class="progress" role="progressbar" aria-label="Progress"
                                            aria-valuenow="@if ($order->hasStatus(\App\Enums\OrderStatus::Completed)) 100
                                @else
                                    @if ($order->order_status->isAccepted())
                                        50
                                    @else
                                        25 @endif
                                @endif"
                                            aria-valuemin="0" aria-valuemax="100" style="height: 2px;">
                                            <div class="progress-bar"
                                                style="width: @if ($order->hasStatus(\App\Enums\OrderStatus::Completed)) 100%
                                    @else
                                        @if ($order->order_status->isAccepted())
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
                                        ($order->order_payment == 'cod' && $order->order_status->isAccepted())) bg-success 
                                    @else
                                        @if ($order->order_payment == 'cod' && $order->hasStatus(\App\Enums\OrderStatus::New))
                                            bg-secondary @endif
                                    @endif
                                    @if (
                                        ($order->order_payment == 'payUrl' && $order->order_payment_status == 1) ||
                                            ($order->order_payment == 'payUrl' && $order->hasStatus(\App\Enums\OrderStatus::Completed))) bg-success 
                                    @else
                                        @if ($order->order_payment == 'payUrl' && $order->order_payment_status == 0)
                                            bg-secondary @endif
                                    @endif
                                    @if (
                                        ($order->order_payment == 'redirect' && $order->order_payment_status == 1) ||
                                            ($order->order_payment == 'redirect' && $order->hasStatus(\App\Enums\OrderStatus::Completed))) bg-success 
                                    @else
                                        @if ($order->order_payment == 'redirect' && $order->order_payment_status == 0)
                                            bg-secondary @endif
                                    @endif
                                    @if ($order->order_payment == 'wallet') bg-success @endif
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
                                                        ($order->order_payment == 'cod' && $order->order_status->isAccepted()))
                                                        Đã xác nhận thông tin thanh toán
                                                        <br>
                                                        <sub>
                                                            {{ date('H:i d/m/Y', strtotime($order->order_payment_time)) }}
                                                        </sub>
                                                    @else
                                                        @if ($order->order_payment == 'cod' && $order->hasStatus(\App\Enums\OrderStatus::New))
                                                            Đang chờ xác nhận từ Sneaker Square
                                                        @endif
                                                    @endif
                                                    {{-- check status payment MOMO --}}
                                                    @if (
                                                        ($order->order_payment == 'payUrl' && $order->order_payment_status == 1) ||
                                                            ($order->order_payment == 'payUrl' && $order->hasStatus(\App\Enums\OrderStatus::Completed)))
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
                                                            ($order->order_payment == 'redirect' && $order->hasStatus(\App\Enums\OrderStatus::Completed)))

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

                                                    {{-- check status payment wallet --}}
                                                    @if ($order->order_payment == 'wallet')
                                                        Đã trừ từ SPay
                                                        <br>
                                                        <sub>
                                                            {{ date('H:i d/m/Y', strtotime($order->order_payment_time)) }}
                                                        </sub>
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
                                                    @if ($order->isBeingReturned())
                                                        Đang hoàn hàng<br>
                                                    @elseif ($order->order_shipping_status === 'delivered')
                                                        Đã giao hàng<br>
                                                    @elseif ($order->order_delivery_status == 1)
                                                        Đang vận chuyển<br>
                                                    @else
                                                        Đang chuẩn bị<br>
                                                    @endif
                                                    <sub>
                                                        <a href="#" data-bs-toggle="modal"
                                                           data-bs-target="#HanhTrinhModal">Xem hành trình</a>
                                                    </sub>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="position-absolute top-0 start-100 translate-middle rounded-pill w-h-2rem
                                    @if ($order->hasStatus(\App\Enums\OrderStatus::Completed)) bg-success
                                    @else
                                        bg-secondary @endif">
                                            <div
                                                class="text-white text-center d-flex justify-content-center align-items-center h-100">
                                                4
                                            </div>
                                            <div class="position-relative">

                                                <div class="position-absolute text-dark text-capitalize w-text-130px"
                                                    style="margin-top:10px; left: -90%;">
                                                    @if ($order->hasStatus(\App\Enums\OrderStatus::Completed))
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
                                                    <small>
                                                        @if ($order->order_expected_delivery)
                                                            Dự kiến nhận hàng: {{ \Carbon\Carbon::parse($order->order_expected_delivery)->format('d/m/Y') }}
                                                        @else
                                                            Đang cập nhật thời gian giao
                                                        @endif
                                                    </small>
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
                                        {{ $order->paymentLabel() }}
                                    </span>
                                </div>
                            </div>
                            {{--  --}}
                            <div class="row">
                                @if ($order->orderReturn)
                                    <div class="col-lg-12 mb-3">
                                        <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                            data-bs-toggle="modal" data-bs-target="#returnDetail">
                                            Lịch sử trả hàng ({{ $order->orderReturns->count() }})
                                        </button>
                                    </div>
                                @endif
                                @if (! $order->hasStatus(\App\Enums\OrderStatus::Cancelled) && ! $order->order_status->isComingBack())
                                    <div class="col-lg-12 mb-3">
                                        @if (! $order->isAwaitingReceipt())
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
                                    @if ($order->hasStatus(\App\Enums\OrderStatus::New) || $order->canRequestCancel())
                                        <div class="col-lg-12 mb-3">
                                            <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                data-bs-toggle="modal" data-bs-target="#cancelOrder">Hủy đơn / Hoàn
                                                tiền</button>
                                        </div>
                                    @else
                                        @if ($order->canRequestReturn())
                                            <div class="col-lg-12 mb-3">
                                                <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
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
    @if ($order->canRequestReturn() || $errors->hasAny(['reason', 'items', 'description', 'images', 'images.*']))
    <div class="modal fade" id="returnOrder" tabindex="-1" aria-labelledby="returnOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('return.order', $order->order_code) }}" method="post" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="returnOrderLabel">Yêu cầu trả hàng / Hoàn tiền</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    @php
                        // Only what is still at the customer's house can be sent
                        // back: a line returned last week is not on offer again.
                        $conTraDuoc = $order->returnableQuantities();
                        $dongConLai = $orderDetail->filter(fn ($dong) => isset($conTraDuoc[$dong->order_details_id]));
                        $daTraBot = $dongConLai->count() < $orderDetail->count()
                            || $dongConLai->contains(fn ($dong) => $conTraDuoc[$dong->order_details_id] < (int) $dong->quantity);
                    @endphp
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Bạn có thể gửi yêu cầu đến hết
                            {{ optional($order->returnDeadline())->format('H:i d/m/Y') }}.
                            Mỗi lần chỉ xử lý được một yêu cầu; xong yêu cầu này bạn vẫn gửi tiếp được cho phần còn lại.
                            Sau khi cửa hàng duyệt, nhân viên GHN sẽ đến địa chỉ nhận hàng của đơn để lấy hàng trả.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Sản phẩm muốn trả <span class="text-danger">*</span></label>
                            <div class="border rounded p-2">
                                @foreach ($dongConLai as $dong)
                                    @php $conLai = $conTraDuoc[$dong->order_details_id]; @endphp
                                    <div class="d-flex justify-content-between align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                        <div>
                                            <div>{{ $dong->pro_name }}</div>
                                            <div class="text-muted small">
                                                Size {{ $dong->size }} &middot; {{ $dong->color }} &middot;
                                                @if ($conLai < (int) $dong->quantity)
                                                    còn trả được {{ $conLai }}/{{ $dong->quantity }}
                                                @else
                                                    đã mua {{ $dong->quantity }}
                                                @endif
                                                &middot;
                                                {{ number_format((int) $dong->price, 0, ',', '.') }}đ / sản phẩm
                                            </div>
                                        </div>
                                        <div style="width: 150px;">
                                            <select class="form-select form-select-sm" name="items[{{ $dong->order_details_id }}]">
                                                @for ($sl = 0; $sl <= $conLai; $sl++)
                                                    <option value="{{ $sl }}" @selected((int) old('items.'.$dong->order_details_id, 0) === $sl)>
                                                        {{ $sl === 0 ? 'Không trả' : 'Trả '.$sl }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($daTraBot)
                                <div class="form-text">
                                    Danh sách chỉ còn phần bạn chưa gửi trả ở các yêu cầu trước.
                                </div>
                            @endif
                            <div class="form-text">
                                Chỉ chọn số lượng bạn thực sự gửi trả. Phần giữ lại vẫn tính là đã mua, và phí vận chuyển
                                không được hoàn khi bạn chỉ trả một phần đơn.
                            </div>
                            @error('items') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="return-reason" class="form-label">Lý do trả hàng <span class="text-danger">*</span></label>
                            <select class="form-select" id="return-reason" name="reason">
                                <option value="">Chọn lý do</option>
                                @foreach (\App\Models\OrderReturnModel::REASONS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reason') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="return-description" class="form-label">Mô tả thêm</label>
                            <textarea class="form-control" id="return-description" name="description" rows="3"
                                placeholder="Ví dụ: giày size 42 bị chật, muốn trả lại">{{ old('description') }}</textarea>
                            @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="return-images" class="form-label">Ảnh sản phẩm (tối đa {{ \App\Http\Requests\Frontend\ReturnOrderRequest::MAX_IMAGES }} ảnh)</label>
                            <input class="form-control" type="file" id="return-images" name="images[]" accept="image/*" multiple>
                            @error('images') <small class="text-danger">{{ $message }}</small> @enderror
                            @error('images.*') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="alert alert-light border small mb-0" role="note">
                            Tiền hoàn được cộng vào <a href="{{ route('user.wallet') }}">SPay</a> của bạn ngay khi
                            cửa hàng nhận và kiểm tra hàng trả. Từ ví bạn có thể mua đơn khác, hoặc rút về ngân hàng —
                            khai số tài khoản ở bước rút tiền.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="border grey-hover border-1 custom-btn text-dark" data-bs-dismiss="modal">Không trả hàng</button>
                        <button type="submit" class="custom-btn bgc-o text-white">Gửi yêu cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($errors->hasAny(['reason', 'items', 'description', 'images', 'images.*']))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('returnOrder')).show();
            });
        </script>
    @endif
    @endif
    @if ($order->orderReturn)
        @include('components.return_request', ['order' => $order->load('orderReturns.items.line')])
    @endif

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
    <div class="modal fade" id="HanhTrinhModal" tabindex="-1" aria-labelledby="HanhTrinhModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="HanhTrinhModalLabel">Hành trình đơn hàng</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @include('components.shipment_timeline', ['order' => $order])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection
