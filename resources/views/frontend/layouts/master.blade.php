<!DOCTYPE html>
<html lang="zxx" class="no-js">

<head>
    <!-- Mobile Specific Meta -->
    <meta name="viewport" content="width=device-width minimum-scale=1.0 maximum-scale=1.0 user-scalable=no" />
    <!-- Meta UTF8 -->
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!-- Author Meta -->
    <meta name="author" content="Sneaker Square">
    <meta name="copyright" content="Sneaker Square" />
    <meta name="revisit-after" content="1 days"/>
    <!-- Meta Keyword -->
    <meta name="description" content="@yield('description','Bước chân đẹp - Mua giày thời trang chất lượng ngay hôm nay!')">
    <meta name="keywords" content="@yield('keywords','Sneaker Square')">
    <meta name="robots" content="index, follow">
    <meta name="google-site-verification" content="GkaUwGJOJnq5CQXSwV4BLK5IwiJIYCRHggcK1QbaB08" />
    <link rel="canonical" href="{{request()->fullUrl()}}">
    <!-- meta title Facebook Twitter  -->
    <meta property="og:title" content="@yield('meta_title','The Sneaker Square')" />
    <meta property="og:site_name" content="{{env('APP_URL')}}">
    <meta property="og:url" content="{{ request()->fullUrl() }}">
    <meta property="og:type" content="webiste">
    <meta property="og:description" content="@yield('description','Bước chân đẹp - Mua giày thời trang chất lượng ngay hôm nay!')" />
    <meta property="og:image" content="@yield('img', asset('uploads/images/banner_meta.png'))" />
    <!-- Meta Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- meta character set -->
    <meta charset="UTF-8">
    <!-- Favicon-->
    <link rel="shortcut icon" href="{{ asset('frontend/img/logo-fav.ico') }}" type="image/x-icon">
    <!-- Site Title -->
    <title> @yield('title') | Sneaker Square</title>
    <link rel="canonical" href="/web/tweet-button">
    <!--CSS============================================= -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css"
        integrity="sha512-rqQltXRuHxtPWhktpAZxLHUVJ3Eombn3hvk9PHjV/N5DMUYnzKPC1i3ub0mEXgFzsaZNeJcoE0YHq0j/GFsdGg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('frontend/css/linearicons.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/themify-icons.css') }}">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/nouislider.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/ion.rangeSlider.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/css/ion.rangeSlider.skinFlat.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery.simplyscroll.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="{{ asset('frontend/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/mmenu/demo.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/aos/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/noty.css') }}">
    @stack('css-access')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('frontend/js/jquery.simplyscroll.js') }}"></script>
</head>

<body>
    <div id="spinner"
        class="show position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="cssload-container">
            <ul class="cssload-flex-container">
                <li>
                    <span class="cssload-loading cssload-one"></span>
                    <span class="cssload-loading cssload-two"></span>
                    <span class="cssload-loading-center"></span>
                </li>
            </ul>
        </div>
    </div>

    @include('frontend.layouts.partials.topbar')

    @include('frontend.layouts.partials.header')

    @section('banner')
        @include('frontend.widgets.banner_home')
    @show

    @yield ('content')

    @include('frontend.layouts.partials.footer')

    <!-- JavaScript Main -->
    <script src="{{ asset('frontend/js/main.js') }}"></script>
    <script src="{{ asset('frontend/js/app.js') }}"></script>
    <script src="{{ asset('frontend/js/easing.js') }}"></script>

    <!-- JavaScript Mmenu -->
    <script src="{{ asset('frontend/js/product_slide.js') }}"></script>

    <!-- JavaScript Slick -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <!-- JavaScript Owl Carousel -->
    <script src="{{ asset('frontend/js/owl.carousel.min.js') }}"></script>

    <!-- JavaScript Other Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"
        integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous">
    </script>
    <script type="text/javascript" src="https://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script src="{{asset('frontend/aos/aos.js')}}"></script>
	<script src="{{asset('frontend/js/jquery.ajaxchimp.min.js')}}"></script>
	<script src="{{asset('frontend/js/vendor/bootstrap.min.js')}}"></script>
	<script src="{{asset('frontend/js/jquery.sticky.js')}}"></script>
	<script src="{{asset('frontend/js/nouislider.min.js')}}"></script>
	<script src="{{asset('frontend/ajax/wishlist.js')}}"></script>
	<script src="{{asset('frontend/ajax/delivery.js')}}"></script>
	<script src="{{asset('frontend/ajax/filter_product.js')}}"></script>
	<script src="{{asset('frontend/ajax/remove_verified_email.js')}}"></script>
	<script src="{{asset('frontend/js/jquery.magnific-popup.min.js')}}"></script>
	<script src="{{asset('frontend/js/validate_product_checkout.js')}}"></script>
	<script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js" integrity="sha512-lOrm9FgT1LKOJRUXF3tp6QaMorJftUjowOWiDcG5GFZ/q7ukof19V0HKx/GWzXCdt9zYju3/KhBNdCLzK8b90Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	
	<!-- JavaScript Start -->
	@stack('script-access')

    <!-- JavaScript Start Mmenu && AOS -->
    <script>
        AOS.init();

        //Alert Notifications
         @if (session('message'))
			@if (session('text'))
				swal({
					title: '{{ session('message') }}',
					text: '{{ session('text') }}' ,
					icon: '{{ session('iconMessage') }}',
				});
			@else
				swal({
					title: '{{ session('message') }}',
					text: 'Click để hoàn tất!' ,
					icon: '{{ session('iconMessage') }}',
				});
			@endif
        @endif
    </script>


    <!-- Back to Top -->
    <div class="btn-lg-square back-to-top text-white">
        <button class="btn-back">
            <svg height="1.2em" class="arrow" viewBox="0 0 512 512"><path d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z"></path></svg>
            <p class="text">Back to Top</p>
        </button>
    </div>
    

    <!-- Start Button call -->
    <div class="call-now-button">
        <div>
            @foreach ($contact as $data)
                <p class="call-text">
                    <a href="tel:{{$data->contact_phone}}"
                        title="{{ '' . substr($data->contact_phone, 0, 4) . ' ' . substr($data->contact_phone, 4, 3) . ' ' . substr($data->contact_phone, 7) }}">
                        {{ '' . substr($data->contact_phone, 0, 4) . ' ' . substr($data->contact_phone, 4, 3) . ' ' . substr($data->contact_phone, 7) }}
                    </a>
                </p>
                <a href="tel:{{$data->contact_phone}}"
                    title="{{ '' . substr($data->contact_phone, 0, 4) . ' ' . substr($data->contact_phone, 4, 3) . ' ' . substr($data->contact_phone, 7) }}">
                    <div class="quick-alo-ph-circle"></div>
                    <div class="quick-alo-ph-circle-fill"></div>
                    <div class="quick-alo-ph-btn-icon quick-alo-phone-img-circle">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    <!-- End Button call -->

</body>

</html>
