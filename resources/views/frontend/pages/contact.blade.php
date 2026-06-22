@extends('frontend.index')

@section('title')
    Liên hệ
@endsection

@section('banner')
    <!-- Start banner Area -->
   
    <!-- End banner Area -->
@endsection

@section('content')
    <section class="contact-page">
        
        <div class="container-fluid px-5 mt-5">
            <div class="row justify-content-center overflow-hidden">
                <div class="col-lg-6 text-center">
                    <div class="container-fluid px-0">
                        <div class="section-title">
                            <h1>Liên hệ</h1>
                            <p>Chúng tôi làm việc vì bạn - Hãy liên hệ với chúng tôi.</p>
                        </div>
                    </div>
                </div>
            </div>
            @if($contact->isEmpty())
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 box__shadow d-flex mb-5 justify-content-center">
                            <h4 class="text-center py-3 mb-0">Đang cập nhập...</h4>
                            <img src="{{asset('uploads/9318694.jpg')}}" alt="" class="img-fluid mx-auto" width="280px" height="280px">
                        </div>
                    </div>
                </div>
            @else
                @foreach($contact as $data)
                    <div class="row mb-4 overflow-x-hidden">
                        <div class="col-xl-6 col-lg-6 mb-3">
                            <div class="container-fluid px-0">
                                <div class="row justify-content-center">
                                    <div class="col-12 mb-3">
                                        <div class="bg-white d-flex align-items-center px-3 py-2 rounded gap-2 border-contact">
                                            <div class="icon-c">
                                                <i class="bi bi-geo-alt-fill"></i>
                                            </div>
                                            <span>{{$data->contact_address}}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="bg-white d-flex align-items-center px-3 py-2 rounded gap-2 border-contact">
                                            <div class="icon-c">
                                                <i class="bi bi-telephone-fill"></i>
                                            </div>
                                            <span>{{ ''.substr($data->contact_phone, 0, 4).' '.substr($data->contact_phone, 4, 3).' '.substr($data->contact_phone, 7) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="bg-white d-flex align-items-center px-3 py-2 rounded gap-2 border-contact">
                                            <div class="icon-c">
                                                <i class="bi bi-envelope-fill"></i>
                                            </div>
                                            <span>{{$data->contact_email}}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="bg-white d-flex align-items-center px-3 py-2 rounded gap-2 border-contact">
                                            <div class="icon-c">
                                                <i class="fab fa-facebook"></i>
                                            </div>
                                            <span>#</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <div class="col-xl-6 col-lg-6" data-aos="fade-left" data-aos-duration="2000">
                        <div class="container-fluid px-0">
                            <form action="{{route('contact-form.sto')}}" method="post" id="formContact">@csrf
                                <div class="row">
                                    <div class="col-lg-6 form-group mb-4 position-relative">
                                        <label for="fullname" class="form-label">Họ và tên</label>
                                        <input type="text" name="name" class="form-control" placeholder="Nhập tại đây..." id="fullname">
                                        <small class="text-error position-absolute text-danger fst-italic">a</small>
                                        @error('name')
                                            <small class="text-danger fst-italic">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 form-group mb-4 position-relative">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="Nhập tại đây..." id="email">
                                        <small class="text-error position-absolute text-danger fst-italic">a</small>
                                        @error('email')
                                            <small class="text-danger fst-italic">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 form-group mb-4 position-relative">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" name="phone" class="form-control" placeholder="Nhập tại đây..." id="phone">
                                        <small class="text-error position-absolute text-danger fst-italic">a</small>
                                    </div>
                                    <div class="col-lg-6 form-group mb-4 position-relative">
                                        <label for="problem" class="form-label">Tiêu đề</label>
                                        <input type="text" name="title" class="form-control" placeholder="Nhập tại đây..." id="problem">
                                        <small class="text-error position-absolute text-danger fst-italic">a</small>
                                        @error('title')
                                            <small class="text-danger fst-italic">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </div>
                                    <div class="col form-group mb-4 position-relative">
                                        <label for="content" class="form-label">Nội dung</label>
                                        <textarea class="form-control" name="content" id="content" rows="5"></textarea>
                                        <small class="text-error position-absolute text-danger fst-italic">a</small>
                                        @error('content')
                                            <small class="text-danger fst-italic">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center mb-3">
                                    <button class="btn btn-custom-pr text-white" style="padding: 5px 3rem">Gửi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-12 mb-5 overflow-hidden">
                        <div class="container-fluid px-0">
                            {!!$data->map_link!!}
                        </div>
                    </div>
                @endforeach
            @endif
    </section>
@endsection

@push('script-access')
    <script>
        const formContact = document.getElementById("formContact");
        const emailContact = document.getElementById("email");
        const fullnameContact = document.getElementById("fullname");
        const phoneContact = document.getElementById("phone");
        const problemContact = document.getElementById("problem");
        const contentContact = document.getElementById("content");

        let isEmailValid = false;
        let isFullnameValid = false;
        let isPhoneValid = false;
        let isProblemValid = false;
        let isContentValid = false;

        formContact.addEventListener("submit", (e) => {
            e.preventDefault();
            let isEmailValid = checkEmail();
            let isFullnameValid = checkFullName();
            let isPhoneValid = checkPhone();
            let isProblemValid = checkProblem();
            let isContentValid = checkContent();

            if(emailContact) {
                emailContact.addEventListener("keyup", () => {
                    isEmailValid = checkEmail();
                });
            }
            if(fullnameContact) {
                fullnameContact.addEventListener("keyup", () => {
                    isFullnameValid = checkFullName();
                });
            }
            if(phoneContact) {
                phoneContact.addEventListener("keyup", () => {
                    isPhoneValid = checkPhone();
                });
            }
            if(problemContact) {
                problemContact.addEventListener("keyup", () => {
                    isProblemValid = checkProblem();
                });
            }
            if(contentContact) {
                contentContact.addEventListener("keyup", () => {
                    isContentValid = checkContent();
                });
            }
            if (isEmailValid && isFullnameValid && isPhoneValid && isProblemValid && isContentValid) {
                formContact.submit();
            }
        });

        function checkEmail() {
            const emailValue = emailContact.value.trim();
            if (emailValue === '') {
                emailContact.classList.add("input-error");
                setErrorFor(emailContact, 'Email không được để trống');
                emailContact.focus();
                return false;
            } else if (emailValue.length < 5) {
                emailContact.classList.add("input-error");
                setErrorFor(emailContact, 'Email quá ngắn');
                emailContact.focus();
                return false;
            } else if(!checkEmailRegex(emailValue)) {
                emailContact.classList.add("input-error");
                setErrorFor(emailContact, 'Email sai định dạng');
                emailContact.focus();
                return false;
            }
            else {
                setSuccessFor(emailContact);
                return true;
            }
        }

        function checkEmailRegex(emailContact) {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                .test(emailContact);
        }

        function checkFullName() {
            if(fullnameContact) {

                const fullnameValue = fullnameContact.value.trim();
            
                if(fullnameValue === '') {
                    fullnameContact.classList.add("input-error");
                    setErrorFor(fullnameContact, 'Họ tên không được để trống');
                    fullnameContact.focus();
                    return false;
                }else if(isValidName(fullnameValue)) {
                    fullnameContact.classList.add("input-error");
                    setErrorFor(fullnameContact, 'Họ tên phải là chữ');
                    fullnameContact.focus();
                    return false;
                }else if(fullnameValue.length < 3) {
                    fullnameContact.classList.add("input-error");
                    setErrorFor(fullnameContact, 'Họ tên phải hơn 3 ký tự');
                    fullnameContact.focus();
                    return false;
                }else {
                    setSuccessFor(fullnameContact);
                    return true;
                }
            }
        }

        function removeAscent(str) {
            if (str === null || str === undefined) return str;
            str = str.toLowerCase();
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
            str = str.replace(/đ/g, "d");
            return str;
        }

        function isValidName(string) {
            var re = /^[a-zA-Z!@#\$%\^\&*\)\(+=._-]{3,}$/g // regex here
            return re.test(removeAscent(string))
        }

        function checkPhone() {
            const phoneContactValue = phoneContact.value.trim();
            if(phoneContactValue === '') {
                phoneContact.classList.add("input-error");
                setErrorFor(phoneContact, 'Vui lòng nhập trường này');
                phoneContact.focus();
                return false;
            }else if(!Number(phoneContactValue)) {
                phoneContact.classList.add("input-error");
                setErrorFor(phoneContact, 'Trường này phải là số');
                phoneContact.focus();
                return false;
            }else if(phoneContactValue.length < 10 || phoneContactValue.length > 10) {
                phoneContact.classList.add("input-error");
                setErrorFor(phoneContact, 'Phải đủ 10 chữ số');
                phoneContact.focus();
                return false;
            }else if(!phoneRegex(phoneContactValue)) {
                phoneContact.classList.add("input-error");
                setErrorFor(phoneContact, 'Số điện thoại không hợp lệ');
                phoneContact.focus();
                return false;
            }else {
                setSuccessFor(phoneContact);
                return true;
            }
        }

        function phoneRegex(phoneContact) {
            return /(84|0[3|5|7|8|9])+([0-9]{8})/.test(phoneContact)
        }

        function checkProblem() {
            if(problemContact) {
                const problemValue = problemContact.value.trim();
            
                if(problemValue === '') {
                    problemContact.classList.add("input-error");
                    setErrorFor(problemContact, 'Tiêu đề không được để trống');
                    problemContact.focus();
                    return false;
                }else if(problemValue.length < 3) {
                    problemContact.classList.add("input-error");
                    setErrorFor(problemContact, 'Tiêu đề quá ngắn');
                    problemContact.focus();
                    return false;
                }else {
                    setSuccessFor(problemContact);
                    return true;
                }
            }
        }

        function checkContent() {
            if(contentContact) {
                const contentValue = contentContact.value.trim();
            
                if(contentValue === '') {
                    contentContact.classList.add("input-error");
                    setErrorFor(contentContact, 'Nội dung không được để trống');
                    contentContact.focus();
                    return false;
                }else if(contentValue.length < 15) {
                    contentContact.classList.add("input-error");
                    setErrorFor(contentContact, 'Nội dung quá ngắn');
                    contentContact.focus();
                    return false;
                }else {
                    setSuccessFor(contentContact);
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
