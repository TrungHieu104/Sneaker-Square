<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="/backend/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Đăng nhập | Sneaker Square</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{asset('frontend/img/logo-fav.ico')}}" type="image/x-icon">

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

    <!-- Page -->
    <link rel="stylesheet" href="/backend/assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="/backend/assets/vendor/js/helpers.js"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="/backend/assets/js/config.js"></script>

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
                            <a href="{{route('home.page')}}" class="app-brand-link gap-2">
                                <img src="/frontend/img/logo_sn.png" alt="" class="logo-brand">
                            </a>
                        </div>

                        <form id="formAuthentication" class="mb-3" action="{{route('user.login_post')}}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <div class="position-relative">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="text" class="form-control" id="email" name="email"
                                        placeholder="Nhập email của bạn" @if(Cookie::has('email')) value="{{Cookie::get('email')}}" @endif />
                                    @error('email')
                                        <small class="text-danger fst-italic">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                    <small class="error-text position-absolute text-danger fst-italic">a</small>
                                </div>
                            </div>
                            <div class="mb-4 form-password-toggle position-relative">
                                <div class="position-relative">
                                    <div class="d-flex justify-content-between">
                                        <label class="form-label" for="password">Mật khẩu</label>
                                        <a href="{{route('user.forgot')}}">
                                            <span>Quên mật khẩu?</span>
                                        </a>
                                    </div>
                                    <input type="password" id="password" class="form-control" name="password" @if(Cookie::has('password')) value="{{Cookie::get('password')}}" @endif
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
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
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                                    <label class="form-check-label" for="remember"> Ghi nhớ </label>
                                </div>
                            </div>
                            @if(Session::exists('Notification'))
                                <div class="mb-3">
                                    <i class="text-danger">{{ Session::get('Notification') }} </i>
                                </div>
                            @endif
                            @if (session('login_attempts', 0) > 3)
                                <div class="g-recaptcha d-flex justify-content-center" data-sitekey="{{ env('CAPTCHA_KEY') }}"></div>
                                <br>
                            @endif
                            @if($errors->has('g-recaptcha-response'))
                            <span class="invalid-feedback" style="display:block">
                                <strong>{{$errors->first('g-recaptcha-response')}}</strong>
                            </span>
                            @endif


                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100"  type="submit">Đăng nhập</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>Khách hàng mới?</span>
                            <a href="{{route('user.register')}}">
                                <span>Tạo tài khoản</span>
                            </a>
                        </p>

                        <!-- Login with Google -->
                        <div class="mb-3">
                            <form action="{{route('login_google')}}">
                                <button class="btn btn-primary d-flex gap-3 mb-3 justify-content-center py-2 w-100 btn-login_user" type="submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 256 262">
                                        <path fill="#4285F4" d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"></path>
                                        <path fill="#34A853" d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"></path>
                                        <path fill="#FBBC05" d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"></path>
                                        <path fill="#EB4335" d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"></path>
                                    </svg>
                                    Đăng nhập bằng Google
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /Register -->
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
    <!-- Main JS -->
    <script src="/backend/assets/js/main.js"></script>
    <script src="{{ asset('frontend/js/validate_login.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
       function onSubmit(token) {
         document.getElementById("login").submit();
       }
     </script>
    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>
    
    <script>
        @if (session('message'))
            swal({
                title: '{{session('message')}}',
                text: 'Click để hoàn tất!',
                icon: '{{session('iconMessage')}}',
            });
        @endif
    </script>
</body>

</html>
