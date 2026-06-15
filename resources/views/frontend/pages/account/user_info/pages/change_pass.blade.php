@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
<div>
    <h3 class="mb-5">Thay đổi mật khẩu</h3>
    <p>Để bảo mật tài khoản, vui lòng không chia sẽ mật khẩu cho người khác</p>
    <div class="bg-white">
            <form
                action="{{ route('user.update_pass_post', [Auth::guard('web')->user()->user_id]) }}"
                method="post" id="changePass">
                @csrf @method('PUT')
                <div class="row container-xxl p-5">
                    <div class="col-md-6 col-12 mb-4">
                        <label class="form-label" for="">Mật khẩu mới</label>
                        <div class="position-relative">
                            <input type="password" placeholder="Nhập mật khẩu mới" name="password" class="form-control" id="password-input">
                            <small class="error-text position-absolute text-danger fst-italic">a</small>
                            <span class="eye-icon" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        @error('password')
                        <small class="text-danger fst-italic">
                            {{ $message }}
                        </small>
                        @enderror
                    </div>
                    <div class="col-md-6 col-12 mb-4">
                        <label class="form-label" for="">Mật khẩu mới</label>
                        <div class="position-relative">
                            <input type="password" placeholder="Nhập mật khẩu mới" name="same_password" class="form-control" id="same_password-input">
                            <small class="error-text position-absolute text-danger fst-italic">a</small>
                            <span class="eye-icons" onclick="togglesame_PasswordVisibility()">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        @error('same_password')
                        <small class="text-danger fst-italic">
                            {{ $message }}
                        </small>
                        @enderror
                    </div>
                    <div class="prd-bottom d-flex justify-content-start">
                        <button type="submit" class="btn btn-social text-white mb-3 px-3 ">
                            Xác nhận
                        </button>
                    </div>
                </div>
            </form>
    </div>
</div>
@endsection

@push('script-access')
    <script>
        const formChange = document.getElementById("changePass");
        const passwordChange = document.getElementById("password-input");
        const samePasswordChange = document.getElementById("same_password-input");

        let isPasswordValid = false;
        let isSamePasswordValid = false;

        formChange.addEventListener("submit", (e) => {
            e.preventDefault();

            let isPasswordValid = checkPassword();
            let isSamePasswordValid = checkSamePassword();
        
            if(passwordChange) {
                passwordChange.addEventListener("keyup", () => {
                    isPasswordValid = checkPassword();
                });
            }
        
            if(samePasswordChange) {
                samePasswordChange.addEventListener("keyup", () => {
                    isSamePasswordValid = checkSamePassword();
                });
            }
        
            if (isPasswordValid && isSamePasswordValid) {
                formChange.submit();
            }
        });

        function checkPassword() {
            const passwordChangeValue = passwordChange.value.trim();
            if (passwordChangeValue === '') {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu không được để trống');
                passwordChange.focus();
                return false;
            } else if (passwordChangeValue.length < 8) {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu phải đủ 8 ký tự');
                passwordChange.focus();
                return false;
            } else if (!(/[0-9]/.test(passwordChangeValue))) {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu phải có số');
                passwordChange.focus();
                return false;
            } else if (!(/[A-Z]/.test(passwordChangeValue))) {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu phải có chữ hoa');
                passwordChange.focus();
                return false;
            } else if (!(/[a-z]/.test(passwordChangeValue))) {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu phải có chữ thường');
                passwordChange.focus();
                return false;
            } else if (!(/[^0-9a-zA-Z]/.test(passwordChangeValue))) {
                passwordChange.classList.add("input-error");
                setErrorFor(passwordChange, 'Mật khẩu phải có ký tự đặc biệt');
                passwordChange.focus();
                return false;
            } else {
                setSuccessFor(passwordChange);
                return true;
            }
        }

        function checkSamePassword() {
            if(samePasswordChange) {
                const samePassChangeValue = samePasswordChange.value.trim();
                if(samePassChangeValue === '') {
                    samePasswordChange.classList.add("input-error");
                    setErrorFor(samePasswordChange, 'Mật khẩu không được để trống');
                    samePasswordChange.focus();
                    return false;
                } else if(passwordChange.value.trim() !== samePassChangeValue) {
                    samePasswordChange.classList.add("input-error");
                    setErrorFor(samePasswordChange, 'Mật khẩu không khớp');
                    samePasswordChange.focus();
                    return false;
                }else {
                    setSuccessFor(samePasswordChange);
                    return true;
                }      
            }
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
@endpush