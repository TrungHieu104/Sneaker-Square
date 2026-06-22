@extends('frontend.index')

@section('title')
    Chính sách giao hàng
@endsection

@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Giao hàng</h1>
                    <nav class="d-flex align-items-center">
                        <a href="{{route('home.page')}}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Chính sách giao hàng</li>
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
                        <h1>Chính sách giao hàng</h1>
                        <p>
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col">
                    <div class="w-100 box__shadow p-4">
                        <h5 class="text-uppercase fw-bold mt-2">1. Quy định thời gian giao nhận hàng</h5>
                        <p>
                            Thời gian phục vụ giao hàng: thứ 2 đến thứ 7 (trừ chủ nhật, ngày lễ, tết). <br>
                            Khu vực giao hàng:
                        </p>
                        <div>
                            <table class="table table-light w-100">
                                <thead>
                                    <tr class="text-center">
                                        <th>Khu vực</th>
                                        <th>Giao hàng tiêu chuẩn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-center">
                                        <td>
                                            Nội thành (HCM)
                                        </td>
                                        <td>
                                            1-2 ngày làm việc
                                        </td>
                                    </tr>
                                    <tr class="text-center">
                                        <td>
                                            Nội vùng
                                        </td>
                                        <td>
                                            1-3 ngày làm việc
                                        </td>
                                    </tr>
                                    <tr class="text-center">
                                        <td>
                                            Liên vùng (giữa 3 thành phố HCM, Hà Nội và Đà Nẵng)
                                        </td>
                                        <td>
                                            3-5 ngày làm việc
                                        </td>
                                    </tr>
                                    <tr class="text-center">
                                        <td>
                                            Liên vùng (từ 3 thành phố lớn HCM, Hà Nội, Đà Nẵng đến các thành phố khác thuộc vùng khác)
                                        </td>
                                        <td>
                                            5-7 ngày làm việc
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p>
                            Hỗ trợ giao hàng ngay qua các ứng dụng như Grab, Ahamove,… nếu sản phẩm có sẵn tại kho của 
                            Sneaker Square trùng với nội thành địa chỉ giao hàng của Quý khách.
                        </p>
                        <h5 class="text-uppercase fw-bold mt-2">2. Quy định kiểm tra hàng hóa khi giao nhận:</h5>
                        <p  style="text-align: justify;">
                            Nhằm bảo vệ tối đa quyền lợi khách hàng khi mua sắm tại Sneaker Square, chúng tôi có chính sách đồng kiểm khi nhận hàng như sau:<br>
                            - Quý khách được quyền yêu cầu nhân viên giao hàng mở niêm phong thùng hàng của Sneaker Square để kiểm tra số lượng, màu sắc, chủng loại, kích cỡ, ngoại quan của các sản phẩm đã mua trước khi nhận. (Lưu ý: Việc đồng kiểm chỉ áp dụng kiểm tra ngoại quan, không áp dụng cho việc dùng thử sản phẩm và kiểm tra sâu chi tiết lỗi của sản phẩm).
                            - Trường hợp Quý khách không ưng ý sản phẩm nhưng đã thanh toán online, Quý khách có thể gửi lại sản phẩm cho nhân viên giao hàng đồng thời Quý khách vui lòng liên hệ Sneaker Square để được hỗ trợ về vấn đề hoàn tiền mà không mất bất cứ chi phí nào.
                            - Trường hợp nhân viên giao hàng từ chối cho Quý khách kiểm tra hàng, Quý khách có quyền từ chối nhận hàng, sau đó liên hệ đến hotline để Sneaker Square yêu cầu nhân viên phải giao hàng lại cho Quý khách và phải cho Quý khách kiểm tra hàng. Hoặc Quý khách có thể gọi ngay vào hotline để được hỗ trợ ngay lập tức.
                            - Trường hợp Quý khách hài lòng với tình trạng sản phẩm được giao và đồng ý mua sản phẩm, Quý khách vui lòng ký vào biên bản đồng kiểm. Bằng việc ký vào biên bản đồng kiểm xác nhận hài lòng với tình trạng sản phẩm được giao, Quý khách xác nhận đã hoàn thành việc đồng kiểm và hoàn tất mua hàng.
                            - Các sản phẩm nằm trong chương trình ưu đãi, khuyến mãi, giảm giá, quà tặng… không áp dụng đổi/trả, vì vậy quý khách vui lòng kiểm tra sản phẩm kỹ trước khi nhận hàng. Sneaker Square sẽ không áp dụng đổi/ trả với các đơn hàng đã được xác nhận đồng kiểm bởi Quý khách.
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
