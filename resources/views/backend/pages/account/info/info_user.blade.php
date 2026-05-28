@extends('backend.index')

@section('title')
    Chỉnh sửa tài khoản
@endsection

@section('content')
    {{-- <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.user')}}" class="tab-back">Danh sách / </a></span> Chỉnh sửa</h4> --}}
    <!-- Bordered Table -->
    <div class="row mt-3 d-flex mt-3">
        <div class="col-lg-12">
            <form action="{{route('account.updateinfo',['id'=>$info->user_id])}}" method="post" enctype="multipart/form-data">
                @csrf 
                <input type="hidden" name="id" value="{{$info->user_id}}">
                <div class="card mb-4">
                    <div class="card-header card-border-top">
                        <div class="row">
                            <div class="col-lg-6 mb-3 d-flex align-items-center">
                                <h5 class="fs-4 text-primary my-0">Thông tin tài khoản</h5>
                            </div>
                            <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                                <button type="submit" class="btn btn-success px-5">Cập nhật</button>
                                <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                            </div>
                        </div>
                    </div>
                    <hr class="m-0">
                    <div class="card-body">
                        <div class="col-sm-12 text-center mb-4">
                            <div class="img-user position-relative px-3">               
                                <img id="previewImage" onerror="this.src='/uploads/img_error3.png'" src="{{asset($info->user_img)}}" alt="" class="border rounded-circle" width="150px" height="150px">
                                <a class="edit-button">
                                    <i class='bx bxs-edit-alt'></i>
                                </a>
                                <input type="file" name="img" id="fileInput" class="file-input" style="display: none;">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label for="image-product" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" name="name" value="{{$info->name}}"/>
                                @if ($errors->has("name")) 
                                    @foreach ($errors->get("name") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }} <br>
                                        </small> 
                                    @endforeach 
                                @endif
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label for="cate-product" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" autocomplete="off" value="{{$info->username}}"/>
                                @if ($errors->has("username")) 
                                @foreach ($errors->get("username") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }} <br>
                                    </small> 
                                @endforeach 
                            @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <label for="cate-product" class="form-label">Email</label>
                                <input type="text" class="form-control" id="email" name="email" autocomplete="off" value="{{$info->email}}"/>
                                @if ($errors->has("email")) 
                                    @foreach ($errors->get("email") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }} <br>
                                        </small> 
                                    @endforeach 
                                @endif
                            </div> 
                        </div>
                    </div>
                </div>
            </form>
            <form id="formAuthenticationInfo" method="POST" action="{{ route('change.password') }}">
                @csrf
                <div class="card mb-4">
                    <div class="card-header card-border-top">
                        <div class="row">
                            <div class="col-lg-6 mb-3 d-flex align-items-center">
                                <h6 class="fs-4 text-primary my-0">Cập nhật mật khẩu</h6>
                            </div>
                            <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                                <button type="submit" class="btn btn-success px-5">Cập nhật</button>
                            </div>
                        </div>
                    </div>
                    <hr class="m-0">
                    <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-3 form-password-toggle position-relative">
                                    <div class="position-relative">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <input type="password" id="password-info" class="form-control" name="password" value="{{old('password')}}"
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
                                        style="right: 30px; top: 35px; transform: translateY(calc(-50%)); margin-top:15px"></i>
                                </div>
                                <div class="col-lg-6 mb-3 form-password-toggle position-relative">
                                    <div class="position-relative">
                                        <label for="password" class="form-label">Mật khẩu mới</label>
                                        <input type="password" id="new-pass" class="form-control" name="new-pass" value="{{old('new-pass')}}"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="new-pass" />
                                        <small class="error-text position-absolute text-danger fst-italic">a</small>
                                        @if ($errors->has('new-pass'))
                                            @foreach ($errors->get('new-pass') as $error)
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
                        </div>
                    </div>
                </form>
                <div class="card mb-4">
                    <div class="card-header card-border-top">
                        Các quyền hiện tại của bạn
                    </div>
                    <hr class="m-0">
                    <div class="card-body">
                        @if ($info->roles->isNotEmpty())
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <label for="image-product" class="form-label">Vai trò</label>
                                @foreach ($info->roles as $role)
                                    <input type="text" class="form-control" readonly value=" {{ $role->name }}"/>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="cate-product" class="form-label">Quyền</label>
                            <div class="demo-inline-spacing">
                                
                                @if ($role->permissions->isNotEmpty())
                                    @foreach ($role->permissions as $permission)
                                        <span class="badge btn-primary">{{ $permission->name }}</span>
                                    @endforeach
                                @else
                                    <span class="badge btn-primary">Không có quyền</span>
                                @endif
                            
                            </div>
                        </div>
                        @else
                            <div class="row">
                                <span class="text-center">Bạn chưa được cấp quyền!</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div> 
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("formAuthenticationInfo");
        const passwords = document.getElementById("password-info");
        const new_pass = document.getElementById("new-pass");

        let isNewPasswordValid = false;
        let isPasswordValid = false;

        form.addEventListener("submit", (e) => {
            e.preventDefault();
            let isNewPasswordValid = checkNewPassword();
            let isPasswordValid = checkPassword();
            new_pass.addEventListener("keyup", () => {
                isNewPasswordValid = checkNewPassword();
            });
            passwords.addEventListener("keyup", () => {
                isPasswordValid = checkPassword();
            });

            if (isNewPasswordValid && isPasswordValid) {
                form.submit();
            }
        });



        function checkPassword() {
            const passwordValue = passwords.value.trim();
            if (passwordValue === '') {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu không được để trống');
                passwords.focus();
                return false;
            } else if (passwordValue.length < 8) {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu phải đủ 8 ký tự');
                passwords.focus();
                return false;
            } else if (!(/[0-9]/.test(passwordValue))) {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu phải có số');
                passwords.focus();
                return false;
            } else if (!(/[A-Z]/.test(passwordValue))) {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu phải có chữ hoa');
                passwords.focus();
                return false;
            } else if (!(/[a-z]/.test(passwordValue))) {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu phải có chữ thường');
                passwords.focus();
                return false;
            } else if (!(/[^0-9a-zA-Z]/.test(passwordValue))) {
                passwords.classList.add("input-error");
                setErrorFor(passwords, 'Mật khẩu phải có ký tự đặc biệt');
                passwords.focus();
                return false;
            } else {
                setSuccessFor(passwords);
                return true;
            }
        }
        function checkNewPassword() {
            const passwordValue = new_pass.value.trim();
            if (passwordValue === '') {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới không được để trống');
                new_pass.focus();
                return false;
            } else if (passwordValue.length < 8) {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới phải đủ 8 ký tự');
                new_pass.focus();
                return false;
            } else if (!(/[0-9]/.test(passwordValue))) {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới phải có số');
                new_pass.focus();
                return false;
            } else if (!(/[A-Z]/.test(passwordValue))) {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới phải có chữ hoa');
                new_pass.focus();
                return false;
            } else if (!(/[a-z]/.test(passwordValue))) {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới phải có chữ thường');
                new_pass.focus();
                return false;
            } else if (!(/[^0-9a-zA-Z]/.test(passwordValue))) {
                new_pass.classList.add("input-error");
                setErrorFor(new_pass, 'Mật khẩu mới phải có ký tự đặc biệt');
                new_pass.focus();
                return false;
            } else {
                setSuccessFor(new_pass);
                return true;
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


