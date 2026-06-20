@extends('frontend.index')

@section('title')
    Đặt hàng thành công
@endsection

@section('banner')
    
@endsection

@section('content')
    <style>
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #7ac142;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            margin: 3% auto;
            box-shadow: inset 0px 0px 0px #7ac142;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #7ac142;
            }
        }
    </style>

    <!-- Start Content Area -->
    <section class="container-fluid p-0 overflow-hidden">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-12 text-center">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                    </svg>
                    <div class="section-title">
                        <h3>Đặt hàng thành công</h3>
                        <span>Xin chân thành cảm ơn bạn đã tin tưởng và lựa chọn sản phẩm của Sneacker Square !</span><br>
                        <b>Thông tin đơn hàng & hóa đơn đã được gửi qua <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank">Email</a> mà bạn đã đăng kí với chúng tôi.</b>
                    </div>
                </div>
                <div class="col-lg-12 text-center">
                    
                    <a href="{{route('home.page')}}" class="btn discount-button mx-3">Trang chủ</a>
                    <a href="{{route('user.order')}}" class="btn bg-light mx-3">Đơn hàng</a>
                </div>
            </div>
            {{-- <form action=""> --}}
            <div class="row">

            </div>
            {{-- </form> --}}
        </div>
    </section>
    <!-- Start Content Area -->
@endsection
