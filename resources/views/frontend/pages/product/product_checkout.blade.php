@extends('frontend.index')

@section('title')
    Thanh toán đơn hàng
@endsection

@section('banner')
    <!-- Start banner Area -->
   
    <!-- End banner Area -->
@endsection

@section('content')
    <div class="modal fade" id="AddressModalToggle" aria-hidden="true" aria-labelledby="AddressModalToggleLabel"
        tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AddressModalToggleLabel">Địa chỉ của tôi</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('deliInfoSelected') }}" method="POST">@csrf
                    <div class="modal-body">
                        <div class="form-check">
                            @if (!empty($InfoDeli) && count($InfoDeli) > 0)
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($InfoDeli as $Inf)
                                    @if ($Inf->info_default == 1)
                                        <input class="form-check-input" name="selectInfoDefault" value="{{ $Inf->info_id }}"
                                            type="radio" name="flexRadioDefault" id="flexRadioDefault{{ $i }}"
                                            checked>
                                    @else
                                        <input class="form-check-input" name="selectInfoDefault" value="{{ $Inf->info_id }}"
                                            type="radio" name="flexRadioDefault" id="flexRadioDefault{{ $i }}">
                                    @endif
                                    <label class="form-check-label d-flex justify-content-between align-items-center"
                                        for="flexRadioDefault{{ $i }}">
                                        <div>
                                            <strong>{{ $Inf->info_name }}</strong> | <b>{{ $Inf->info_phone }}</b><br>
                                            <span>{{ $Inf->info_email }}</span><br>
                                            <span>{{ $Inf->info_address }},</span><br>
                                            <span>{{ $Inf->info_ward }}, {{ $Inf->info_district }}, {{ $Inf->info_province }}</span><br>
                                            @if ($Inf->info_default == 1)
                                                <strong class="uk7Wpm">Mặc định</strong>
                                            @endif
                                        </div>
                                        <div>
                                            <button style="width: max-content;" class="text-button-ad" type="button"
                                                data-bs-target="#ChangeAddressModal{{ $Inf->info_id }}"
                                                data-bs-toggle="modal">Thay đổi</button>
                                        </div>
                                    </label>
                                    <hr>
                                    @php
                                        $i++;
                                    @endphp
                                @endforeach
                            @else
                                <b>Không có địa chỉ nhận hàng.</b>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">

                        <button type="button" class="border grey-hover border-1 mx-2 custom-btn text-dark " data-bs-target="#CreateAddressModal"
                            data-bs-toggle="modal">Thêm địa chỉ mới</button>
                        @if (!empty($InfoDeli) && count($InfoDeli) > 0)
                            <button type="submit" class="custom-btn bgc-o text-white">Xác nhận</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    @foreach ($InfoDeli as $Inf)
        <div class="modal fade" id="ChangeAddressModal{{ $Inf->info_id }}" aria-hidden="true"
            aria-labelledby="ChangeAddressModalLabel" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="ChangeAddressModalLabel">Cập nhật địa chỉ</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('diachi.update', $Inf->info_id) }}" method="post" class="form-add-delivery" id="form-add-delivery">
                        @csrf @method('PATCH')
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6">
                                    <label for="lname" class="form-label">Họ & tên</label>
                                    <input type="text" class="form-control lname" value="{{ $Inf->info_name }}"
                                        name="info_name" id="lname" placeholder="Nguyễn Văn A">
                                    @if ($errors->has('info_name'))
                                        @foreach ($errors->get('info_name') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error fst-italic">a</small>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-control phone" value="{{ $Inf->info_phone }}"
                                        name="info_phone" id="phone" placeholder="Số điện thoại">
                                    @if ($errors->has('info_phone'))
                                        @foreach ($errors->get('info_phone') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error fst-italic">a</small>
                                </div>
                                <div class="col-lg-12 col-md-12 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control email" value="{{ $Inf->info_email }}"
                                        name="info_email" id="email" placeholder="Địa chỉ email">
                                    @if ($errors->has('info_email'))
                                        @foreach ($errors->get('info_email') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error fst-italic">a</small>
                                </div>
                                <div class="col-lg-6 col-md-6 my-data-province" id="my-province">
                                    <label for="province" class="form-label">Tỉnh / Thành phố</label>
                                    <select class="form-select province-data province-select" id="province" aria-label="Default select example">
                                        <option selected>Chọn tỉnh / thành phố</option>
                                    </select>
                                    @if ($errors->has('info_province'))
                                        @foreach ($errors->get('info_province') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error-data fst-italic">a</small>
                                    <input type="hidden" id="info_province" name="info_province">
                                </div>
                                <div class="col-lg-6 col-md-6 my-data-district" id="my-district">
                                    <label for="district" class="form-label">Quận/ Huyện</label>
                                    <select class="form-select district-data district-select" id="district" aria-label="Default select example">
                                        <option selected>Chọn Quận / Huyện</option>
                                    </select>
                                    @if ($errors->has('info_district'))
                                        @foreach ($errors->get('info_district') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error-data fst-italic">a</small>
                                    <input type="hidden" id="info_district" name="info_district">
                                    <input type="hidden" id="districtFee">
                                </div>
                                <div class="col-lg-6 col-md-6 my-data-ward" id="my-ward">
                                    <label for="ward" class="form-label">Phường / Xã</label>
                                    <select class="form-select ward-data ward-select" id="ward">
                                        <option selected>Chọn Phường / Xã</option>
                                    </select>
                                    @if ($errors->has('info_ward'))
                                        @foreach ($errors->get('info_ward') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error-data fst-italic">a</small>
                                    <input type="hidden" id="info_ward" name="info_ward">
                                    <input type="hidden" id="wardFee">
                                    <input type="hidden" id="priceFeeForm" name="infoFeeFormUp" value="25000">
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <label for="address" class="form-label">Địa chỉ</label>
                                    <input type="text" class="form-control address" value="{{ $Inf->info_address }}"
                                        name="info_address" id="address" placeholder="Địa chỉ cụ thể">
                                    @if ($errors->has('info_address'))
                                        @foreach ($errors->get('info_address') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error fst-italic">a</small>
                                </div>
                                <div class="col-lg-12 col-md-12">
                                    <label for="service" class="form-label">Chuyển phát</label>
                                    <select name="infoService" id="service" class="form-select service-data service-select">
                                        <option selected value ="">Chuyển phát</option>
                                    </select>
                                    @if ($errors->has('infoService'))
                                        @foreach ($errors->get('infoService') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="text-danger text-error-data fst-italic">a</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" data-bs-target="#AddressModalToggle" data-bs-toggle="modal"
                                class="border grey-hover border-1 mx-2 custom-btn text-dark ">Quay lại</button>
                            <button type="submit" class="custom-btn bgc-o text-white" id="btn-add-delivery">Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="CreateAddressModal" aria-hidden="true" aria-labelledby="CreateAddressModalToggleLabel2"
        tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content form-parent-delivery">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="CreateAddressModalToggleLabel2">Thêm mới thông tin người nhận</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('diachi.store') }}" method="post" class="form-add-delivery" id="form-add-delivery">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-lg-6 col-md-6">
                                <label for="lname" class="form-label">Họ & tên</label>
                                <input type="text" class="form-control lname" name="info_name" id="lname" value="{{old('info_name')}}"
                                    placeholder="Nguyễn Văn A">
                                @if ($errors->has('info_name'))
                                    @foreach ($errors->get('info_name') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error fst-italic">a</small>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control phone" name="info_phone" id="phone" value="{{old('info_phone')}}"
                                    placeholder="Số điện thoại">
                                @if ($errors->has('info_phone'))
                                    @foreach ($errors->get('info_phone') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error fst-italic">a</small>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control email" name="info_email" id="email" value="{{old('info_email')}}"
                                    placeholder="Địa chỉ email">
                                @if ($errors->has('info_email'))
                                    @foreach ($errors->get('info_email') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error fst-italic">a</small>
                            </div>
                            <div class="col-lg-6 col-md-6 my-data-province" id="my-province">
                                <label for="province" class="form-label">Tỉnh / Thành phố</label>
                                <select class="form-select province-data province-select" id="province" aria-label="Default select example">
                                    <option selected>Chọn tỉnh / thành phố</option>
                                </select>
                                @if ($errors->has('info_province'))
                                    @foreach ($errors->get('info_province') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error-data fst-italic">a</small>
                                <input type="hidden" id="info_province" name="info_province">
                            </div>
                            <div class="col-lg-6 col-md-6 my-data-district" id="my-district">
                                <label for="district" class="form-label">Quận/ Huyện</label>
                                <select class="form-select district-data district-select" id="district" aria-label="Default select example">
                                    <option selected>Chọn Quận / Huyện</option>
                                </select>
                                @if ($errors->has('info_district'))
                                    @foreach ($errors->get('info_district') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error-data fst-italic">a</small>
                                <input type="hidden" id="info_district" name="info_district">
                                <input type="hidden" id="districtFee">
                            </div>
                            <div class="col-lg-6 col-md-6 my-data-ward" id="my-ward">
                                <label for="ward" class="form-label">Phường / Xã</label>
                                <select class="form-select ward-data ward-select" id="ward">
                                    <option selected>Chọn Phường / Xã</option>
                                </select>
                                @if ($errors->has('info_ward'))
                                    @foreach ($errors->get('info_ward') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error-data fst-italic">a</small>
                                <input type="hidden" id="info_ward" name="info_ward">
                                <input type="hidden" id="wardFee">
                                <input type="hidden" id="priceFeeForm" name="infoFeeForm" value="25000">
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label for="address" class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control address" name="info_address" id="address" value="{{old('info_address')}}"
                                    placeholder="Địa chỉ cụ thể">
                                @if ($errors->has('info_address'))
                                    @foreach ($errors->get('info_address') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error fst-italic">a</small>
                            </div>
                            <div class="col-12">
                                <label for="service" class="form-label">Chuyển phát</label>
                                <select name="infoService" id="service" class="form-select service-data service-select">
                                    <option selected value = "">Chuyển phát</option>
                                </select>
                                @if ($errors->has('infoService'))
                                    @foreach ($errors->get('infoService') as $error)
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small>
                                    @endforeach
                                @endif
                                <small class="text-danger text-error-data fst-italic">a</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-target="#AddressModalToggle" data-bs-toggle="modal"
                            class="border grey-hover border-1 mx-2 custom-btn text-dark ">Quay lại</button>
                        <button type="submit" class="custom-btn bgc-o text-white" id="btn-add-delivery">Thêm mới</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <section class="overflow-hidden">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center" data-aos="fade-right" data-aos-duration="2000">
                    <div class="section-title">
                        <h1>Thanh toán đơn hàng</h1>
                        <p>
                            Thanh toán đơn hàng dễ dàng và an toàn tại cửa hàng của chúng tôi
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 box__shadow-round rounded">
                <div class="container-xxl">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="vtrWey"></div><br>
                            <div class="container-fluid px-0">
                                <div class="row">
                                    <div class="cart__top bt_hr mb-3">
                                        <h4 class="text-center fw-bold mb-3 text-uppercase">Đơn hàng của bạn</h4>
                                    </div>
                                   
                                </div>
                                <div class="row">
                                    <h5 class="text-uppercase fw-bold mb-3">Địa chỉ nhận hàng</h5>
                                    @if (!empty($InfoDeli) && count($InfoDeli) > 0)
                                        <?php $hasDefaultInfo = false; ?>
                                        @foreach ($InfoDeli as $Inf)
                                            @if ($Inf->info_default == 1)
                                                <?php $hasDefaultInfo = true; ?>
                                                <div
                                                    class="col-lg-12 col-md-12 mb-3 bt_hr">
                                                    <div class="bg-light d-flex px-3 py-4 justify-content-between align-items-center rounded mb-3 flex-wrap">
                                                        <div>
                                                            <strong>{{ $Inf->info_name }}</strong> |
                                                            <b>{{ $Inf->info_phone }}</b> |
                                                            <b>{{ $Inf->info_email }}</b><br>
                                                            <span>{{ $Inf->info_address }},</span>
                                                            <span>{{ $Inf->info_ward }}, {{ $Inf->info_district }}, {{ $Inf->info_province }}</span><br>
                                                            <strong class="uk7Wpm">Mặc định</strong>
                                                        </div>
                                                        
                                                        <div>
                                                            <button type="button" class="text-button-ad"
                                                                data-bs-target="#AddressModalToggle" data-bs-toggle="modal">Thay
                                                                đổi</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @break;
                                            @endif
                                        @endforeach
            
                                        @if (!$hasDefaultInfo)
                                            <div class="col-12 p-3">
                                                <div class="container px-2">
                                                    <div class="row">
                                                        <div class="col-12 bg-light rounded p-3">
                                                            <button class="text-button-ad text-start w-100 " data-bs-target="#AddressModalToggle"
                                                                data-bs-toggle="modal">+ Lựa chọn địa chỉ giao hàng</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="col-12 p-3">
                                            <div class="container px-2">
                                                <div class="row">
                                                    <div class="col-12 bg-light rounded p-3">
                                                        <button class="text-button-ad text-start " data-bs-target="#AddressModalToggle"
                                                            data-bs-toggle="modal">+ Thêm địa chỉ giao hàng</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-lg-8 mb-3">
                                        <label for="floatingTextarea2" class="form-label">Lưu ý cho người bán</label>
                                        <textarea class="form-control" id="floatingTextarea2" cols="5" rows="4" onkeyup="updateNote()"></textarea>
                                    </div>
                                    <div class="col-lg-4 mb-3 h-100">
                                        <label for="" class="form-label fw-bold">Đơn vị vận chuyển</label>
                                        <div class="bg-light p-3 h-100 rounded bd_hr d-flex flex-column">
                                            <span class="text-dvvc fw-bold">Giao hàng nhanh</span>
                                            <small>Nhận hàng vào 22 Th10 - 22 Th10</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <h5 class="text-uppercase fw-bold mb-3">Hình thức thanh toán</h5>
                                    <div class="col-lg-4 mb-3">
                                        <input type="radio" class="btn-check btn-tst" value="cod" name="btnradio"
                                            id="btnradio1" autocomplete="off" checked>
                                        <label class="btn border p-2 d-flex justify-content-center gap-3 align-items-center"
                                            for="btnradio1">
                                            <span>
                                                Khi nhận hàng
                                            </span>
                                            <img src="/frontend/img/delivery-bike.png" alt="" width="13%" height="100%">
                                        </label>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <input type="radio" class="btn-check btn-tst" value="redirect" name="btnradio"
                                            id="btnradio2" autocomplete="off">
                                        <label class="btn border p-2 d-flex justify-content-center gap-3 align-items-center"
                                            for="btnradio2">
                                            <span>
                                                VNPay
                                            </span>
                                            <img src="/frontend/img/VNPAY.png" alt="" width="13%" height="100%">
                                        </label>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <input type="radio" class="btn-check btn-tst" value="payUrl" name="btnradio"
                                            id="btnradio3" autocomplete="off">
                                        <label class="btn border p-2 d-flex justify-content-center gap-3 align-items-center"
                                            for="btnradio3">
                                            <span>
                                                Momo
                                            </span>
                                            <img src="/frontend/img/momo.png" alt="" width="13%" height="100%">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <br>
                            <h4 class="fw-bold mb-3 text-center text-uppercase" style="margin-top: 3px;">Sản phẩm đã mua</h4>
                            @php
                                $phivanchuyen = DB::table('delivery_info')->where('user_id', Auth::id())
                                ->where('info_default', 1)
                                ->value('info_delivery_fee');
                                $giatri_donhang = 0;
                                $thanhtien = 0;

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
                                    @if ($color == '' && $size == '')
                                        <div class="d-flex gap-3 mb-3">
                                            <img src="{{ $img }}" class="img-payment__new rounded" alt="{{ $ten_sp }}">
                                            <div>
                                                <h6 class="text__truncate mb-1 fw-bold">{{ $ten_sp }}</h6>
                                                <div class="d-flex gap-3 flex-wrap">
                                                    <span class="fw-bold">SL: <span class="fw-normal">{{ $quantity }}</span></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2 gap-3 flex-wrap">
                                                    <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($giasale, 0, ',', '.') }} VNĐ</h5>
                                                    <del class="text-decoration-line-through">{{ number_format($giabandau, 0, ',', '.') }} VNĐ</del>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex gap-3 mb-3">
                                            <img src="{{ $img }}" class="img-payment__new rounded" alt="{{ $ten_sp }}">
                                            <div>
                                                <h6 class="text__truncate mb-1 fw-bold">{{ $ten_sp }}</h6>
                                                <div class="d-flex gap-3 flex-wrap">
                                                    <span class="fw-bold">Màu: <span class="fw-normal">{{ $color }}</span></span>
                                                    <span class="fw-bold">Size: <span class="fw-normal">{{ $size }}</span></span>
                                                    <span class="fw-bold">SL: <span class="fw-normal">{{ $quantity }}</span></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2 gap-3 flex-wrap">
                                                    <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($giasale, 0, ',', '.') }} VNĐ</h5>
                                                    <del class="text-decoration-line-through">{{ number_format($giabandau, 0, ',', '.') }} VNĐ</del>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    @php
                                        $giatrisp = $gia * $quantity;
                                        $giatri_donhang += $giatrisp;
                                    @endphp
                                    @if ($color == '' && $size == '')
                                        <div class="d-flex gap-3 mb-3">
                                            <img src="{{ $img }}" class="img-payment__new rounded" alt="{{ $ten_sp }}">
                                            <div>
                                                <h6 class="text__truncate mb-1 fw-bold">{{ $ten_sp }}</h6>
                                                <div class="d-flex gap-3 flex-wrap">
                                                    <span class="fw-bold">SL: <span class="fw-normal">{{ $quantity }}</span></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($giatrisp, 0, ',', '.') }} VNĐ</h5>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="d-flex gap-3 mb-3">
                                                    <img src="{{ $img }}" class="img-payment__new rounded" alt="{{ $ten_sp }}">
                                                    <div>
                                                        <h6 class="text__truncate mb-1 fw-bold">{{ $ten_sp }}</h6>
                                                        <div class="d-flex gap-3 flex-wrap">
                                                            <span class="fw-bold">Màu: <span class="fw-normal">{{ $color }}</span></span>
                                                            <span class="fw-bold">Size: <span class="fw-normal">{{ $size }}</span></span>
                                                            <span class="fw-bold">SL: <span class="fw-normal">{{ $quantity }}</span></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                                            <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($giatrisp, 0, ',', '.') }} VNĐ</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                @php
                                    $sosanpham += $quantity;
                                @endphp
                            @endforeach
                            <hr class="mb-4">
                            <div class="mb-3">
                                <h5 class="text-uppercase fw-bold">Sneaker Voucher</h5>
                                <div class="input-group mb-3">
                                    <form action="{{ route('checkCoupon.cart') }} " class="d-flex w-100" method="post">
                                        @csrf
                                        <input type="text" name="coupon" class="form-control coupon_input"
                                            placeholder="Nhập mã để kiểm tra ngay ...">
                                        <input type="text" name="giatri_donhang" value="{{ $giatri_donhang }}" hidden>
                                        <button type="submit" class="input-group-text discount-button border-0">Kiểm tra</button>

                                    </form>
                                    @if ($errors->has('coupon'))
                                        @foreach ($errors->get('coupon') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            @isset($coupon_data)
                                <div class="mb-3 w-100">
                                    <div class="alert alert-success alert-dismissible fade show " role="alert">
                                        <b>Bạn đã giảm được
                                            {{ $coupon_data->coupon_condition == 1
                                                ? number_format($coupon_data->coupon_value, 0, ',', '.') . ' VNĐ'
                                                : $coupon_data->coupon_value . '%' }}
                                            cho đơn hàng này.
                                        </b> <br>
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
                                </div>
                            @endisset
                            <div class="cart__bottom mb-4">
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase">Giá trị sản phẩm <small class="fw-normal">({{ $sosanpham }} sản phẩm):</small></h5>
                                    <label class="font__size">{{ number_format($giatri_donhang, 0, ',', '.') }} VNĐ</label>
                                </div>
            
                                @isset($phivanchuyen)
                                    <div class="d-flex justify-content-between align-items-center gap-1">
                                        <h6 class="fw-bold text-uppercase">Phí vận chuyển:</h6>
                                        <label class="font__size" id="totalFee">
                                            @foreach ($InfoDeli as $item)
                                                @if ($item->info_default == 1)
                                                    {{ number_format($item->info_delivery_fee, 0, ',', '.') . ' VNĐ' }}
                                                @endif
                                            @endforeach
                                        </label>
                                        <input type="hidden" name="totalFee" id="hiddenFee">
                                    </div>
                                @endisset
            
                                @isset($coupon_data)
                                    <div class="d-flex justify-content-between align-items-center gap-1">
                                        <h6 class="fw-bold text-black text-uppercase mb-0">Mã giảm giá:
                                            @if ($coupon_data->coupon_condition == 0)
                                                {{"(" . $coupon_data->coupon_value . "%)"}}
                                            @endif
                                        </h6>
                                        <label class="font__size">
                                            @php
                                                if ($coupon_data->coupon_condition == 0) {
                                                    $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                                }
                                            @endphp
                                            {{ $coupon_data->coupon_condition == 1
                                                ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.') . ' VNĐ'
                                                : '- ' . number_format($discount, 0, ',', '.') . ' VNĐ' }}
                                        </label>
                                    </div>
                                @endisset
                                
                                <hr class="mb-3">
                                <div class="mb-3 d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase mb-0">Thành tiền: </h6>
                                    <h4 class="fw-bold mb-0">
                                        @if ($coupon_data)
                                            @if (isset($discount))
                                                @php
                                                    $thanhtien = $giatri_donhang - $discount + $phivanchuyen;
                                                @endphp
                                                {{ number_format($thanhtien, 0, ',', '.') }}
                                            @else
                                                @php
                                                    $thanhtien = $giatri_donhang - $coupon_data->coupon_value + $phivanchuyen;
                                                @endphp
                                                {{ number_format($thanhtien, 0, ',', '.') }}
                                            @endif
                                        @else
                                            @php
                                                $thanhtien = $giatri_donhang + $phivanchuyen;
                                            @endphp
                                            {{ number_format($thanhtien, 0, ',', '.') }}
                                        @endif
                                        VNĐ
                                    </h4>
                                </div>
                            </div>
                            <form action="{{ route('product.checkoutPOST') }}" method="post" id="checkoutForm">
                                @csrf
                                @php
                                    $date = date('dmY');
                                    $random_number = rand(0000, 9999);
                                    $check_oc_exist = DB::table('order')
                                        ->where('order_code', '=', $date . $random_number)
                                        ->first();
                                    if ($check_oc_exist) {
                                        $random_number = rand(0000, 9999);
                                    }
                                    $order_code = $date . $random_number;
                                @endphp
                                <input type="hidden" name="deliFee" value="{{$item->info_delivery_fee ?? 0}}">
                                @php
                                    $defaultValue = null;
            
                                    if(isset($coupon_data) && isset($coupon_data->coupon_condition) && isset($giatri_donhang)){
                                        if ($coupon_data->coupon_condition == 1) {
                                            $defaultValue = $coupon_data->coupon_value;
                                        } else {
                                            $defaultValue = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                        }
                                    }
                                @endphp
            
                                <input type="hidden" name="couVal" value="{{ $defaultValue ?? 0 }}">
                                <input type="hidden" name="order_code" value="{{ $order_code }}">
                                <input type="hidden" name="note_customer" value="" id="note_customer">
                                <input type="hidden" value="{{ $thanhtien }}" name="thanhtien">
                                <input type="hidden" name="payment" value="cod">
                                <button type="submit" name="cod"
                                    class="btn btn-primary border-0 w-100 btn-checkout text-white font__size">Đặt Hàng</button>
            
                            </form>
                            <div class="my-3">
                                <span>Nhấn "<span class="fw-bold">Đặt Hàng</span>" đồng nghĩa với việc bạn đồng ý với các chính sách
                                    <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-thanh-toan.html" target="_blank">thanh toán</a>,
                                    <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-bao-hanh-va-doi-tra.html" target="_blank">bảo hành & đổi trả</a> và
                                    <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-giao-hang.html" target="_blank">giao hàng</a> của chúng tôi.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        const radioButtons = document.querySelectorAll('.btn-check');
        const checkoutForm = document.getElementById('checkoutForm');

        radioButtons.forEach(radioButton => {
            radioButton.addEventListener('change', function() {
                const selectedValue = document.querySelector('input[name="btnradio"]:checked').value;
                checkoutForm.querySelector('input[name="payment"]').value = selectedValue;
                checkoutForm.querySelector('button[type="submit"]').name = selectedValue;
            });
        });

        function updateNote() {
            var noteValue = document.getElementById("floatingTextarea2").value;
            document.getElementById("note_customer").value = noteValue;
        }
    </script>
@endsection