@extends('backend.index')

@section('title')
    Danh sách sản phẩm
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Danh sách</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý sản phẩm</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{ route('product.create') }}" title="Thêm mới" class="btn btn-success">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{ route('product.trashed') }}" class="btn btn-danger" title="Thùng rác">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a> 
            </div>
        </div>
        <div class="card-body">
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
                            <th class="text-center" style="width: 60px;">Nổi bật</th>
                            <th class="text-center" style="width: 60px;">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($allProducts->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có sản phẩm để hiển thị.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $allProducts->firstItem();
                            @endphp
                            @foreach ($allProducts as $product)
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

                                    <td>
                                        {{ $product->pro_code }}
                                    </td>

                                    <td>
                                        {{ $product->pro_name }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($product->capital_price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($product->pro_price, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        {{ $product->pro_price_sale != 0 ? number_format($product->pro_price_sale, 0, ',', '.'):'Không giảm giá' }}
                                    </td>

                                    <td>
                                        {{ $product->pro_slug }}
                                    </td>

                                    <td class="text-center text-wrap">
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
                                        <input name="pro_hot" 
                                                    class="form-check-input"  
                                                    type="checkbox" value="1" id="hot-{{ $product->pro_id }}" 
                                                    {{ $product->pro_hot == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $product->pro_id }}" 
                                        /> 
                                    </td>

                                    <td class="text-center">
                                        <input name="pro_hidden" 
                                                    class="form-check-input"  
                                                    type="checkbox" value="1" id="hidden-{{ $product->pro_id }}" 
                                                    {{ $product->pro_hidden == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $product->pro_id }}" 
                                        /> 
                                    </td>

                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="btn btn-success btn-sm m-1" title="Xem" 
                                                    data-bs-toggle="modal" data-bs-target="#product-modal-{{$product->pro_id}}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @can('Quản trị Sản phẩm (Kho)')
                                            <a href="{{ route('stock.show', $product->pro_slug) }}" class="btn btn-secondary btn-sm m-1" title="Kho hàng">
                                                <i class="text-white fas fa-warehouse"></i>
                                            </a>
                                            @endcan
                                            <a href="{{ route('image.show', $product->pro_slug) }}" class="btn btn-info btn-sm m-1" title="Hình ảnh">
                                                <i class="text-white fas fa-image"></i>
                                            </a>

                                            <form class="d-inline" action="{{ route('product.destroy', $product->pro_id) }}" method="POST">
                                                @csrf 
                                                @method('DELETE')
                                                <button type='submit' onclick="return confirm('Bạn muốn đưa sản phẩm này vào thùng rác?')"
                                                        class="btn btn-danger btn-sm ms-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $stt++;
                                @endphp

                                @include('backend.pages.product.products.product_edit')
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{ $allProducts->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection