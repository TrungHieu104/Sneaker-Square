<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="/backend/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Lấy lại mật khẩu | Sneaker Square</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('frontend/img/logo-fav.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="/backend/assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/backend/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="/backend/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="/backend/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="/backend/assets/vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="/backend/assets/vendor/js/helpers.js"></script>
    <script src="/backend/assets/js/config.js"></script>

</head>

<body>
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic">
            <div class="authentication-inner py-4" id="aut-forgot">
                <!-- Forgot Password -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="{{ route('home.page') }}" class="app-brand-link gap-2">
                                <img src="/frontend/img/logo_sn.png" alt="" class="logo-brand">
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h3 class="mb-4 text-center">Đặt lại mật khẩu mới</h3>
                        <form id="formAuthentication" class="mb-3" method="post"
                            action="{{ route('user.reset_pass_post', $user->remember_token) }}">
                            @csrf
                            <div class="mb-4 form-password-toggle position-relative">
                                <div class="position-relative">
                                    <label class="form-label" for="password">Mật khẩu</label>
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" autofocus/>
                                    <small class="error-text position-absolute text-danger fst-italic">a</small>
                                    @error('password')
                                        <small class="text-danger fst-italic">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>
                                <i class="bx bx-hide position-absolute cursor-pointer"
                                    style="right: 15px; top: 50%; transform: translateY(calc(-50% + 15px));"></i>
                            </div>
                            <div class="mb-4 form-password-toggle position-relative">
                                <div class="position-relative">
                                    <label class="form-label" for="same_password">Nhập lại mật khẩu</label>
                                    <input type="password" id="same_password" class="form-control" name="same_password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="same-password" />
                                    <small class="error-text position-absolute text-danger fst-italic">a</small>
                                    @error('same_password')
                                        <small class="text-danger fst-italic">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>
                                <i class="bx bx-hide position-absolute cursor-pointer"
                                    style="right: 15px; top: 50%; transform: translateY(calc(-50% + 15px));"></i>
                            </div>
                            <button class="btn btn-primary d-grid w-100">Đổi mật khẩu</button>
                        </form>
                        <div class="text-center">
                            <a href="{{ route('user.login') }}"
                                class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                                Quay lại đăng nhập
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password -->
            </div>
        </div>
    </div>
    <!-- / Content -->

    <div class="buy-now">
        <a href="tel:039469525" class="btn btn-danger btn-buy-now">Hỗ trợ</a>
    </div>

    <!-- build:js assets/vendor/js/core.js -->
    <script src="/backend/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/backend/assets/vendor/libs/popper/popper.js"></script>
    <script src="/backend/assets/vendor/js/bootstrap.js"></script>
    <script src="/backend/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="/backend/assets/vendor/js/menu.js"></script>
    <script src="/frontend/js/validate_reset.js"></script>

    <!-- Main JS -->
    <script src="/backend/assets/js/main.js"></script>
    <script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>
    <script>
        @if (session('message'))
            swal({
                title: '{{ session('message') }}',
                text: 'Click để hoàn tất!',
                icon: '{{ session('iconMessage') }}',
            });
        @endif
    </script>
    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>
