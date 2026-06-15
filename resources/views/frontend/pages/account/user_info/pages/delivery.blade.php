@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
    <div>
        <h3 class="mb-2">Địa chỉ của tôi</h3>
        <div class="mb-3 p-4 bg-white">
            <div class="row">
                <div class="col-md-6 col-4">
                    <h4>Địa chỉ</h4>
                </div>
                <div class="col-md-6 col-8 d-flex justify-content-end">
                    <button class="btn-address text-white" data-bs-toggle="modal" data-bs-target="#addAddress"><i
                            class='bx bx-plus pe-2 text-white'></i>Thêm địa
                        chỉ mới</button>
                    <!-- Modal -->
                    <div class="modal fade" id="addAddress" tabindex="-1" aria-labelledby="addAddressLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="addAddressLabel">Thêm địa chỉ mới
                                    </h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form action="{{ route('diachi.store') }}" method="post" id="formDelivery">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 col-12 mb-4 position-relative">
                                                <label class="form-label" for="">Họ tên</label>
                                                <input class="form-control" name="info_name" placeholder="Nhập tên"
                                                    value="" type="text" id="fullNameDeli">
                                                @if ($errors->has('info_name'))
                                                    @foreach ($errors->get('info_name') as $error)
                                                        <small class="text-danger fst-italic">
                                                            {{ $error }}
                                                        </small>
                                                    @endforeach
                                                @endif
                                                <small class="text-danger text-error fst-italic position-absolute">a</small>
                                            </div>
                                            <div class="col-md-6 col-12 mb-4 position-relative">
                                                <label class="form-label" for="">Số điện
                                                    thoại</label>
                                                <input class="form-control" name="info_phone"
                                                    placeholder="Nhập số điện thoại" value="" type="text"
                                                    id="phoneDeli">
                                                @if ($errors->has('info_phone'))
                                                    @foreach ($errors->get('info_phone') as $error)
                                                        <small class="text-danger fst-italic">
                                                            {{ $error }}
                                                        </small>
                                                    @endforeach
                                                @endif
                                                <small class="text-danger text-error fst-italic position-absolute">a</small>
                                            </div>
                                            <div class="col-md-6 col-12 mb-4 position-relative">
                                                <label class="form-label" for="">Email</label>
                                                <input class="form-control" name="info_email" placeholder="Nhập email"
                                                    value="" type="email" id="emailDeli">
                                                @if ($errors->has('info_email'))
                                                    @foreach ($errors->get('info_email') as $error)
                                                        <small class="text-danger fst-italic">
                                                            {{ $error }}
                                                        </small>
                                                    @endforeach
                                                @endif
                                                <small class="text-danger text-error fst-italic position-absolute">a</small>
                                            </div>
                                            <div class="col-lg-6 col-md-6 my-data-province" id="my-province">
                                                <label for="province" class="form-label">Tỉnh / Thành phố</label>
                                                <select class="form-select province-data province-select" id="province"
                                                    aria-label="Default select example">
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
                                                <select class="form-select district-data district-select" id="district"
                                                    aria-label="Default select example">
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
                                                <input type="hidden" id="priceFeeForm" name="infoFeeForm"
                                                    value="25000">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="">Địa chỉ cụ thể</label>
                                                <textarea class="form-control" name="info_address" id="addressDeli" cols="" rows="3"></textarea>
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
                                                <select name="infoService" id="service"
                                                    class="form-select service-data service-select">
                                                    <option selected value="">Chuyển phát</option>
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
                                        <button type="button"
                                            class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                            data-bs-dismiss="modal">Trở lại</button>
                                        <button type="submit" class="custom-btn bgc-o text-white">Thêm</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            @if (count($delivery) > 0)
                @php
                    $deliveryCount = count($delivery);
                @endphp
                @foreach ($delivery as $dl)
                    <!-- ---------------address--------------------- -->
                    <form action="{{ route('deliInfoDefault', [$dl->info_id]) }}" method="post">
                        @csrf
                        <div class="row">
                            <input type="text" value="1" hidden name="info_default">
                            <input type="text" hidden name="info_id" value="{{ $dl->info_id }}">
                            <div class="col-md-8 col-12">
                                <p><b>{{ $dl->info_name }}</b> | <b>{{ $dl->info_phone }}</Span></b>|
                                    <b>{{ $dl->info_email }}</Span></b>
                                <p>

                                    <span>{{ $dl->info_address }}, </span> <br>
                                    <span>{{ $dl->info_ward }}, {{ $dl->info_district }}, {{ $dl->info_province }}</span>
                                </p>
                                @if ($dl->info_default == 1)
                                    <span class="border border-1 pe-1 ps-1 border-danger text-danger">Mặc
                                        định</span>
                                @endif
                            </div>
                            <div class="col-md-4 col-12 m-auto">
                                <div class="d-flex justify-content-end gap-2">
                                    <a class="" href="#" data-bs-toggle="modal"
                                        data-bs-target="#updateInfo{{ $dl->info_id }}">Cập nhật</a>
                                    @if ($dl->info_default == 0 || $deliveryCount == 1)
                                        <a class="delete-link" href="{{ route('diachi.destroy', [$dl->info_id]) }}"
                                            onclick="event.preventDefault(); document.getElementById('delete-form-{{ $dl->info_id }}').submit();">Xóa</a>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button class="border border-1 p-1 text-dark ps-3 pe-3"
                                        @if ($dl->info_default == 1) disabled @endif>Thiết lập mặc
                                        định</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <hr>
                @endforeach
            @else
                <div class="">
                    <div class="d-flex justify-content-center">
                        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" width="90px" height="90px" viewBox="0 0 29 32"
                            enable-background="new 0 0 29 32" xml:space="preserve" fill="#000000">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round">
                            </g>
                            <g id="SVGRepo_iconCarrier">
                                <g>
                                    <path fill="#808184"
                                        d="M1.045,27.731l13.303,4.245C14.397,31.992,14.449,32,14.5,32c0.053,0,0.106-0.009,0.157-0.025l13.312-4.407 C28.595,27.36,29,26.803,29,26.145s-0.404-1.218-1.03-1.425l-7.733-2.56c-0.264-0.09-0.545,0.056-0.632,0.317 c-0.087,0.263,0.055,0.545,0.317,0.632l7.733,2.56C27.975,25.775,28,26.06,28,26.145c0,0.084-0.025,0.368-0.344,0.474 l-13.159,4.356L1.35,26.778C1.029,26.676,1.001,26.392,1,26.307c-0.001-0.084,0.021-0.368,0.339-0.478l8.028-2.753 c0.261-0.089,0.4-0.374,0.311-0.635c-0.09-0.262-0.375-0.401-0.635-0.311l-8.028,2.753C0.392,25.097-0.007,25.659,0,26.317 S0.417,27.53,1.045,27.731z">
                                    </path>
                                    <path fill="#808184"
                                        d="M15.679,0.063C9.81-0.595,4.817,3.982,4.817,9.705c0,2.24,0.729,4.23,2.355,6.442l6.31,9.121 c0.249,0.359,0.658,0.574,1.096,0.574h0c0.441,0,0.853-0.218,1.098-0.577l4.173-5.993l2.344-3.568 c1.655-2.094,2.361-4.718,1.988-7.388C23.581,4.022,20.005,0.551,15.679,0.063z M21.382,15.12l-2.362,3.592l-4.168,5.986 c-0.089,0.13-0.222,0.145-0.274,0.145s-0.185-0.014-0.274-0.143l-6.318-9.133c-1.5-2.041-2.169-3.85-2.169-5.862 C5.817,4.905,9.734,1,14.548,1c0.335,0,0.675,0.019,1.018,0.058c3.88,0.437,7.086,3.548,7.624,7.397 C23.525,10.852,22.892,13.206,21.382,15.12z">
                                    </path>
                                    <path fill="#808184"
                                        d="M14.497,4.705c-2.757,0-5,2.243-5,5s2.243,5,5,5s5-2.243,5-5S17.253,4.705,14.497,4.705z M14.497,13.705 c-2.206,0-4-1.794-4-4s1.794-4,4-4s4,1.794,4,4S16.702,13.705,14.497,13.705z">
                                    </path>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <p class="d-block text-center">Bạn chưa có địa chỉ giao hàng</p>
                </div>
            @endif
        </div>
        @foreach ($delivery as $dl)
            <!-- ---model------ -->
            <div class="modal fade" id="updateInfo{{ $dl->info_id }}" tabindex="-1"
                aria-labelledby="updateInfoLabel{{ $dl->info_id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="verifiedEmailLabel">Cập nhật địa chỉ</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('diachi.update', [$dl->info_id]) }}" method="post">
                                @csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="row">

                                        <div class="col-md-6 col-12 mb-3">
                                            <label class="form-label" for="">Họ tên</label>
                                            <input class="form-control" name="info_name" placeholder="Nhập tên"
                                                value="{{ $dl->info_name }}" type="text">
                                        </div>
                                        <div class="col-md-6 col-12 mb-3">
                                            <label class="form-label" for="">Số điện thoại</label>
                                            <input class="form-control" name="info_phone"
                                                placeholder="Nhập số điện thoại" value="{{ $dl->info_phone }}"
                                                type="text">
                                        </div>
                                        <div class="col-md-6 col-12 mb-3">
                                            <label class="form-label" for="">Email</label>
                                            <input class="form-control" name="info_email" placeholder="Nhập email"
                                                value="{{ $dl->info_email }}" type="email">
                                        </div>
                                        <div class="col-lg-6 col-md-6 my-data-province" id="my-province">
                                            <label for="province" class="form-label">Tỉnh / Thành phố</label>
                                            <select class="form-select province-data province-select" id="province"
                                                aria-label="Default select example">
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
                                            <select class="form-select district-data district-select" id="district"
                                                aria-label="Default select example">
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
                                        <div class="col-12">
                                            <label class="form-label" for="">Địa chỉ cụ thể</label>
                                            <textarea class="form-control" name="info_address" id="addressDeli" cols="" rows="3">{{$dl->info_address}}</textarea>
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
                                            <select name="infoService" id="service"
                                                class="form-select service-data service-select">
                                                <option selected value="">Chuyển phát</option>
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
                                    <button type="button" class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                        data-bs-dismiss="modal">Trở lại</button>
                                    <button type="submit" class="custom-btn bgc-o text-white">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <form id="delete-form-{{ $dl->info_id }}" action="{{ route('diachi.destroy', [$dl->info_id]) }}"
                method="POST" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>
