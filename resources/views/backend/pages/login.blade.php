<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="/backend/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Đăng nhập quản trị | Sneaker Square</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('backend/assets/img/favicon/logo-fav.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/css/core.css') }}"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('backend/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/css/pages/page-auth.css') }}" />
    <!-- Helpers -->
    <script src="{{ asset('backend/assets/vendor/js/helpers.js') }}"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('backend/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic">
            <div class="authentication-inner" id="aut-login">
                <!-- Register -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="{{ route('home.page') }}" class="app-brand-link gap-2">
                                <img src="/frontend/img/logo_sn.png" alt="" class="logo-brand">
                            </a>
                        </div>

                        <form id="formAuthentication" class="mb-3" action="{{ route('admin.login_check') }}"
                            method="POST">
                            @csrf
                            <div class="mb-4">
                                <div class="position-relative">
                                    <label for="email" class="form-label">Email hoặc username</label>
                                    <input type="text" class="form-control" id="email" name="email"
                                        placeholder="Email hoặc username" autofocus />
                                    @if ($errors->has('email'))
                                        @foreach ($errors->get('email') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                    <small class="error-text position-absolute text-danger fst-italic">a</small>
                                </div>
                            </div>
                            <div class="mb-4 form-password-toggle position-relative">
                                <div class="position-relative">
                                    <div class="d-flex justify-content-between">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <a href="{{route('user.forgot')}}">
                                            Quên mật khẩu?
                                        </a>
                                    </div>
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
                                    <small class="error-text position-absolute text-danger fst-italic">a</small>
                                    @if ($errors->has('password'))
                                        @foreach ($errors->get('password') as $error)
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small>
                                        @endforeach
                                    @endif
                                </div>
                                <i class="bx bx-hide position-absolute cursor-pointer"
                                    style="right: 15px; top: 50%; transform: translateY(calc(-50% + 15px));"></i>
                            </div>
                            <div class="mb-3 mt-4">
                                <button class="btn btn-primary d-grid w-100" type="submit">Đăng nhập</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Register -->
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="{{ asset('backend/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('backend/assets/vendor/js/menu.js') }}"></script>
    <!-- endbuild -->

    <!-- Main JS -->
    <script src="{{ asset('backend/assets/js/main.js') }}"></script>
    <script src="{{ asset('backend/js/validate_form.js') }}"></script>
    <script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>

    <!-- Page JS -->
    <script>
        @if (session('message'))
            swal({
                title: '{{ session('message') }}',
                icon: '{{ session('iconMessage') }}',
            });
        @endif
    </script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>
