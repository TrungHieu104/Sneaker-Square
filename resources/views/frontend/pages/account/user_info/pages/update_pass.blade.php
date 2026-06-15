@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
<div>
    <h3 class="mb-5">Thay đổi mật khẩu</h3>
    <p>Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu cho người khác.</p>
    <div class="bg-white">
            <div class="row p-md-5 p-4">
                <div class="alert alert-warning">
                    <p class="text-center">Bạn cần xác nhận đổi mật khẩu trước khi sử dụng tính năng này.</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-social text-white" data-bs-toggle="modal"
                            data-bs-target="#verifiedEmail">Xác nhận</button>
                    </div>
                    <!-- ---model------ -->
                    <div class="modal fade" id="verifiedEmail" tabindex="-1"
                        aria-labelledby="verifiedEmailLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="verifiedEmailLabel">Xác nhận</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="{{ route('send_mail_pass') }}">
                                        @csrf
                                        <div>
                                            <div class="d-flex justify-content-center mt-4">
                                                <svg height="80px" width="80px" version="1.1"
                                                    id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                                    viewBox="0 0 512.00 512.00" xml:space="preserve"
                                                    fill="#000000" stroke="#000000"
                                                    stroke-width="0.00512">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <path style="fill:#FEAF50;"
                                                            d="M256,512C-4.9,412.338,3.285,140.287,16.2,38.83C19.027,16.619,37.967,0,60.357,0H256h195.643 c22.39,0,41.33,16.619,44.157,38.83C508.716,140.287,516.901,412.338,256,512z">
                                                        </path>
                                                        <path style="fill:#FE9443;"
                                                            d="M256,512V0h195.643c22.39,0,41.33,16.619,44.157,38.83C508.716,140.287,516.901,412.338,256,512z">
                                                        </path>
                                                        <path style="fill:#FEF184;"
                                                            d="M80.306,55.652h351.388c5.758,0,10.555,4.387,11.086,10.12 c9.436,101.955,1.243,301.341-186.78,385.992C67.977,367.113,59.784,167.727,69.22,65.772 C69.751,60.039,74.549,55.652,80.306,55.652z">
                                                        </path>
                                                        <path style="fill:#FED76E;"
                                                            d="M442.78,65.772c9.436,101.955,1.243,301.341-186.78,385.992V55.652h175.694 C437.452,55.652,442.249,60.039,442.78,65.772z">
                                                        </path>
                                                        <path style="fill:#FE9443;"
                                                            d="M373.982,158.053l-116.87,155.826c-4.204,5.605-10.802,8.904-17.809,8.904 s-13.605-3.299-17.809-8.904l-50.087-66.783c-7.376-9.835-5.383-23.789,4.452-31.165c9.834-7.377,23.789-5.384,31.165,4.452 l32.278,43.038l99.061-132.082c7.375-9.835,21.328-11.828,31.165-4.452C379.364,134.264,381.358,148.218,373.982,158.053z">
                                                        </path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-center m-auto mt-2 w-75">
                                                    Khi bạn xác nhận đổi mật khẩu, chúng tôi sẽ gửi liên kết đổi mật khẩu về email đã đăng ký tài khoản tại cửa hàng.
                                                </p>
                                                <div class="d-flex justify-content-center m-auto">
                                                    <button
                                                        class="d-flex gap-3 btn btn-social text-white mt-5"><i
                                                            class='bx bx-envelope'></i> Xác nhận</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>
@endsection