@extends('backend.index')

@section('title')
    Thống kê sản phẩm
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Thống kê</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="row">
            <div class="col-12">
                <div class="card-header">
                        <div class="row">
                            <div class="col-lg-10 d-flex align-items-center">
                                <h5 class="mb-0">Sản phẩm sắp hết hàng</h5>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            <th class="text-center">Tên sản phẩm</th>
                            <th class="text-center">Màu sắc</th>
                            <th class="text-center">Còn lại</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($stock->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center">
                                    Không có sản phẩm.
                                </td>
                            </tr>
                        @else
                            @foreach ($stock as $s)
                                @if ($s->total_quantity <= 10)
                                    <tr>
                                        <td class="text-center">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="px-3">
                                            <div class="img-container_admin mx-auto border p-1 rounded">
                                                <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($s->getProducts->pro_img) }}" class="img-list_admin rounded"
                                                    alt="{{ $s->pro_name }}">
                                            </div>
                                        </td>

                                        <td class="text-center text-wrap">
                                            <a class="tab-sort" href="?sort-by=pro_name&sort-type={{ $orderBy === 'pro_name' ? $orderType : 'desc' }}">
                                                {{ $s->getProducts->pro_name }}
                                            </a>
                                        </td>

                                        <td class="text-center">
                                            <a class="tab-sort" href="?sort-by=color&sort-type={{ $orderBy === 'color' ? $orderType : 'desc' }}">
                                                {{ $s->color_id ? $s->color_vn:'Không có màu sắc'}}
                                            </a>
                                        </td>

                                        <td class="text-center">
                                            @if ($s->total_quantity == 0)
                                                <span class="text-danger">Hết hàng</span>
                                            @else
                                                {{ $s->total_quantity }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4 card-border-top">
        <div class="row">
            <div class="col-12">
                <div class="card-header">
                        <div class="row">
                            <div class="col-lg-10 d-flex align-items-center">
                                <h5 class="mb-0">Sản phẩm bán chạy trong tháng</h5>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            <th class="text-center">Tên sản phẩm</th>
                            <th class="text-center">Màu sắc</th>
                            <th class="text-center">Giá vốn</th>
                            <th class="text-center">Giá đã bán</th>
                            <th class="text-center">Đã bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($statistical->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center">
                                    Không có sản phẩm.
                                </td>
                            </tr>
                        @else
                            @foreach ($statistical as $s)
                                <tr>
                                    <td class="text-center">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($s->product ? $s->product->pro_img:'') }}" class="img-list_admin rounded"
                                                alt="{{ $s->pro_name }}">
                                        </div>
                                    </td>

                                    <td class="text-center text-wrap">
                                        <a class="tab-sort" href="?sort-by=pro_name&sort-type={{ $orderBy === 'pro_name' ? $orderType : 'desc' }}">
                                            {{ $s->pro_name }}
                                        </a>
                                    </td>

                                    <td class="text-center">
                                        <a class="tab-sort" href="?sort-by=color&sort-type={{ $orderBy === 'color' ? $orderType : 'desc' }}">
                                            {{ is_null($s->color) ? 'Không có màu sắc':$s->color}}
                                        </a>
                                    </td>

                                    <td class="text-center">
                                        {{ $s->product ? number_format($s->capital_price, 0, ',', '.'):'Sản phẩm đã bị xóa' }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($s->price, 0, ',', '.') }} VNĐ
                                    </td>

                                    <td class="text-center">
                                        {{ $s->total_quantity }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection