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
                        <h4 class="mb-2">Quên mật khẩu? 🔒</h4>
                        <p class="mb-4">Nhập email của bạn và chúng tôi sẽ gửi cho bạn hướng dẫn để đặt lại mật khẩu
                            của bạn</p>
                        <form id="formAuthentication" class="mb-3" action="{{ route('user.forgot_post') }}"
                            method="POST">
                            @csrf
                            <div class="mb-4 position-relative">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    placeholder="Địa chỉ email của bạn" autofocus />
                                <small class="error-text position-absolute text-danger fst-italic">a</small>
                            </div>
                            <button class="btn btn-primary d-grid w-100">Gửi yêu cầu</button>
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
    <script src="{{ asset('backend/js/sweetalert.min.js') }}"></script>

    <script>
        @if (session('message'))
            swal({
                title: '{{ session('message') }}',
                text: 'Click để hoàn tất!',
                icon: '{{ session('iconMessage') }}',
            });
        @endif
        // 
        const form = document.getElementById("formAuthentication");
        const email = document.getElementById("email");

        let isEmailValid = false;

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            let isEmailValid = checkEmail();
            if (email) {
                email.addEventListener("keyup", () => {
                    isEmailValid = checkEmail();
                });
            }
            if (isEmailValid) {
                form.submit();
            }
        });

        function checkEmail() {
            const emailValue = email.value.trim();
            if (emailValue === '') {
                email.classList.add("input-error");
                setErrorFor(email, 'Email không được để trống');
                email.focus();
                return false;
            } else if (emailValue.length < 5) {
                email.classList.add("input-error");
                setErrorFor(email, 'Email quá ngắn');
                email.focus();
                return false;
            } else if (!checkEmailRegex(emailValue)) {
                email.classList.add("input-error");
                setErrorFor(email, 'Email sai định dạng');
                email.focus();
                return false;
            } else {
                setSuccessFor(email);
                return true;
            }

        }

        function checkEmailRegex(email) {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                .test(email);
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
            formChild.className = "form-control input-success";
        }
        
    </script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>
