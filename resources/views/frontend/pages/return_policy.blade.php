{{--
    @param int $soNgayTra        days the customer may still ask to send an order back
    @param int $soNgayTuHoanTat  days after delivery before an order completes itself
--}}
@extends('frontend.index')

@section('title')
    Chính sách trả hàng và hoàn tiền
@endsection

@section('banner')
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Chính sách</h1>
                    <nav class="d-flex align-items-center">
                        <a title="Quay về trang chủ" href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Trả hàng và hoàn tiền</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    @php
        use App\Models\OrderReturnModel;

        $lyDoLoiShop = collect(OrderReturnModel::REASONS)
            ->filter(fn ($nhan, $ma) => in_array($ma, OrderReturnModel::SHOP_AT_FAULT, true));
        $lyDoLoiKhach = collect(OrderReturnModel::REASONS)
            ->reject(fn ($nhan, $ma) => in_array($ma, OrderReturnModel::SHOP_AT_FAULT, true));
    @endphp

    <section class="contact-page">
        <div class="container-xxl px-3 my-5">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8 text-center">
                    <div class="section-title">
                        <h1>Trả hàng và hoàn tiền</h1>
                        <p class="mb-0">
                            Trang này nói rõ bạn được trả hàng trong bao lâu, trả được những gì,
                            ai chịu phí gửi trả và tiền về bằng cách nào.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9 policy-return">

                    <h4>Thời hạn</h4>
                    <p>
                        Bạn có <b>{{ $soNgayTra }} ngày</b> kể từ khi đơn hàng hoàn tất để gửi yêu cầu trả hàng.
                        Quá hạn thì nút gửi yêu cầu sẽ không còn trên trang đơn hàng nữa.
                    </p>
                    <p>
                        Đơn hàng hoàn tất khi bạn bấm <b>"Đã nhận hàng"</b>. Nếu bạn quên bấm, hệ thống tự hoàn tất
                        sau <b>{{ $soNgayTuHoanTat }} ngày</b> kể từ lúc đơn vị vận chuyển báo giao thành công —
                        và {{ $soNgayTra }} ngày trả hàng được tính từ mốc đó.
                    </p>

                    <h4>Mỗi đơn trả hàng một lần</h4>
                    <p>
                        Một đơn hàng chỉ trả được <b>một lần</b>. Bạn chọn những món muốn trả trong cùng một yêu cầu,
                        không nhất thiết phải trả hết cả đơn — giữ lại đôi vừa chân, gửi trả đôi không vừa là chuyện
                        bình thường. Nhưng khi yêu cầu đó đã được duyệt và xử lý xong thì đơn khép lại, phần còn lại
                        không gửi trả được nữa.
                    </p>
                    <p>
                        Lý do: mỗi lần trả là một lần nhân viên vận chuyển đến tận nhà bạn lấy hàng, và chuyến đi đó
                        tốn tiền thật. Gom vào một lần vừa đỡ cho cửa hàng, vừa đỡ cho bạn phải chờ hai lượt lấy hàng.
                    </p>
                    <p>
                        Yêu cầu <b>bị từ chối</b> hoặc <b>bạn tự huỷ</b> thì không tính. Trong hai trường hợp đó chưa
                        có ai đến lấy hàng, nên còn trong thời hạn là bạn gửi lại được — chẳng hạn khi cửa hàng từ chối
                        vì ảnh chụp chưa rõ lỗi.
                    </p>

                    <h4>Ai chịu phí gửi trả</h4>
                    <p>Phí gửi trả là tiền cước chuyến xe đến lấy hàng ở chỗ bạn và mang về cửa hàng. Ai chịu tuỳ vào lý do:</p>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lý do bạn chọn khi gửi yêu cầu</th>
                                    <th>Bên chịu phí</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lyDoLoiShop as $nhan)
                                    <tr>
                                        <td>{{ $nhan }}</td>
                                        <td><b>Cửa hàng</b></td>
                                    </tr>
                                @endforeach
                                @foreach ($lyDoLoiKhach as $nhan)
                                    <tr>
                                        <td>{{ $nhan }}</td>
                                        <td><b>Người mua</b> &mdash; trừ vào tiền hoàn</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p>
                        Ngay ở màn hình gửi yêu cầu, sau khi bạn chọn lý do, hệ thống hiện luôn bên nào chịu phí —
                        bạn biết trước khi bấm gửi chứ không phải đến lúc nhận tiền mới biết.
                    </p>
                    <p>
                        Nếu cửa hàng nhận hàng trả mà không qua đơn vị vận chuyển thì không có phí nào để chia.
                    </p>

                    <h4>Tiền hoàn được tính thế nào</h4>
                    <ul>
                        <li>Tính theo <b>giá bạn đã trả</b> cho những món gửi trả, không theo giá đang bán hiện tại.</li>
                        <li>Nếu đơn có mã giảm giá, phần giảm được chia theo giá trị từng món và trừ khỏi tiền hoàn
                            của những món bạn trả.</li>
                        <li><b>Phí giao hàng ban đầu</b> chỉ được hoàn khi bạn trả toàn bộ đơn. Trả một phần thì không,
                            vì kiện hàng đó vẫn đã được chở đến chỗ bạn.</li>
                        <li>Trừ phí gửi trả nếu lý do thuộc về người mua, theo bảng ở trên.</li>
                    </ul>

                    <h4>Tiền về đâu</h4>
                    <p>
                        Tiền hoàn được cộng vào <a href="{{ route('user.wallet') }}">SPay</a> ngay khi cửa hàng nhận
                        và kiểm tra xong hàng trả. Không phụ thuộc bạn đã thanh toán bằng cách nào lúc đặt hàng:
                        tiền mặt khi nhận, VNPay, MoMo hay chính SPay thì tiền hoàn đều về SPay.
                    </p>
                    <p>
                        Cách này nhanh hơn hoàn ngược qua cổng thanh toán hoặc ngân hàng, vốn mất vài ngày làm việc
                        và phải đối soát từng giao dịch.
                    </p>

                    <h4>Các bước sau khi bạn gửi yêu cầu</h4>
                    <ol>
                        <li>Cửa hàng xem lý do và ảnh bạn gửi, rồi duyệt hoặc từ chối kèm lý do.</li>
                        <li>Được duyệt: nhân viên vận chuyển đến địa chỉ nhận hàng của đơn để lấy hàng. Bạn đóng gói
                            sẵn giúp cửa hàng.</li>
                        <li>Cửa hàng nhận và kiểm tra hàng, rồi hoàn tiền vào SPay.</li>
                    </ol>
                    <p>
                        Chừng nào cửa hàng chưa duyệt, bạn vẫn <b>huỷ được yêu cầu</b> trong mục lịch sử trả hàng
                        ở trang đơn hàng. Duyệt rồi thì không huỷ được nữa, vì lúc đó vận đơn lấy hàng có thể đã tạo.
                    </p>

                    <h4>Những trường hợp không trả được</h4>
                    <ul>
                        <li>Đơn chưa hoàn tất, hoặc đã quá {{ $soNgayTra }} ngày kể từ khi hoàn tất.</li>
                        <li>Đơn đã qua một lần trả hàng được duyệt.</li>
                        <li>Đơn đã huỷ, hoặc giao không thành công và đã hoàn về kho — những đơn này xử lý theo luồng
                            hoàn tiền riêng, không cần bạn gửi yêu cầu.</li>
                    </ul>

                    <p class="text-muted">
                        Còn thắc mắc, bạn gọi hotline hoặc nhắn qua trang <a href="{{ route('contact.page') }}">Liên hệ</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css-access')
    <style>
        .policy-return h4 {
            margin-top: 32px;
            margin-bottom: 10px;
            font-size: 19px;
            font-weight: 600;
        }

        .policy-return ul,
        .policy-return ol {
            padding-left: 20px;
        }

        /* frontend/mmenu/demo.css strips every marker on the site with
           `* { list-style: none !important }` for its menus. A policy page
           reads as loose paragraphs without them, and nothing short of
           !important gets past a universal !important. */
        .policy-return ul > li {
            list-style: disc outside !important;
        }

        .policy-return ol > li {
            list-style: decimal outside !important;
        }

        .policy-return li {
            margin-bottom: 6px;
        }

        .policy-return .table th {
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6B6867;
        }
    </style>
@endpush
