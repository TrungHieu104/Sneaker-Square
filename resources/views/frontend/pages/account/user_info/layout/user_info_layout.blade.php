@extends('frontend.index')
<!-- Title -->
@section('title')
    Thông tin tài khoản
@endsection

<!-- Banner -->
@section('banner')
    <!-- Start banner Area -->

    <!-- End banner Area -->
@endsection

<!-- Content -->
@section('content')
    <div class="container-fluid px-5 my-5">
        <div class="row ">
            <div class="col-md-3 col-3  button-userinfo ">
                <ul class="" id="myTab" role="tablist">
                    <li class="button-userinfos d-flex gap-3 {{ ( (url()->current()) == (route('thong-tin-tai-khoan.index')) ) ? 'active' : '' }}">
                        <a class="nav-link p-3 {{ ( (url()->current()) == (route('thong-tin-tai-khoan.index')) ) ? 'active' : '' }}" href="{{route('thong-tin-tai-khoan.index')}}">
                            <i class="bx bx-user pe-3"></i>
                            <span class="menu-info">{{Auth()->guard('web')->user()->name}}
                            @if(Auth()->guard('web')->user()->email_verified_at != null)
                            <svg width="16px" height="16px" viewBox="0 0 32.00 32.00" fill="none"
                                xmlns="http://www.w3.org/2000/svg" stroke="#000000" stroke-width="0.00032">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z"
                                        fill="#4383FF"></path>
                                </g>
                            </svg>
                            </span>
                            @endif
                        </a>
                    </li>
                    <hr>
                    <li class="button-userinfos d-flex gap-3 {{ ( (url()->current()) == (route('user.update_pass')) ) ? 'active' : '' }}">           
                        <a class="nav-link p-3 {{ ( (url()->current()) == (route('user.update_pass')) ) ? 'active' : '' }}" href="{{  (route('user.update_pass')) }}"><i class='bx bxs-key pe-3'></i><span class="menu-info">Mật khẩu</span></a>
                    </li>
                    <hr>
                    <li class="button-userinfos d-flex gap-3 {{ ( (url()->current()) == (route('user.delivery')) ) ? 'active' : '' }}">
                        <a class="nav-link p-3 {{ ( (url()->current()) == (route('user.delivery')) ) ? 'active' : '' }}" href="{{route('user.delivery')}}"> <i class='bx bx-current-location pe-3'></i><span class="menu-info">Địa chỉ giao hàng</span></a>
                    </li>
                    <hr>
                    <li class="button-userinfos d-flex gap-3 {{ ( (url()->current()) == (route('user.order')) ) ? 'active' : '' }}">
                        <a class="nav-link p-3 {{ ( (url()->current()) == (route('user.order')) ) ? 'active' : '' }}" href="{{route('user.order')}}"><i class='bx bx-cart pe-3'></i><span class="menu-info">Đơn hàng</span></a>
                    </li>
                    <hr>
                    {{-- <li class="button-userinfos d-flex gap-3" role="presentation">
                        <i class='bx bx-history'id="profile-tab" data-bs-toggle="tab" data-bs-target="#htr-cart-tab-pane"
                            type="button" role="tab" aria-controls="cart-tab-pane" aria-selected="false"></i>
                        <a class="nav-link p-3" id="profile-tab" data-bs-toggle="tab"
                            data-bs-target="#htr-cart-tab-pane" type="button" role="tab"
                            aria-controls="cart-tab-pane" aria-selected="false">Lịch sử mua hàng</a>
                    </li> --}}
                </ul>
            </div>
            <div class="col-md-9 col-9 p-md-5 p-3  page-user">
                <div class="tab-content" id="myTabContent">
                    @yield('content_user')
                    <!-- ====================== User Info ============================== -->
                    
                    <!-- ====================== User Pass ============================== -->
                    
                    <!-- ====================== User Delivery ============================== -->
                    
                    <!-- ====================== Order ============================== -->
                    
                </div>
            </div>
        </div>
    </div>
@endsection
