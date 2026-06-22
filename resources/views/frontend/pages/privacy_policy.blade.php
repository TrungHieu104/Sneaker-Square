@extends('frontend.index')

<!-- Title -->
@section('title')
    Chính sách bảo mật
@endsection

<!-- Banner -->
@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Bảo mật</h1>
                    <nav class="d-flex align-items-center">
                        <a href="{{route('home.page')}}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Chính sách bảo mật</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start Content Area -->
    <section class="contact-page">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center"  data-aos="fade-up" data-aos-duration="2000">
                    <div class="section-title">
                        <h1>Chính sách bảo mật</h1>
                        <p>
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col">
                    <div class="w-100 box__shadow p-4">
                        <h6 class="text-uppercase fw-bold mb-0 mt-2">1. Thu Thập Thông Tin Cá Nhân</h6>
                        <p class="mb-0">
                            Chúng tôi có thể thu thập các loại thông tin cá nhân sau đây: <br>
                        </p>
                        <p class="mb-0">&#8226; Tên và thông tin liên hệ (địa chỉ email, số điện thoại) khi bạn đăng ký tài khoản hoặc đặt hàng.</p>
                        <p class="mb-0">&#8226; Thông tin địa chỉ giao hàng để giao hàng sản phẩm cho bạn.</p>
                        <p class="mb-0">&#8226; Thông tin thanh toán (thẻ tín dụng, số tài khoản ngân hàng) để xử lý thanh toán đơn hàng.</p>
                        <p class="mb-0">&#8226; Thông tin cá nhân bạn cung cấp khi tham gia các cuộc khảo sát hoặc tham gia chương trình khuyến mãi.</p>
                        
                        <h6 class="text-uppercase fw-bold mb-0 mt-2">2. Sử Dụng Thông Tin Cá Nhân</h6>
                        <p class="mb-0">
                            Chúng tôi sử dụng thông tin cá nhân của bạn để: <br>
                        </p>
                        <p class="mb-0">&#8226; Xử lý đơn hàng và giao hàng sản phẩm cho bạn.</p>
                        <p class="mb-0">&#8226; Liên hệ với bạn về đơn hàng, thông báo vận chuyển, và dịch vụ khách hàng.</p>
                        <p class="mb-0">&#8226; Cải thiện trải nghiệm của bạn trên trang web của chúng tôi và tùy chỉnh nội dung và quảng cáo.</p>
                        <p class="mb-0">&#8226; Gửi thông tin về sản phẩm, dịch vụ, và khuyến mãi mà chúng tôi cho là bạn có thể quan tâm.</p>

                        <h6 class="text-uppercase fw-bold mb-0 mt-2">3. Bảo Mật Thông Tin Cá Nhân</h6>
                        <p class="mb-0"  style="text-align: justify;">
                            Chúng tôi cam kết bảo mật thông tin cá nhân của bạn và đã áp dụng các biện pháp bảo mật thích hợp để 
                            ngăn chặn truy cập trái phép hoặc tiết lộ thông tin cá nhân. Tuy nhiên, chúng tôi không thể đảm bảo 
                            100% an toàn của thông tin trên Internet và không chịu trách nhiệm đối với bất kỳ sự truy cập trái 
                            phép nào.
                        </p>

                        <h6 class="text-uppercase fw-bold mb-0 mt-2">4. Chia Sẻ Thông Tin Cá Nhân</h6>
                        <p class="mb-0"  style="text-align: justify;">
                            Chúng tôi không chia sẻ thông tin cá nhân của bạn với bất kỳ bên thứ ba nào trừ khi cần thiết để 
                            xử lý đơn hàng hoặc tuân thủ các yêu cầu pháp luật.
                        </p>

                        <h6 class="text-uppercase fw-bold mb-0 mt-2">5. Quyền Truy Cập Và Sửa Đổi Thông Tin Cá Nhân</h6>
                        <p class="mb-0"  style="text-align: justify;">
                            Bạn có quyền truy cập và sửa đổi thông tin cá nhân của mình trong tài khoản cá nhân trên trang web của chúng tôi.
                        </p>

                        <h6 class="text-uppercase fw-bold mb-0 mt-2">6. Sự Thay Đổi Và Cập Nhật Chính Sách Bảo Mật</h6>
                        <p class="mb-0"  style="text-align: justify;">
                            Chúng tôi có thể thay đổi hoặc cập nhật chính sách bảo mật này để phản ánh các thay đổi trong 
                            cách chúng tôi thu thập, sử dụng, hoặc bảo vệ thông tin cá nhân. Chúng tôi khuyến nghị bạn xem 
                            xét chính sách bảo mật này định kỳ để hiểu rõ cách chúng tôi bảo vệ thông tin cá nhân của bạn.
                        </p>

                        <p class="fst-italic">
                            * Mọi chi tiết hoặc thắc mắc quý khách vui lòng liên hệ với <label class="fw-bold">Sneaker Square</label> để được hỗ trợ. Xin chân thành cảm ơn.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Start Content Area -->

@endsection
