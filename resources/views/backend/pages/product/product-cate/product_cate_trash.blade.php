@extends('backend.index')

@section('title')
    Danh mục sản phẩm | Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Danh mục /</span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý danh mục</h5>
            <div class="px-4">
                <a href="{{ route('product-category.index') }}" title="Danh mục sản phẩm" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            @if($cateTrash->isNotEmpty())
                <div class="mb-3 d-flex gap-3">
                    <a href="{{ route('product-category.restore.all') }}" title="Khôi phục" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-trash-restore"></i></a>
                    <a href="{{ route('product-category.delete.all') }}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả danh mục vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=cate_name&sort-type={{ $orderBy === 'cate_name' ? $orderType : 'desc' }}">
                                    Tên danh mục
                                </a>
                            </th>
                            <th class="text-center">Đường dẫn</th>
                            <th class="text-center">Từ khóa</th>
                            <th class="text-center">Tên danh mục gốc</th>
                            <th class="text-center">Số sản phẩm</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($cateTrash->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">
                                    Không có danh mục trong thùng rác.
                                </td>
                            </tr>
                        @else
                            @foreach($cateTrash as $cates)
                                <tr>
                                    <td class="text-center dx-num">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($cates->cate_img) }}" class="img-list_admin rounded"
                                                alt="{{ $cates->cate_name }}">
                                        </div>
                                    </td>

                                    <td class="text-center">

                                        {{ $cates->cate_name }}
                                    </td>
    
                                    <td class="text-center">
                                        {{ $cates->cate_slug }}
                                    </td>
    
                                    <td class="text-center">
                                        {{ $cates->cate_meta_keywords ? $cates->cate_meta_keywords:'Chưa có từ khóa' }}
                                    </td>
    
                                    <td class="text-center">
                                        @if ($cates->cate_parent_id)
                                            @if (is_null($cates->getParentCate))
                                                {{ 'Danh mục gốc đã bị xóa' }}
                                            @else 
                                                {{ $cates->getParentCate->cate_name }}
                                            @endif
                                        @elseif (count($cates->getChildCate))
                                            {{ 'Danh mục gốc' }}
                                        @else
                                            {{ 'Không có danh mục gốc' }}
                                        @endif
                                    </td>
    
                                    <td class="text-center">
                                        {{ count($cates->getProductsInCate) }}
                                    </td>

                                    <td class="text-center dx-res">
                                        <div>
                                            <a class="btn btn-success btn-sm mx-1" title="Khôi phục"
                                                href="{{route('product-category.restore', $cates->cate_id)}}">
                                                <i class="fas fa-trash-restore"></i>
                                            </a>

                                            <a href="{{route('product-category.delete', $cates->cate_id)}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa danh mục này vĩnh viễn?')"
                                                class="btn btn-danger btn-sm ms-1"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>                                       
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{$cateTrash->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
