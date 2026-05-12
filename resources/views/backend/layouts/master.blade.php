<!DOCTYPE html>

<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title') | Sneaker Square</title>
    <meta name="description" content="" />
	<!-- Meta Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('backend/assets/img/favicon/logo-fav.ico') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css"
        integrity="sha512-rqQltXRuHxtPWhktpAZxLHUVJ3Eombn3hvk9PHjV/N5DMUYnzKPC1i3ub0mEXgFzsaZNeJcoE0YHq0j/GFsdGg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/css/core.css') }}"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('backend/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{asset('frontend/css/animate.css')}}">
	<link rel="stylesheet" href="{{asset('frontend/css/noty.css')}}">
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <!-- Helpers -->
    <script src="{{ asset('backend/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <script src="{{ asset('backend/assets/js/config.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('backend/assets/js/revenue.js')}}"></script>
    <script src="{{ asset('backend/assets/js/getlink.js')}}"></script>
    <script src="{{ asset('backend/ajax/cate-slide-status.js')}}"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            @include('backend.layouts.partials.aside')

            <!-- Layout container -->
            <div class="layout-page">

                @include('backend.layouts.partials.navbar')

                <!-- Content wrapper -->
                <div class="content-wrapper overflow-hidden">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <!-- / Content -->

                    @include('backend.layouts.partials.footer')

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    {{-- <script src="{{ asset('frontend/js/main.js')}}"></script> --}}
    <script src="{{ asset('backend/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/js/jquery.js') }}"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('backend/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    @stack('script-backend')

    <!-- Main JS -->
    <script src="{{ asset('backend/assets/js/main.js') }}"></script>
    <script src="{{ asset('backend/assets/js/convert-slug.js') }}"></script>
    <script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ asset('backend/assets/js/dashboards-analytics.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js" integrity="sha512-lOrm9FgT1LKOJRUXF3tp6QaMorJftUjowOWiDcG5GFZ/q7ukof19V0HKx/GWzXCdt9zYju3/KhBNdCLzK8b90Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Ckeditor JS -->
    <script src="/backend/ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="/js/ckfinder/ckfinder.js"></script>
    {{-- Morris JS --}}
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script src="{{ asset('backend/assets/vendor/js/datepicker.js') }}"></script>
    <script>
        CKEDITOR.replace('content', {
            filebrowserBrowseUrl: "{{ route('ckfinder_browser') }}",
            language: 'vi',
            extraPlugins: 'lineheight',
            line_height: '0.5;0.75;1;1.5;2;2.25;2.75;3;3.75;4;5;6;8;9;10;11;12;15;',
        });

        //Alert Notifications
        @if (session('message'))
            swal({
                title: '{{session('message')}}',
                text: 'Click để hoàn tất!',
                icon: '{{session('iconMessage')}}',
            });
        @endif
       
    </script>
    
    @include('ckfinder::setup')


</body>
</html>