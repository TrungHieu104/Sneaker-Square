@extends('backend.index')

@section('title')
    Thêm tài khoản
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.index')}}" class="tab-back">Danh sách / </a></span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form id="formAuthenticationReg" action="{{asset('admin/account')}}" method="post">
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Thêm mới tài khoản</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-12 mb-3">
                        <label for="cate-product" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" autocomplete="off" value="{{old('email')}}"/>
                        <small class="error-text position-absolute text-danger fst-italic">a</small>
                        @if ($errors->has("email")) 
                            @foreach ($errors->get("email") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }} <br>
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{old('name')}}" placeholder="Nhập tại đây"/>
                        <small class="error-text position-absolute text-danger fst-italic">a</small>
                        @if ($errors->has("name")) 
                            @foreach ($errors->get("name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }} <br>
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" value="{{old('username')}}" placeholder="Nhập tại đây"/>
                        <small class="error-text position-absolute text-danger fst-italic">a</small>
                        @if ($errors->has("username")) 
                            @foreach ($errors->get("username") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }} <br>
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-lg-6 mb-3 form-password-toggle position-relative">
                        <div class="position-relative">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" id="passwords" class="form-control" name="passwords" value="{{old('passwords')}}"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                aria-describedby="password" />
                            <small class="error-text position-absolute text-danger fst-italic">a</small>
                            @if ($errors->has('passwords'))
                                @foreach ($errors->get('passwords') as $error)
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small>
                                @endforeach
                            @endif
                        </div>
                        <i class="bx bx-hide position-absolute cursor-pointer"
                            style="right: 30px; top: 35px; transform: translateY(calc(-50%)); margin-top:15px"></i>
                    </div>
                    <div class="col-lg-6 mb-3 form-password-toggle position-relative">
                        <div class="position-relative">
                            <label for="password" class="form-label">Nhập lại mật khẩu</label>
                            <input type="password" id="confirm-pass" class="form-control" name="confirm-pass" value="{{old('confirm-pass')}}"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                aria-describedby="new-pass" />
                            <small class="error-text position-absolute text-danger fst-italic">a</small>
                            @if ($errors->has('confirm-pass'))
                                @foreach ($errors->get('confirm-pass') as $error)
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small>
                                @endforeach
                            @endif
                        </div>
                        <i class="bx bx-hide position-absolute cursor-pointer"
                            style="right: 30px; top: 35px; transform: translateY(calc(-50%)); margin-top:15px"></i>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3 pl-3">
                        <label for="title" class="form-label">Trạng thái</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck3" checked />
                                <label class="form-check-label" for="defaultCheck3"> Kích hoạt </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="0" id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Vô hiệu </label>
                            </div>
                        </div>
                    </div>   
                    <div class="col-lg-6 mb-3 pl-3">
                        <label for="title" class="form-label">Quyền</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="per" type="radio" value="1" id="defaultCheck5" checked />
                                <label class="form-check-label" for="defaultCheck3">Quản trị viên</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="per" type="radio" value="0" id="defaultCheck6"/>
                                <label class="form-check-label" for="defaultCheck4">Khách hàng</label>
                            </div>
                        </div>
                    </div>   

                </div>
                
            </div>
        </div>
    </form>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const formes = document.getElementById("formAuthenticationReg");
        console.log(formes)
        const pass = document.getElementById("passwords");
        const email = document.getElementById("email");
        const names = document.getElementById("name");
        const username = document.getElementById("username");
        const confirm_pass = document.getElementById("confirm-pass");

        let isConfirmPassValid = false;
        let isPassValid = false;
        let isEmailValid = false;
        let isNameValid = false;
        let isUsernameValid = false;

        formes.addEventListener("submit", (e) => {
            e.preventDefault();
            let isConfirmPassValid = checkSamePassword();
            let isPassValid = checkPassword();
            let isNameValid = checkName();
            let isEmailValid = checkEmail();
            let isUsernameValid = checkUname();
            pass.addEventListener("keyup", () => {
                isPassValid = checkPassword();
            });
            email.addEventListener("keyup", () => {
                isEmailValid = checkEmail();
            });
            if(username) {
                username.addEventListener("keyup", () => {
                    isUsernameValid = checkUname();
                });
            }
            if(names) {
                names.addEventListener("keyup", () => {
                    isNameValid = checkName();
                });
            }
            if(confirm_pass) {
                confirm_pass.addEventListener("keyup", () => {
                    isConfirmPassValid = checkSamePassword();
                });
            }
            if (isConfirmPassValid && isPassValid && isNameValid && isEmailValid && isUsernameValid) {
                formes.submit();
            }
        });

        function checkPassword() {
            const passwordValue = pass.value.trim();
            if (passwordValue === '') {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu không được để trống');
                pass.focus();
                return false;
            } else if (passwordValue.length < 8) {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu phải đủ 8 ký tự');
                pass.focus();
                return false;
            } else if (!(/[0-9]/.test(passwordValue))) {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu phải có số');
                pass.focus();
                return false;
            } else if (!(/[A-Z]/.test(passwordValue))) {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu phải có chữ hoa');
                pass.focus();
                return false;
            } else if (!(/[a-z]/.test(passwordValue))) {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu phải có chữ thường');
                pass.focus();
                return false;
            } else if (!(/[^0-9a-zA-Z]/.test(passwordValue))) {
                pass.classList.add("input-error");
                setErrorFor(pass, 'Mật khẩu phải có ký tự đặc biệt');
                pass.focus();
                return false;
            } else {
                setSuccessFor(pass);
                return true;
            }
        }

        function checkEmail() {
            const emailValue = email.value.trim();
            if (emailValue === '') {
                email.classList.add("input-error");
                setErrorFor(email, 'Email không được để trống');
                email.focus();
                return false;
            } else if(!checkEmailRegex(emailValue)) {
                email.classList.add("input-error");
                setErrorFor(email, 'Email sai định dạng');
                email.focus();
                return false;
            }
            else {
                setSuccessFor(email);
                return true;
            }
        }

        function checkEmailRegex(email) {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                .test(email);
        }

        function checkName() {
            if(names) {
                const namesValue = names.value.trim();
            
                if(namesValue === '') {
                    names.classList.add("input-error");
                    setErrorFor(names, 'Họ tên không được để trống');
                    names.focus();
                    return false;
                }else if(!/^[a-zA-Z\sàáạảãăắằẳẵặâấầẩẫậèéẹẻẽêếềểễệđìíịỉĩóỏọõôốồổỗộơớờởỡợùúụủũưứừửữựỳỹýỷỹ]+$/u.test(namesValue)){
                    names.classList.add("input-error");
                    setErrorFor(names, 'Họ tên phải là chữ');
                    names.focus();
                    return false;
                }else if(namesValue.length < 3) {
                    names.classList.add("input-error");
                    setErrorFor(names, 'Họ tên phải hơn 3 ký tự');
                    names.focus();
                    return false;
                }else {
                    setSuccessFor(names);
                    return true;
                }
            }
        }

        function checkUname() {
            if(username) {
                const usernameValue = username.value.trim();
            
                if(usernameValue === '') {
                    username.classList.add("input-error");
                    setErrorFor(username, 'Tên đăng nhập không được để trống');
                    username.focus();
                    return false;
                }else if(usernameValue.length < 3) {
                    username.classList.add("input-error");
                    setErrorFor(username, 'Tên đăng nhập phải hơn 3 ký tự');
                    username.focus();
                    return false;
                }else {
                    setSuccessFor(username);
                    return true;
                }
            }
        }

        function checkSamePassword() {
            if(confirm_pass) {
                const samePasswordValue = confirm_pass.value.trim();
                if(samePasswordValue === '') {
                    confirm_pass.classList.add("input-error");
                    setErrorFor(confirm_pass, 'Mật khẩu không được để trống!');
                    confirm_pass.focus();
                    return false;
                } else if(pass.value.trim() !== samePasswordValue) {
                    confirm_pass.classList.add("input-error");
                    setErrorFor(confirm_pass, 'Mật khẩu không khớp!');
                    confirm_pass.focus();
                    return false;
                }else {
                    setSuccessFor(confirm_pass);
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

    })
</script>