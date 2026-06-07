@extends('frontend.index')

@section('title')
    Giỏ hàng
@endsection

@section('banner')
    <!-- Start banner Area -->
  
    <!-- End banner Area -->
@endsection

<!-- Content -->
@section('content')
    <section class="cart-order">
        <!-- Start Product Cart -->
        <div class="container-fluid px-5 my-5">
            <div class="col-lg-12 box__shadow-round rounded">
                <div class="container-xxl">
                    <div class="row">
                        <div class="col-lg-8 p-3">
                            <div class="container-fluid px-0">
                                <div class="d-flex justify-content-between p-3">
                                    <h4>Giỏ hàng</h4>
                                </div>
                                <div class="card border-0">
                                    @php
                                        $giatri_donhang = 0;
                                        $sosanpham = 0;
                                        $giatrisp = 0;
                                    @endphp
                                    @foreach ($cart as $c)
                                        @php
                                            $proSlug = $c['proSlug'];
                                            $quantity = $c['quantity'];
                                            $color_id = $c['color_id'];
                                            $size_id = $c['size_id'];
                                            $ten_sp = $c['pro_name'];
                                            $gia = DB::table('products')
                                                ->where('pro_slug', '=', $proSlug)
                                                ->value('pro_price');
                                            $size = DB::table('size')
                                                ->where('size_id', '=', $size_id)
                                                ->value('size');
                                            $color = DB::table('color')
                                                ->where('color_id', '=', $color_id)
                                                ->value('color_vn');
                                            $gia_sale = DB::table('products')
                                                ->where('pro_slug', '=', $proSlug)
                                                ->value('pro_price_sale');
                                            $img = DB::table('products')
                                                ->where('pro_slug', '=', $proSlug)
                                                ->value('pro_img');
                                            $cate_id = DB::table('products')
                                                ->where('pro_slug', '=', $proSlug)
                                                ->value('cate_id');
                                            $cate_name = DB::table('category')
                                                ->where('cate_id', '=', $cate_id)
                                                ->value('cate_name');
                                        @endphp

                                        @if ($gia_sale != 0)
                                            @php
                                                $giasale = $gia_sale * $quantity;
                                                $giabandau = $gia * $quantity;
                                                $giatri_donhang += $giasale;
                                            @endphp

                                            @if ($color && $size)
                                                <div class="row g-0">
                                                    <div class="col-md-3 p-3">
                                                        <img src="{{ $img }}" alt="{{ $ten_sp }}" onerror="this.src='/uploads/img_error.jpg'"
                                                            class="img-fluid h-100 rounded border img-cart__order">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="card-body h-100">
                                                            <div class="row h-100">
                                                                <div class="col-12">
                                                                    <h4 class="card-title fw-bold mb-2">
                                                                        {{ $ten_sp }}
                                                                    </h4>
                                                                    <div class="d-flex gap-3 align-items-center">
                                                                        <h5 class="fw-bold mb-3">
                                                                            {{ number_format($giasale, 0, ',', '.') }}
                                                                            VNĐ
                                                                        </h5>
                                                                        <del class="fw-bold mb-3">{{ number_format($giabandau, 0, ',', '.') }}
                                                                            VNĐ</del>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                        <div>
                                                                            <span class="fw-bold">Thương hiệu:
                                                                            </span><span>{{ $cate_name }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">Size:
                                                                            </span><span>{{ $size }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">SL:
                                                                            </span><span>{{ $quantity }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">Màu sắc:
                                                                            </span><span>{{ $color }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <form
                                                                            action="{{ route('delProduct.cart', $proSlug) }}"
                                                                            method="post">@csrf
                                                                            <input name="giatri_donhang"
                                                                                value="{{ $giatri_donhang }}" hidden>
                                                                            <button class="border-0" type="submit"><i
                                                                                    class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!$color && !$size)
                                                <div class="row g-0">
                                                    <div class="col-md-3 p-3">
                                                        <img src="{{ $img }}" alt="{{ $ten_sp }}"
                                                            class="img-fluid h-100 rounded border img-cart__order">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="card-body h-100">
                                                            <div class="row h-100">
                                                                <div class="col-12">
                                                                    <h4 class="card-title fw-bold mb-2">
                                                                        {{ $ten_sp }}
                                                                    </h4>
                                                                    <div class="d-flex gap-3 align-items-center">
                                                                        <h5 class="fw-bold mb-3">
                                                                            {{ number_format($giasale, 0, ',', '.') }}
                                                                            VNĐ
                                                                        </h5>
                                                                        <del class="fw-bold mb-3">{{ number_format($giabandau, 0, ',', '.') }}
                                                                            VNĐ</del>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                        <div>
                                                                            <span class="fw-bold">Thương hiệu:
                                                                            </span><span>{{ $cate_name }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">SL:
                                                                            </span><span>{{ $quantity }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <form
                                                                            action="{{ route('delProduct.cart', $proSlug) }}"
                                                                            method="post">@csrf
                                                                            <input name="giatri_donhang"
                                                                                value="{{ $giatri_donhang }}" hidden>
                                                                            <button class="border-0" type="submit"><i
                                                                                    class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                @if (!$size)
                                                    <div class="row g-0">
                                                        <div class="col-md-3 p-3">
                                                            <img src="{{ $img }}" alt="{{ $ten_sp }}"
                                                                class="img-fluid h-100 rounded border img-cart__order">
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="card-body h-100">
                                                                <div class="row h-100">
                                                                    <div class="col-12">
                                                                        <h4 class="card-title fw-bold mb-2">
                                                                            {{ $ten_sp }}
                                                                        </h4>
                                                                        <div class="d-flex gap-3 align-items-center">
                                                                            <h5 class="fw-bold mb-3">
                                                                                {{ number_format($giasale, 0, ',', '.') }}
                                                                                VNĐ
                                                                            </h5>
                                                                            <del class="fw-bold mb-3">{{ number_format($giabandau, 0, ',', '.') }}
                                                                                VNĐ</del>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                            <div>
                                                                                <span class="fw-bold">Thương hiệu:
                                                                                </span><span>{{ $cate_name }}</span>
                                                                            </div>
                                                                            <div>
                                                                                <span class="fw-bold">SL:
                                                                                </span><span>{{ $quantity }}</span>
                                                                            </div>
                                                                            <div>
                                                                                <span class="fw-bold">Màu sắc:
                                                                                </span><span>{{ $color }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <form
                                                                                action="{{ route('delProduct.cart', $proSlug) }}"
                                                                                method="post">@csrf
                                                                                <input name="giatri_donhang"
                                                                                    value="{{ $giatri_donhang }}" hidden>
                                                                                <button class="border-0" type="submit"><i
                                                                                        class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        @else
                                            @php
                                                $giatrisp = $gia * $quantity;
                                                $giatri_donhang += $giatrisp;
                                            @endphp
                                            @if ($color && $size)
                                                <div class="row g-0">
                                                    <div class="col-md-3 p-3">
                                                        <img src="{{ $img }}" alt="{{ $ten_sp }}"
                                                            class="img-fluid h-100 rounded border img-cart__order">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="card-body h-100">
                                                            <div class="row h-100">
                                                                <div class="col-12">
                                                                    <h4 class="card-title fw-bold mb-2">
                                                                        {{ $ten_sp }}
                                                                    </h4>
                                                                    <div class="d-flex gap-3 align-items-center">
                                                                        <h5 class="fw-bold mb-3">
                                                                            {{ number_format($giatrisp, 0, ',', '.') }} VNĐ
                                                                        </h5>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                        <div>
                                                                            <span class="fw-bold">Thương hiệu:
                                                                            </span><span>{{ $cate_name }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">Size:
                                                                            </span><span>{{ $size }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">SL:
                                                                            </span><span>{{ $quantity }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">Màu sắc:
                                                                            </span><span>{{ $color }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <form
                                                                            action="{{ route('delProduct.cart', $proSlug) }}"
                                                                            method="post">@csrf
                                                                            <input name="giatri_donhang"
                                                                                value="{{ $giatri_donhang }}" hidden>
                                                                            <button class="border-0" type="submit"><i
                                                                                    class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!$color && !$size)
                                                <div class="row g-0">
                                                    <div class="col-md-3 p-3">
                                                        <img src="{{ $img }}" alt="{{ $ten_sp }}"
                                                            class="img-fluid h-100 rounded border img-cart__order">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <div class="card-body h-100">
                                                            <div class="row h-100">
                                                                <div class="col-12">
                                                                    <h4 class="card-title fw-bold mb-2">
                                                                        {{ $ten_sp }}
                                                                    </h4>
                                                                    <div class="d-flex gap-3 align-items-center">
                                                                        <h5 class="fw-bold mb-3">
                                                                            {{ number_format($giatrisp, 0, ',', '.') }} VNĐ
                                                                        </h5>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                        <div>
                                                                            <span class="fw-bold">Thương hiệu:
                                                                            </span><span>{{ $cate_name }}</span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="fw-bold">SL:
                                                                            </span><span>{{ $quantity }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <form
                                                                            action="{{ route('delProduct.cart', $proSlug) }}"
                                                                            method="post">@csrf
                                                                            <input name="giatri_donhang"
                                                                                value="{{ $giatri_donhang }}" hidden>
                                                                            <button class="border-0" type="submit"><i
                                                                                    class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                @if (!$size)
                                                    <div class="row g-0">
                                                        <div class="col-md-3 p-3">
                                                            <img src="{{ $img }}" alt="{{ $ten_sp }}"
                                                                class="img-fluid h-100 rounded border img-cart__order">
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="card-body h-100">
                                                                <div class="row h-100">
                                                                    <div class="col-12">
                                                                        <h4 class="card-title fw-bold mb-2">
                                                                            {{ $ten_sp }}
                                                                        </h4>
                                                                        <div class="d-flex gap-3 align-items-center">
                                                                            <h5 class="fw-bold mb-3">
                                                                                {{ number_format($giatrisp, 0, ',', '.') }}
                                                                                VNĐ
                                                                            </h5>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                                                            <div>
                                                                                <span class="fw-bold">Thương hiệu:
                                                                                </span><span>{{ $cate_name }}</span>
                                                                            </div>
                                                                            <div>
                                                                                <span class="fw-bold">SL:
                                                                                </span><span>{{ $quantity }}</span>
                                                                            </div>
                                                                            <div>
                                                                                <span class="fw-bold">Màu sắc:
                                                                                </span><span>{{ $color }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <form
                                                                                action="{{ route('delProduct.cart', $proSlug) }}"
                                                                                method="post">@csrf
                                                                                <input name="giatri_donhang"
                                                                                    value="{{ $giatri_donhang }}" hidden>
                                                                                <button class="border-0" type="submit"><i
                                                                                        class="bx bxs-trash fs-5 text-danger"></i></button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        @endif
                                        @php
                                            $sosanpham += $quantity;
                                        @endphp
                                    @endforeach

                                    <div class="col border-bottom px-3"></div>
                                    <div class="row">
                                        <div class="col-lg-6 mt-3">
                                            <div class="container-fluid px-0">
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <a href="{{ route('product.page') }}"
                                                            class="btn-del__cart btn-add__pro">
                                                            <span class="button__text">Mua thêm hàng</span>
                                                            <span class="button__icon">
                                                                <i class='bx bx-shopping-bag'></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <a href="{{ route('del.cart') }}" class="btn-del__cart">
                                                            <span class="button__text">Xóa giỏ hàng</span>
                                                            <span class="button__icon">
                                                                <i class='bx bxs-trash'></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 p-3">
                            <h4 class="text-center mb-3 p-3">Thanh toán</h4>
                            <form action="{{ route('checkCoupon.cart') }}" method="POST" id="checkCoupon">
                                @csrf
                                <div class="mb-4 position-relative">
                                    <label for="coupon" class="form-label">Mã giảm giá</label>
                                    <input type="text" name="coupon" class="form-control" id="coupon"
                                        placeholder="Nhập tại đây...">
                                    <small class="text-danger text-error fst-italic position-absolute">a</small>
                                    <input type="text" name="giatri_donhang" value="{{ $giatri_donhang }}" hidden>
                                    @if ($errors->has('coupon'))
                                        @foreach ($errors->get('coupon') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="submit" class="apply-discount-button rounded btn-order">Kiểm tra</button>
                            </form>
                            @isset($coupon_data)
                                <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                                    <b>Bạn đã giảm được
                                        {{ $coupon_data->coupon_condition == 1 ? number_format($coupon_data->coupon_value, 0, ',', '.') . ' VNĐ' : $coupon_data->coupon_value . '%' }}
                                        cho đơn hàng này.</b> <br>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                    <form action="{{ route('removeCoupon.cart') }}" method="post">@csrf
                                        <sub>
                                            <button type="submit" class="border-0">
                                                Loại bỏ thẻ giảm giá
                                            </button>
                                        </sub>
                                    </form>
                                </div>
                            @endisset
                            <hr class="mt-4 mb-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold">TỔNG TIỀN: </h6>
                                <h5 class="fw-bold">
                                    {{ number_format($giatri_donhang, 0, ',', '.') }}
                                    VNĐ
                                </h5>
                            </div>
                            @isset($coupon_data)
                                <div class="d-flex justify-content-between  ">
                                    @php
                                        if ($coupon_data->coupon_condition == 0) {
                                            $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                        }
                                    @endphp
                                    <div class="d-flex mb-2 align-items-center gap-1">
                                        <h6 class="fw-bold mb-0">MÃ GIẢM GIÁ:</h6>
                                        <span>
                                            {{ $coupon_data->coupon_condition == 0 ? '('. $coupon_data->coupon_value . '%):' : '' }}
                                        </span>
                                    </div>

                                    <h5>
                                        {{ $coupon_data->coupon_condition == 1
                                            ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.') . ' VNĐ'
                                            : '- ' . number_format($discount, 0, ',', '.') . ' VNĐ' }}
                                    </h5>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-1">
                                        <h6 class="fw-bold mb-0"> THÀNH TIỀN</h6>
                                        <span class="">({{ $sosanpham }} sản phẩm):</span>
                                    </div>
                                    <h4 class="mb-0 fw-bold">
                                        @if ($coupon_data)
                                            @if (isset($discount))
                                                {{ number_format($giatri_donhang - $discount, 0, ',', '.') }}
                                            @else
                                                {{ number_format($giatri_donhang - $coupon_data->coupon_value, 0, ',', '.') }}
                                            @endif
                                        @else
                                            {{ number_format($giatri_donhang, 0, ',', '.') }}
                                        @endif
                                        VNĐ
                                    </h4>
                                </div>
                            @endisset
                            <a href="{{ route('product.checkout') }}"
                                class="btn-checkout rounded btn-order form-control mt-2">Thanh toán</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Product Cart -->
    </section>
@endsection
@push('script-access')
    <script src="{{ asset('frontend/js/pro-detail.js') }}"></script>
    <script>
        const formCoupon = document.getElementById("checkCoupon");
        const couponCheck = document.getElementById("coupon");

        let isCouponValid = false;

        formCoupon.addEventListener("submit", (e) => {
            e.preventDefault();

            let isCouponValid = checkCoupon();
            
            if(couponCheck) {
                couponCheck.addEventListener("keyup", () => {
                    isCouponValid = checkCoupon();
                });
            }

            if (isCouponValid) {
                formCoupon.submit();
            }
        });

        function checkCoupon() {
            const couponValue = couponCheck.value.trim();
            if (couponValue === '') {
                couponCheck.classList.add("input-error");
                setErrorFor(couponCheck, 'Không được để trống');
                couponCheck.focus();
                return false;
            } else if (couponValue.length < 3) {
                couponCheck.classList.add("input-error");
                setErrorFor(couponCheck, 'Mã giảm quá ngắn');
                couponCheck.focus();
                return false;
            } else if(!checkCouponRegex(couponValue)) {
                couponCheck.classList.add("input-error");
                setErrorFor(couponCheck, 'Không nhập ký tự đặc biệt');
                couponCheck.focus();
                return false;
            } else if(!checkCouponRegexLc(couponValue)) {
                couponCheck.classList.add("input-error");
                setErrorFor(couponCheck, 'Phải là chữ in hoa');
                couponCheck.focus();
                return false;
            }else {
                setSuccessFor(couponCheck);
                return true;
            }
        }

        function checkCouponRegex(couponCheck) {
            return /^[a-zA-Z0-9]+$/.test(couponCheck);
        }

        function checkCouponRegexLc(couponCheck) {
            return /^[A-Z0-9]+$/.test(couponCheck);
        }

        function setErrorFor(input, message) {
            const formControl = input.parentElement;
            const small = formControl.querySelectorAll('small');
            small.forEach(element => {
                element.innerText = message;
            });
        }

        function setSuccessFor(input) {
            const formControl = input.parentElement;
            const formChild = formControl.querySelector(".form-control");
            formChild.className = "form-control input-success";
        }

    </script>
@endpush