@endsection

@push('script-access')
    <script>
        const formDelivery = document.getElementById("formDelivery");
        const fullName = document.getElementById("fullNameDeli");
        const phoneDeli = document.getElementById("phoneDeli");
        const emailDeli = document.getElementById("emailDeli");
        const addressDeli = document.getElementById("addressDeli");
        const provinceDeli = document.getElementById("province");
        const districtDeli = document.getElementById("district");
        const wardDeli = document.getElementById("ward");
        const serviceDeli = document.getElementById("service");

        let fullNameValid = false;
        let phoneDeliValid = false;
        let emailDeliValid = false;
        let addressDeliValid = false;
        let provinceDeliValid = false;
        let districtDeliValid = false;
        let wardDeliValid = false;
        let serviceDeliValid = false;

        if (formDelivery) {
            formDelivery.addEventListener("submit", (e) => {
                e.preventDefault();

                let fullNameValid = checkName();
                let phoneDeliValid = checkPhone();
                let emailDeliValid = checkEmail();
                let addressDeliValid = checkAddress();
                let provinceDeliValid = checkProvince();
                let districtDeliValid = checkDistrict();
                let wardDeliValid = checkWard();
                let serviceDeliValid = checkService();

                fullName.addEventListener("keyup", () => {
                    fullNameValid = checkName();
                });

                phoneDeli.addEventListener("keyup", () => {
                    phoneDeliValid = checkPhone();
                });

                emailDeli.addEventListener("keyup", () => {
                    emailDeliValid = checkEmail();
                });

                addressDeli.addEventListener("keyup", () => {
                    addressDeliValid = checkAddress();
                });

                provinceDeli.addEventListener("change", () => {
                    provinceDeliValid = checkProvince();
                });

                districtDeli.addEventListener("change", () => {
                    districtDeliValid = checkDistrict();
                });

                wardDeli.addEventListener("change", () => {
                    wardDeliValid = checkWard();
                });

                serviceDeli.addEventListener("change", () => {
                    serviceDeliValid = checkService();
                });

                if (fullNameValid && phoneDeliValid && emailDeliValid && addressDeliValid && provinceDeliValid &&
                    districtDeliValid && wardDeliValid && serviceDeliValid) {
                    formDelivery.submit();
                }
            });
        }

        function checkName() {
            const fullnameValue = fullName.value.trim();
            if (fullnameValue === '') {
                fullName.classList.add("input-error");
                setErrorFor(fullName, 'Họ tên không được để trống');
                fullName.focus();
                return false;
            } else if (isValidName(fullnameValue)) {
                fullName.classList.add("input-error");
                setErrorFor(fullName, 'Trường này phải là chữ');
                fullName.focus();
                return false;
            } else if (fullnameValue.length < 3) {
                fullName.classList.add("input-error");
                setErrorFor(fullName, 'Họ tên quá ngắn');
                fullName.focus();
                return false;
            } else {
                setSuccessFor(fullName);
                return true;
            }
        }

        function removeAscent(str) {
            if (str === null || str === undefined) return str;
            str = str.toLowerCase();
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
            str = str.replace(/đ/g, "d");
            return str;
        }

        function isValidName(string) {
            var re = /^[a-zA-Z!@#\$%\^\&*\)\(+=._-]{3,}$/g // regex here
            return re.test(removeAscent(string))
        }

        function checkPhone() {
            const phoneDeliValue = phoneDeli.value.trim();

            if (phoneDeliValue === '') {
                phoneDeli.classList.add("input-error");
                setErrorFor(phoneDeli, 'Vui lòng nhập trường này');
                phoneDeli.focus();
                return false;
            } else if (!Number(phoneDeliValue)) {
                phoneDeli.classList.add("input-error");
                setErrorFor(phoneDeli, 'Trường này phải là số');
                phoneDeli.focus();
                return false;
            } else if (phoneDeliValue.length < 10 || phoneDeliValue.length > 10) {
                phoneDeli.classList.add("input-error");
                setErrorFor(phoneDeli, 'Phải đủ 10 chữ số');
                phoneDeli.focus();
                return false;
            } else if (!phoneRegex(phoneDeliValue)) {
                phoneDeli.classList.add("input-error");
                setErrorFor(phoneDeli, 'Số điện thoại không hợp lệ');
                phoneDeli.focus();
                return false;
            } else {
                setSuccessFor(phoneDeli);
                return true;
            }
        }

        function phoneRegex(phoneDeli) {
            return /(84|0[3|5|7|8|9])+([0-9]{8})/.test(phoneDeli)
        }

        function checkEmail() {
            const emailDeliValue = emailDeli.value.trim();
            if (emailDeliValue === '') {
                emailDeli.classList.add("input-error");
                setErrorFor(emailDeli, 'Vui lòng nhập trường này');
                emailDeli.focus();
                return false;
            } else if (emailDeliValue.length < 5) {
                emailDeli.classList.add("input-error");
                setErrorFor(emailDeli, 'Email quá ngắn');
                emailDeli.focus();
                return false;
            } else if (!(checkEmailRegex(emailDeliValue))) {
                emailDeli.classList.add("input-error");
                setErrorFor(emailDeli, 'Email sai định dạng');
                emailDeli.focus();
                return false;
            } else {
                setSuccessFor(emailDeli);
                return true;
            }
        }

        function checkEmailRegex(emailDeli) {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                .test(emailDeli);
        }

        function checkAddress() {
            const addressDeliValue = addressDeli.value.trim();
            if (addressDeliValue === '') {
                addressDeli.classList.add("input-error");
                setErrorFor(addressDeli, 'Vui lòng nhập trường này');
                addressDeli.focus();
                return false;
            } else if (addressDeliValue.length < 15) {
                addressDeli.classList.add("input-error");
                setErrorFor(addressDeli, 'Địa chỉ quá ngắn');
                addressDeli.focus();
                return false;
            } else {
                setSuccessFor(addressDeli);
                return true;
            }
        }

        function checkProvince() {
            if (provinceDeli.selectedIndex == 0) {
                provinceDeli.classList.add("input-error");
                setErrorFor(provinceDeli, 'Vui lòng chọn trường này');
                provinceDeli.focus();
                return false;
            } else {
                setSuccessFor(provinceDeli);
                return true;
            }
        }

        function checkDistrict() {
            if (districtDeli.selectedIndex == 0) {
                districtDeli.classList.add("input-error");
                setErrorFor(districtDeli, 'Vui lòng chọn trường này');
                districtDeli.focus();
                return false;
            } else {
                setSuccessFor(districtDeli);
                return true;
            }
        }

        function checkWard() {
            if (wardDeli.selectedIndex == 0) {
                wardDeli.classList.add("input-error");
                setErrorFor(wardDeli, 'Vui lòng chọn trường này');
                wardDeli.focus();
                return false;
            } else {
                setSuccessFor(wardDeli);
                return true;
            }
        }

        function checkService() {
            if (serviceDeli.selectedIndex == 0) {
                serviceDeli.classList.add("input-error");
                setErrorFor(serviceDeli, 'Vui lòng chọn trường này');
                serviceDeli.focus();
                return false;
            } else {
                setSuccessFor(serviceDeli);
                return true;
            }
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
            const formChildSelect = formControl.querySelector(".form-select");
            if (formChild) {
                formChild.className = "form-control input-success";
            }
            if (formChildSelect) {
                formChildSelect.className = "form-select input-success";
            }
        }
    </script>
@endpush