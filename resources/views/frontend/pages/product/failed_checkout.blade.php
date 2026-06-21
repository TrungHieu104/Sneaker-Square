@extends('frontend.index')

@section('title')
    Đặt hàng thất bại
@endsection

@section('banner')
    
@endsection

@section('content')
    <style>
        .errormark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #F24C3D;;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .errormark {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            margin: 3% auto;
            box-shadow: inset 0px 0px 0px #F24C3D;;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .errormark__check {
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
                box-shadow: inset 0px 0px 0px 30px #F24C3D;;
            }
        }
    </style>

    <!-- Start Content Area -->
    <section class="container-fluid p-0 overflow-hidden">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-12 text-center">
                    <svg class="errormark" xmlns="http://www.w3.org/2000/svg" height="0.875em" viewBox="0 0 384 512">
                        <circle class="errormark__circle" cx="26" cy="26" r="25" fill="none" />
                        <style>svg{fill:#ffffff}</style>
                        <path class="errormark__check" d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                    </svg>
                                     
                    <div class="section-title">
                        <h3>Đặt hàng thất bại</h3>
                        <span>Đặt hàng ngay để đơn hàng giao tới tay bạn trong thời gian sớm nhất.</span>
                    </div>
                </div>
                <div class="col-lg-12 text-center">
                    
                    <a href="{{route('product.checkout')}}" class="btn discount-button mx-3">Thanh toán lại</a>
                    <a href="{{route('product.cart')}}" class="btn bg-light mx-3">Giỏ hàng</a>
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
