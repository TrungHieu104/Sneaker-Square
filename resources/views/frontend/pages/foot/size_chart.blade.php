{{--
    @param array<int, array{from: int, to: int, asian: int}> $ranges  size => foot length it fits, in millimetres
--}}
@extends('frontend.index')

@section('title')
    Bảng quy đổi size
@endsection

@section('description', 'Bảng quy đổi size giày EU theo chiều dài bàn chân tại Sneaker Square, kèm cách đo chân tại nhà.')

@push('css-access')
    <link rel="stylesheet" href="{{ asset_v('frontend/css/size-chart.css') }}">
@endpush

@section('banner')
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Bảng quy đổi size</h1>
                    <nav class="d-flex align-items-center">
                        <a title="Quay về trang chủ" href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Bảng quy đổi size</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="size-chart-page">
        <div class="container-xxl px-3 my-5">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8 text-center">
                    <div class="section-title">
                        <h1>Bảng quy đổi size giày</h1>
                        <p class="mb-0">
                            Size giày ở Việt Nam, kể cả trên Sneaker Square, đánh số theo chuẩn EU.
                            Dò size theo chiều dài bàn chân, đo từ gót đến đầu ngón dài nhất.
                            Bảng đã chừa sẵn {{ \App\Services\FootSize::TOE_ROOM_MM }} mm cho ngón chân, bạn chỉ cần dò đúng chiều dài bàn chân thật.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-5">
                    <div class="size-chart-guide">
                        <h4>Cách đo chân tại nhà</h4>
                        <ol class="size-chart-guide__steps">
                            <li>Đặt một tờ giấy sát tường, đứng lên giấy bằng chân trần, <b>gót chạm tường</b>.</li>
                            <li>Đánh dấu <b>đầu ngón chân dài nhất</b>, rồi dùng thước đo từ mép giấy sát tường đến dấu đó, tính bằng mm.</li>
                            <li>Đo cả hai chân, lấy số của <b>chân dài hơn</b> rồi dò bảng bên cạnh.</li>
                        </ol>

                        <div class="size-chart-guide__measure">
                            <p class="mb-2"><b>Không có thước?</b> Chụp bàn chân trên một tờ giấy A4, hệ thống sẽ đo và gợi ý size giúp bạn.</p>
                            <a href="{{ route('foot.measure') }}" class="btn btn-cart_order text-white fw-bold w-100">
                                <i class='bx bx-camera'></i> Đo cỡ chân bằng ảnh
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <table class="table size-chart-table mb-2">
                        <thead>
                            <tr>
                                <th scope="col">Size<small>VN / EU</small></th>
                                <th scope="col">Chiều dài bàn chân</th>
                                <th scope="col">Size Nhật / Hàn<small>cm / mm</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ranges as $size => $range)
                                <tr>
                                    <th scope="row">{{ $size }}</th>
                                    <td>{{ $range['from'] }} – {{ $range['to'] }} mm</td>
                                    <td class="size-chart-table__asian">{{ number_format($range['asian'] / 10, 1, ',', '') }} / {{ $range['asian'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="size-chart-note">
                        Chân dài bằng số lớn của một size vẫn vừa size đó; dài hơn 1 mm thì chọn size kế tiếp.
                        Size US, UK khác nhau theo từng hãng và giữa giày nam, nữ, nên bảng không quy đổi sang hai hệ này.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
