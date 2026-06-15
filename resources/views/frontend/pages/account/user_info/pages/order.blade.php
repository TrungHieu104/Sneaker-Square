@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
    <div>
        <div class="mb-3 bg-white">
            <ul class="nav nav-underline d-flex justify-content-evenly align-items-center" id="v-pills-tab" role="tablist"
                aria-orientation="vertical">
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60 active" id="v-pills-all-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-all" role="tab" aria-controls="v-pills-all"
                        aria-selected="true">Tất cả</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-wait-payment-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-wait-payment" role="tab"
                        aria-controls="v-pills-wait-payment" aria-selected="false">Chờ thanh toán</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-wait-confirm-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-wait-confirm" role="tab"
                        aria-controls="v-pills-wait-confirm" aria-selected="false">Chờ xác nhận</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-delivering-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-delivering" role="tab"
                        aria-controls="v-pills-delivering" aria-selected="false">Đang giao</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-deli-success-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-deli-success" role="tab"
                        aria-controls="v-pills-deli-success" aria-selected="false">Hoàn thành</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-canceled-order-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-canceled-order" role="tab"
                        aria-controls="v-pills-canceled-order" aria-selected="false">Đã hủy</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="v-pills-return-order-tab"
                        data-bs-toggle="pill" data-bs-target="#v-pills-return-order" role="tab"
                        aria-controls="v-pills-return-order" aria-selected="false">Trả hàng / hoàn tiền</button>
                </li>
            </ul>
        </div>
        <div class="tab-content" id="v-pills-tabContent">
            <div class="tab-pane fade show active" id="v-pills-all" role="tabpanel" aria-labelledby="v-pills-all-tab"
                tabindex="0">
                <div class="d-flex mb-3 position-relative" role="search">
                    <svg style="left:23px;" class="position-absolute top-50 translate-middle"
                        xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                        <path
                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                    </svg>
                    <input id="orderCode" class="form-control p-2 px-5" type="search"
                        placeholder="Bạn có thể tìm kiếm theo ID đơn hàng hoặc tên sản phẩm" aria-label="Search">
                </div>
                @if ($all_order->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($all_order as $all_order_data)
                        <div class="mb-3 p-3 bg-white all_order">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $all_order_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        @php
                                            if ($all_order_data->order_status == 0) {
                                                //chờ thanh toán && chờ xác nhận
                                                if ($all_order_data->order_payment == 'payUrl') {
                                                    if ($all_order_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($all_order_data->order_payment == 'redirect') {
                                                    if ($all_order_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($all_order_data->order_payment == 'cod') {
                                                    echo 'Chờ xác nhận';
                                                }
                                            } elseif ($all_order_data->order_status == 1) {
                                                // đã xác nhận
                                                echo 'Đang giao';
                                                // if ($all_order_data->order_delivery_status == 1) {
                                                //     echo 'Đang giao';
                                                // } else {
                                                //     echo 'Chờ xác nhận';
                                                // }
                                            } elseif ($all_order_data->order_status == 2) {
                                                //đã hủy
                                                echo 'Đã hủy';
                                            } elseif ($all_order_data->order_status == 3) {
                                                //trả hàng / hoàn tiền
                                                echo ' Trả hàng / Hoàn tiền';
                                            } elseif ($all_order_data->order_status == 10) {
                                                //hoàn thành
                                                echo 'Hoàn thành';
                                            }
                                        @endphp
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $all_order_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}" alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                    @if ($all_order_data->order_status == 0)
                                        @if ($all_order_data->order_payment == 'redirect' || $all_order_data->order_payment == 'payUrl')
                                            @if ($all_order_data->order_payment_status == 0)
                                            <div class="mt-md-0 mt-2 col-md-4 col-12">
                                                <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                                href="{{route('orderBill.checkout', $all_order_data->order_code) }}">Chi tiết
                                                đơn hàng</a>
                                            </div>
                                            @else
                                            <div class="mt-md-0 mt-2 col-md-4 col-12">
                                                <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank" href="{{url('/in-don-hang/' . $all_order_data->order_code) }}">In đơn hàng</a>
                                            </div>
                                            <div class="mt-md-0 mt-2 col-md-4 col-12">
                                                <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" href="{{ route('orderBill.checkout', $all_order_data->order_code) }}">Chi tiết đơn hàng</a>
                                            </div>
                                            @endif
                                        @endif
                                        @if ($all_order_data->order_payment == 'cod')
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank" href="{{url('/in-don-hang/' . $all_order_data->order_code) }}">In đơn hàng</a>
                                        </div>
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" href="{{route('orderBill.checkout', $all_order_data->order_code) }}">Chi tiết đơn hàng</a>
                                        </div>
                                        @endif
                                    @endif
                                    @if ($all_order_data->order_status == 1)
                                        @if ($all_order_data->order_delivery_status == 1)
                                            {{-- <button class="custom-btn bgc-o text-white">Đã nhận hàng</button> --}}
                                            <form class="ms-md-0 ms-2 col-md-4 col-12" action="{{ route('success.order') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="order_code" value="{{ $all_order_data->order_code }}">
                                                <button class="custom-btn bgc-o text-white btn w-100">Đã nhận
                                                hàng</button>
                                            </form>
                                        @endif
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank" target="_blank" href="{{url('/in-don-hang/' . $all_order_data->order_code)}}">In đơn hàng</a>
                                        </div>
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank" target="_blank" href="{{route('orderBill.checkout', $all_order_data->order_code)}}">Chi tiết
                                            đơn hàng</a>
                                        </div>
                                    @endif
                                    @if ($all_order_data->order_status == 2)
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                        <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank"
                                        href="{{route('orderBill.checkout', $all_order_data->order_code)}}">Chi tiết hủy đơn</a>
                                    </div>
                                    @endif
                                    @if ($all_order_data->order_status == 3)
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                        <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" href="{{route('orderBill.checkout', $all_order_data->order_code)}}">Chi tiết đơn hàng</a>
                                    </div>
                                    @endif
                                    @if ($all_order_data->order_status == 10)
                                        {{-- <button disabled class="custom-btn bgc-o text-white bgc-o-disabled">Đã nhận hàng</button> --}}
                                        <form class="ms-md-0 ms-2 col-md-4 col-12" action="{{ route('success.order') }}" method="post">
                                            @csrf
                                            <input type="hidden" name="order_code" value="{{ $all_order_data->order_code }}">
                                            <button disabled class="custom-btn bgc-o text-white btn w-100">Đã nhận
                                            hàng</button>
                                        </form>
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank"
                                                href="{{url('/in-don-hang/' . $all_order_data->order_code) }}">In đơn hàng</a>
                                        </div>
                                        <div class="mt-md-0 mt-2 col-md-4 col-12">
                                            <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                                href="{{route('orderBill.checkout', $all_order_data->order_code) }}">Chi tiết đơn hàng</a>
                                        </div>
                                    @endif

                                </div>
                                <div class="col-12 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($all_order_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                <span id="blank_find"></span>
            </div>
            {{-- Chờ thanh toán --}}
            <div class="tab-pane fade" id="v-pills-wait-payment" role="tabpanel"
                aria-labelledby="v-pills-wait-payment-tab" tabindex="0">
                @if ($wait_payment->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($wait_payment as $wait_payment_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $wait_payment_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        @php
                                            if ($wait_payment_data->order_status == 0) {
                                                //chờ thanh toán && chờ xác nhận
                                                if ($wait_payment_data->order_payment == 'payUrl') {
                                                    if ($wait_payment_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($wait_payment_data->order_payment == 'redirect') {
                                                    if ($wait_payment_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($wait_payment_data->order_payment == 'cod') {
                                                    echo 'Chờ xác nhận';
                                                }
                                            } elseif ($wait_payment_data->order_status == 1) {
                                                // đã xác nhận
                                                if ($wait_payment_data->order_delivery_status == 1) {
                                                    echo 'Đang giao';
                                                } else {
                                                    echo 'Chờ xác nhận';
                                                }
                                            } elseif ($wait_payment_data->order_status == 2) {
                                                //đã hủy
                                                echo 'Đã hủy';
                                            } elseif ($wait_payment_data->order_status == 3) {
                                                //trả hàng / hoàn tiền
                                                echo ' Trả hàng / Hoàn tiền';
                                            } elseif ($wait_payment_data->order_status == 10) {
                                                //hoàn thành
                                                echo 'Hoàn thành';
                                            }
                                        @endphp
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $wait_payment_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-8 col-md-8">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                        href="{{ route('orderBill.checkout', $wait_payment_data->order_code) }}">Xem chi
                                        tiết đơn hàng</a>
                                </div>
                                <div class="col-4 col-md-4 text-end">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($wait_payment_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            {{-- Chờ xác nhận --}}
            <div class="tab-pane fade" id="v-pills-wait-confirm" role="tabpanel"
                aria-labelledby="v-pills-wait-confirm-tab" tabindex="0">
                @if ($wait_confirm->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($wait_confirm as $wait_confirm_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $wait_confirm_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        @php
                                            if ($wait_confirm_data->order_status == 0) {
                                                //chờ thanh toán && chờ xác nhận
                                                if ($wait_confirm_data->order_payment == 'payUrl') {
                                                    if ($wait_confirm_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($wait_confirm_data->order_payment == 'redirect') {
                                                    if ($wait_confirm_data->order_payment_status == 0) {
                                                        echo 'Chờ thanh toán';
                                                    } else {
                                                        echo 'Chờ xác nhận';
                                                    }
                                                } elseif ($wait_confirm_data->order_payment == 'cod') {
                                                    echo 'Chờ xác nhận';
                                                }
                                            } elseif ($wait_confirm_data->order_status == 1) {
                                                // đã xác nhận
                                                if ($wait_confirm_data->order_delivery_status == 1) {
                                                    echo 'Đang giao';
                                                } else {
                                                    echo 'Chờ xác nhận';
                                                }
                                            } elseif ($wait_confirm_data->order_status == 2) {
                                                //đã hủy
                                                echo 'Đã hủy';
                                            } elseif ($wait_confirm_data->order_status == 3) {
                                                //trả hàng / hoàn tiền
                                                echo ' Trả hàng / Hoàn tiền';
                                            } elseif ($wait_confirm_data->order_status == 10) {
                                                //hoàn thành
                                                echo 'Hoàn thành';
                                            }
                                        @endphp
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $wait_confirm_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank"
                                        href="{{ url('/in-don-hang/' . $wait_confirm_data->order_code) }}">In đơn hàng</a>
                                </div>
                                <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                    href="{{ route('orderBill.checkout', $wait_confirm_data->order_code) }}">Chi
                                    tiết đơn hàng</a>
                                </div>
                                </div>
                                <div class="col-4 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($wait_confirm_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            {{-- Đang giao --}}
            <div class="tab-pane fade" id="v-pills-delivering" role="tabpanel" aria-labelledby="v-pills-delivering-tab"
                tabindex="0">
                @if ($delivery_order->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($delivery_order as $delivery_order_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $delivery_order_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        Đang giao <br>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $delivery_order_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                    @if ($delivery_order_data->order_delivery_status == 0 || $delivery_order_data->order_status == 10)
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                        <button disabled
                                            class="custom-btn bgc-o text-white bgc-o-disabled w-100 btn">Đã nhận
                                            hàng</button>
                                    </div>
                                    @else
                                        <form class="ms-md-0 ms-2 col-md-4 col-12" action="{{ route('success.order') }}" method="post">
                                            @csrf
                                            <input type="hidden" name="order_code" value="{{ $delivery_order_data->order_code }}">
                                            <button class="custom-btn bgc-o text-white btn w-100">Đã nhận
                                            hàng</button>
                                        </form>
                                    @endif
                                    {{-- <button class="custom-btn bgc-o text-white">Đã nhận hàng</button> --}}
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank"
                                            href="{{ url('/in-don-hang/' . $delivery_order_data->order_code) }}">In đơn
                                            hàng</a>
                                    </div>
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                            href="{{ route('orderBill.checkout', $delivery_order_data->order_code) }}">Chi
                                            tiết đơn hàng</a>
                                    </div>
                                </div>
                                <div class="col-4 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($delivery_order_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            {{-- Hoàn thành --}}
            <div class="tab-pane fade" id="v-pills-deli-success" role="tabpanel"
                aria-labelledby="v-pills-deli-success-tab" tabindex="0">
                @if ($success_order->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($success_order as $success_order_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $success_order_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        Hoàn thành
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $success_order_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                    {{-- <button >Đã nhận hàng</button> --}}

                                    <form class="ms-md-0 ms-2 col-md-4 col-12" action="{{ route('success.order') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="order_code"
                                            value="{{ $success_order_data->order_code }}">
                                        <button class="custom-btn bgc-o text-white btn w-100">Đã nhận
                                            hàng</button>
                                    </form>
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                        <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100" target="_blank"
                                            href="{{ url('/in-don-hang/' . $success_order_data->order_code) }}">In đơn
                                            hàng</a>
                                    </div>
                                    <div class="mt-md-0 mt-2 col-md-4 col-12">
                                        <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                            href="{{ route('orderBill.checkout', $success_order_data->order_code) }}">Chi
                                            tiết đơn hàng</a>
                                    </div>           
                                </div>
                                <div class="col-12 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($success_order_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            {{-- Đã hủy --}}
            <div class="tab-pane fade" id="v-pills-canceled-order" role="tabpanel"
                aria-labelledby="v-pills-canceled-order-tab" tabindex="0">
                @if ($cancel_order->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa hủy đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($cancel_order as $cancel_order_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $cancel_order_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="nkmfr2"><svg class="mx-1" xmlns="http://www.w3.org/2000/svg"
                                            height="1em" viewBox="0 0 640 512">
                                            <style>
                                                svg {
                                                    fill: #26aa99
                                                }
                                            </style>
                                            <path
                                                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                                        </svg> *Trạng thái đơn hàng*</span> |
                                    <span class="text-co fw-bold text-uppercase">
                                        Đã hủy
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $cancel_order_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                            <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                        href="{{ route('orderBill.checkout', $cancel_order_data->order_code) }}">Chi
                                        tiết đơn hàng</a>
                                </div>
                                </div>
                                <div class="col-12 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($cancel_order_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            {{-- Trả hàng / Hoàn tiền --}}
            <div class="tab-pane fade" id="v-pills-return-order" role="tabpanel"
                aria-labelledby="v-pills-return-order-tab" tabindex="0">
                @if ($return_order->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center mt-5">
                            <div>
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3">
                                    Chưa có đơn hàng nào !
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @foreach ($return_order as $return_order_data)
                        <div class="mb-3 p-3 bg-white">
                            <div class="row">
                                <div class="col-4 text-start">
                                    # Mã đơn hàng - {{ $return_order_data->order_code }}
                                </div>
                                <div class="col-8 text-end">
                                    <span class="text-co fw-bold text-uppercase">
                                        Trả hàng / hoàn tiền
                                    </span>
                                </div>
                            </div>
                            <hr>
                            @foreach ($all_order_detail as $all_order_detail_data)
                                @if ($all_order_detail_data->order_id == $return_order_data->order_id)
                                    @php
                                        $pro = DB::table('products')
                                            ->where('pro_id', '=', $all_order_detail_data->pro_id)
                                            ->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-2 col-12 m-auto">
                                            <img class="img__cart" src="{{ $pro->pro_img }}"
                                                alt="{{ $pro->pro_name }}">
                                        </div>
                                        <div class="col-md-8 col-12 m-auto">
                                            <h4 class=" fs-6 text mt-md-0 mt-3 pro-name">
                                                {{ $all_order_detail_data->pro_name }}</h4>
                                            @if ($all_order_detail_data->size !== null)
                                                <div>
                                                    <b>Size: </b> <span>{{ $all_order_detail_data->size }}</span>
                                                </div>
                                            @endif
                                            @if ($all_order_detail_data->color !== null)
                                                <div>
                                                    <b>Màu: </b> <span>{{ $all_order_detail_data->color }}</span>
                                                </div>
                                            @endif
                                            <p>x {{ $all_order_detail_data->quantity }}</p>
                                        </div>
                                        <div class="col-md-2 col-12 d-flex justify-content-end m-auto">
                                            <span class="total-text fs17px">
                                                {{ number_format($all_order_detail_data->quantity * $all_order_detail_data->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                @endif
                            @endforeach
                            <div class="row p-1 d-flex justify-content-between">
                                <div class="col-12 row col-md-8 d-md-flex d-block order-md-1 order-2 mt-md-0 mt-3">
                                <div class="mt-md-0 mt-2 col-md-4 col-12">
                                    <a class="border grey-hover border-1 mx-2 custom-btn text-dark btn w-100"
                                            href="{{ route('orderBill.checkout', $return_order_data->order_code) }}">Chi
                                            tiết</a>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-end order-md-2 order-1">
                                    <strong>Thành tiền: </strong> <span
                                        class="total-text">{{ number_format($return_order_data->order_total, 0, ',', '.') }}
                                    </span><sub>VNĐ</sub>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

            </div>
        </div>
    </div>
    <script>
        //find order bill
        document.addEventListener("DOMContentLoaded", function() {
            const orderCodeInput = document.getElementById("orderCode");
            const orderContainers = document.querySelectorAll(
                ".mb-3.p-3.bg-white.all_order"); // Select all order containers
            const blankFind = document.getElementById("blank_find");

            orderCodeInput.addEventListener("input", function() {
                const searchTerm = orderCodeInput.value.trim().toLowerCase();

                // Loop through each order container and check if the order code contains the search term
                let found = false;
                orderContainers.forEach(function(container) {
                    const orderCode = container.querySelector(".text-start").textContent
                        .toLowerCase();
                    const namePro = container.querySelector(".pro-name").textContent.toLowerCase();
                    if (orderCode.includes(searchTerm) || namePro.includes(searchTerm)) {
                        found = true;
                        container.style.display = "block";
                    } else {
                        container.style.display = "none";
                    }
                });

                // If no results are found, show the blank_find element
                if (!found) {
                    blankFind.innerHTML = "Không tồn tại !";
                } else {
                    blankFind.innerHTML = "";
                }
            });
        });
    </script>
@endsection