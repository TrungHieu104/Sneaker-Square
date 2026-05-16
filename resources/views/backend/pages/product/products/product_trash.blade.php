@extends('backend.index')

@section('title')
    Danh sách sản phẩm | Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý sản phẩm</h5>
            <div class="px-4">
                <a href="{{ route('product.index') }}" title="Danh sách sản phẩm" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            @if($productTrash->isNotEmpty())
                <div class="d-flex gap-3 mb-3">
                    <a href="{{ route('product.restore.all') }}" title="Khôi phục" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-trash-restore"></i></a>
                    <a href="{{ route('product.delete.all') }}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=pro_code&sort-type={{ $orderBy === 'pro_code' ? $orderType : 'desc' }}">
                                    Mã sản phẩm
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=pro_name&sort-type={{ $orderBy === 'pro_name' ? $orderType : 'desc' }}">
                                    Tên sản phẩm
                                </a>
                            </th>
                            <th class="text-center">Giá nhập</th>
                            <th class="text-center">Giá bán</th>
                            <th class="text-center">Giá sale</th>
                            <th class="text-center">Đường dẫn</th>
                            <th class="text-center">Từ khóa</th>
                            <th class="text-center">Lượt xem</th>
                            <th class="text-center">Ngày bán</th>
                            <th class="text-center">Danh mục</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($productTrash->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">
                                    Không có sản phẩm trong thùng rác.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $productTrash->firstItem();
                            @endphp
                            @foreach($productTrash as $product)
                                <tr>
                                    <td class="text-center">
                                        {{ $stt }}
                                    </td>

                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($product->pro_img) }}" class="img-list_admin rounded"
                                                alt="{{ $product->pro_name }}">
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_code }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_name }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($product->capital_price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($product->pro_price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_price_sale != 0 ? number_format($product->pro_price_sale, 0, ',', '.'):'Không giảm giá' }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_slug }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_meta_keywords ? $product->pro_meta_keywords:'Chưa có từ khóa' }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->pro_views }}
                                    </td>

                                    <td class="text-center">
                                        {{ date('d-m-Y', strtotime($product->pro_date)) }}
                                    </td>

                                    <td class="text-center">
                                        {{ $product->getCate ? $product->getCate->cate_name:'Chưa xác định' }}
                                    </td>

                                    <td class="text-center">
                                        <div>
                                            <div>
                                                <a class="btn btn-success btn-sm mx-1" title="Khôi phục"
                                                    href="{{route('product.restore', $product->pro_id)}}">
                                                    <i class="fas fa-trash-restore"></i>
                                                </a>
    
                                                <a href="{{route('product.delete', $product->pro_id)}}"
                                                    onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này vĩnh viễn?')"
                                                    class="btn btn-danger btn-sm ms-1"
                                                    title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </a>                                        
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                @php
                                    $stt++
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{$productTrash->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
