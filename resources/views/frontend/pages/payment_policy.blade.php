@extends('frontend.index')

@section('title')
    Chính sách thanh toán
@endsection

@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Thanh toán</h1>
                    <nav class="d-flex align-items-center">
                        <a href="{{route('home.page')}}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Chính sách thanh toán</li>
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
                        {{-- <h1>{{$detailFaq->faq_name}}</h1> --}}
                        {{-- <p>
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.
                        </p> --}}
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col">
                    <div class="w-100 box__shadow p-4">
                        <h5 class="text-uppercase fw-bold mt-2">Chính sách thanh toán</h5> 
                        <p>
                            Sneaker Square hiện đang hỗ trợ thanh toán qua các hình thức: <br>
                            1. Thanh toán online bằng hình thức chuyển khoản (Áp dụng cho toàn bộ 49 ngân hàng tại Việt Nam). <br>
                            2. Thanh toán online qua ví điện tử Momo. <br>
                            3. Thanh toán trả góp online qua thẻ tín dụng (Visa, Mastercard,...). <br>
                            4. Thanh toán khi nhận hàng (COD). <br>
                        </p>
                        <p class="fw-bold mb-0">
                            Thanh toán online qua thẻ ATM nội địa <br>
                        </p>
                        <p class="mb-0">
                            - Khách hàng thanh toán số tiền mua hàng bằng thẻ ATM nội địa của 49 ngân hàng trong nước phát hành. <br>
                            - Hình thức thanh toán đơn giản, dễ sử dụng, trực quan và an toàn chỉ trong 3 bước: <br>
                        </p>
                        <p class="mb-0">&#8226; Nhập thông tin thẻ</p>
                        <p class="mb-0">&#8226; Xác thực khách hàng</p>
                        <p class="mb-0">&#8226; Thanh toán và nhận hóa đơn</p>
                        <p>
                            Ngoài ra, để thanh toán bằng thẻ ngân hàng nội địa, thẻ của khách hàng phải được đăng ký sử dụng tính năng thanh toán trực tuyến, hoặc ngân hàng điện tử của Ngân hàng. Giao dịch được ghi nhận là thành công khi khách hàng nhận được thông báo từ hệ thống Snecker Square.
                            Nội dung chuyển khoản quý khách chỉ ghi mã đơn hàng hoặc tên sử dụng của các trang mạng xã hội.
                        </p>
                        <p class="fw-bold mb-0">
                            Thanh toán online qua ứng dụng ví điện tử Momo <br>
                        </p>
                        <p>
                            Ứng dụng thanh toán di động giúp đáp ứng nhu cầu thanh toán của quý khách hàng mà không cần tiền mặt hay qua ngân hàng.
                            Quý khách có thể lựa chọn thêm phương thức thanh toán qua ví điện tử bằng cách liên hệ với Fanpage hoặc hotline của <label class="fw-bold">Sneaker Square</label> để được hỗ trợ.
                        </p>
                        <p class="fw-bold mb-0">
                            Thanh toán qua thẻ tín dụng/thẻ ghi nợ Visa, Mastercard, JCB <br>
                        </p>
                        <p>
                            Khách hàng sử dụng các các loại thẻ tín dụng/ghi nợ/trả trước VISA, MasterCard, JCB của các 
                            ngân hàng trong và ngoài nước phát hành. <br>
                            Toàn bộ hệ thống thanh toán được bảo mật theo tiêu chuẩn quốc tế PCI - DSS, khách hàng hoàn toàn có thể yên 
                            tâm khi thanh toán bằng hình thức trả trước qua thẻ tín dụng/thẻ ghi nợ tại website. <br>
                            Qúy khách có thể lựa chọn theo phương thức thanh toán trả góp qua thẻ tín dụng/thẻ ghi nợ bằng cách liên hệ với chúng tôi qua 
                            Fanpage hoặc hotline để chúng tôi hỗ trợ.
                        </p>
                        <p class="fw-bold mb-0">
                            Thanh toán khi nhận hàng (COD) <br>
                        </p>
                        <p>
                            Thanh toán khi nhận hàng (COD): Khách hàng vui lòng chuyển khoản cọc trước 500.000 VNĐ đối với đơn hàng trên 2.000.000 VNĐ
                            Quý khách có quyền kiểm tra đơn hàng, sản phẩm trước khi nhận hàng và thanh toán.
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
