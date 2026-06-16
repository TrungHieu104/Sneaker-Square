@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
<div class="bg-white container-fluid p-md-5 p-1 page-user-info"
    id="user-tab-pane" role="tabpanel" aria-labelledby="user_info" tabindex="0">
    <h3 class="mb-5 ms-md-0 ms-4 text-center">Thông tin tài khoản</h3>
    <form action="{{ route('thong-tin-tai-khoan.update', [Auth::guard('web')->user()->user_id]) }}"
        method="post" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row d-flex justify-content-center">
            <div class="col-md-4 col-8 p-3">
                <div class="img-user">
                    <img id="previewImage" onerror="this.src='/uploads/img_error3.png'" src="{{ $user->user_img }}"
                        alt="">
                    <a class="edit-button">
                        <i class='bx bxs-edit-alt'></i>
                    </a>
                    <input type="file" name="img__new" id="fileInput" class="files"
                        style="display: none;">
                </div>
                @if ($errors->has('img__new'))
                    @foreach ($errors->get('img__new') as $error)
                        <small class="text-danger fst-italic">
                            {{ $error }}
                        </small><br>
                    @endforeach
                @endif
            </div>
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label" for="fname">Họ và tên</label>
                    <input value="{{ $user->name }}" name="name" id="fname" class="form-control"
                        type="text">
                    @if ($errors->has('name'))
                        @foreach ($errors->get('name') as $error)
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small>
                        @endforeach
                    @endif
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" placeholder="{{ $user->email }}" type="email" disabled>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="uphone">Số điện thoại</label>
                    <input class="form-control" name="user_phone" id="uphone"
                        placeholder="{{ ($user->user_phone) ? $user->user_phone : 'Chưa cập nhập số điện thoại' }}"
                        value="{{ ($user->user_phone) ? $user->user_phone : old('user_phone') }}" type="text">
                    @if ($errors->has('user_phone'))
                        @foreach ($errors->get('user_phone') as $error)
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small>
                        @endforeach
                    @endif
                </div>
                <input class="form-control" type="hidden" name="password" value="{{ $user->password }}">
                <div class="col-lg-6">
                    <label class="form-label" for="uaddress">Địa chỉ giao hàng mặc định</label>
                    <input class="form-control" name="" id="uaddress"
                        value="{{($address) ? $address->info_address.', '.$address->info_ward.', '.$address->info_district.', '.$address->info_province  : 'Chưa có địa chỉ nhận hàng'}}" disabled type="text">
                    @if ($errors->has('info_address'))
                        @foreach ($errors->get('info_address') as $error)
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small>
                        @endforeach
                    @endif
                </div>
                <div class="col-lg-6 mt-2">
                    <div class="col-12 mb-3">
                        <label class="form-label" for="">Chỉnh sửa Gmail</label>
                        <a class="btn btn-secondary text-white w-100 btn-order" data-bs-toggle="modal" data-bs-target="#changeEmail">
                            Thay đổi Gmail
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="border p-2 rounded">
                            <svg width="22px" height="22px" viewBox="-3 0 262 262"
                                xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid"
                                fill="#000000">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                    stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path
                                        d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"
                                        fill="#4285F4"></path>
                                    <path
                                        d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"
                                        fill="#34A853"></path>
                                    <path
                                        d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"
                                        fill="#FBBC05"></path>
                                    <path
                                        d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"
                                        fill="#EB4335"></path>
                                </g>
                            </svg>
                        </div>
                        <span class="ms-3">Tài khoản Google: 
                            <span class="text-primary">
                                @if ($user->google_id)
                                    Đã liên kết
                                @else
                                    Chưa liên kết
                                @endif
                            </span>
                        </span>
                    </div>
                    
                </div>
                <div class="col-lg-6 mt-2">
                    <div class="row">
                        @if($user->email_verified_at == null)         
                            @csrf
                            <div class="col-12 mb-3">
                                <label class="form-label" for="">Xác minh email</label>
                                <a class="btn btn-danger text-white w-100 btn-order" data-bs-toggle="modal" data-bs-target="#verifiedEmail">Xác minh</a>
                            </div>
                        @endif
                        @if ($errors->has('info_email'))
                            @foreach ($errors->get('info_email') as $error)
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small>
                            @endforeach
                        @endif
                        <div class="col-12 mb-3">
                            <label class="form-label" for="">Cập nhập thông tin</label>
                            <button class="btn btn-success text-white w-100 btn-order">
                                Cập nhật
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="modal fade" id="changeEmail" tabindex="-1"
        aria-labelledby="changeEmailLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="changeEmailLabel">Thay đổi Gmail</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="post" action="{{ route('user.change_email',$user->user_id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" name="email" id="email" value="{{ $user->email }}" type="email">
                            @if ($errors->has('email'))
                                @foreach ($errors->get('email') as $error)
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small>
                                @endforeach
                            @endif
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
    <div class="modal fade" id="verifiedEmail" tabindex="-1"
        aria-labelledby="verifiedEmailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="verifiedEmailLabel">Xác nhận
                        email</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="{{ route('verification.send') }}">
                        @csrf
                        <div>
                            <div class="d-flex justify-content-center mt-4">
                                <svg height="80px" width="80px" version="1.1"
                                    id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                    viewBox="0 0 512.00 512.00" xml:space="preserve"
                                    fill="#000000" stroke="#000000"
                                    stroke-width="0.00512">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <path style="fill:#FEAF50;"
                                            d="M256,512C-4.9,412.338,3.285,140.287,16.2,38.83C19.027,16.619,37.967,0,60.357,0H256h195.643 c22.39,0,41.33,16.619,44.157,38.83C508.716,140.287,516.901,412.338,256,512z">
                                        </path>
                                        <path style="fill:#FE9443;"
                                            d="M256,512V0h195.643c22.39,0,41.33,16.619,44.157,38.83C508.716,140.287,516.901,412.338,256,512z">
                                        </path>
                                        <path style="fill:#FEF184;"
                                            d="M80.306,55.652h351.388c5.758,0,10.555,4.387,11.086,10.12 c9.436,101.955,1.243,301.341-186.78,385.992C67.977,367.113,59.784,167.727,69.22,65.772 C69.751,60.039,74.549,55.652,80.306,55.652z">
                                        </path>
                                        <path style="fill:#FED76E;"
                                            d="M442.78,65.772c9.436,101.955,1.243,301.341-186.78,385.992V55.652h175.694 C437.452,55.652,442.249,60.039,442.78,65.772z">
                                        </path>
                                        <path style="fill:#FE9443;"
                                            d="M373.982,158.053l-116.87,155.826c-4.204,5.605-10.802,8.904-17.809,8.904 s-13.605-3.299-17.809-8.904l-50.087-66.783c-7.376-9.835-5.383-23.789,4.452-31.165c9.834-7.377,23.789-5.384,31.165,4.452 l32.278,43.038l99.061-132.082c7.375-9.835,21.328-11.828,31.165-4.452C379.364,134.264,381.358,148.218,373.982,158.053z">
                                        </path>
                                    </g>
                                </svg>
                            </div>
                            <div>
                                <p class="text-center m-auto mt-2 w-75">
                                    Để tăng cường bảo mật cho tài khoản của bạn, Chúng tôi sẽ gửi mail cho đến {{$user->email}}.
                                </p>
                                <div class="d-flex justify-content-center m-auto">
                                    <button
                                        class="d-flex gap-3 btn btn-social mt-5 text-white"><i
                                            class='bx bx-envelope'></i> Xác minh bằng
                                        liên kết Email</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection