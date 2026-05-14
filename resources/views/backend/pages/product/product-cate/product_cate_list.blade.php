@extends('backend.index')

@section('title')
    Danh mục sản phẩm
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Danh mục /</span> Danh sách</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý danh mục</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{ route('product-category.create') }}" class="btn btn-success" title="Thêm mới">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{ route('product-category.trashed') }}" class="btn btn-danger" title="Thùng rác">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a> 
            </div>
        </div>
        <div class="card-body">
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
                            <th class="text-center" style="width: 60px;">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($allCates->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có danh mục để hiển thị.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $allCates->firstItem();
                            @endphp
                            @foreach ($allCates as $cates)
                                <tr>
                                    <td class="text-center">
                                        {{ $stt }}
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

                                    <td class="text-center">
                                        @if ($cates->cate_parent_id != null)
                                            <input name="cate_hidden" 
                                                    class="form-check-input cate-parent-id-{{ $cates->getParentCate->cate_id }}" 
                                                    type="checkbox" value="1" 
                                                    {{ $cates->cate_hidden == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $cates->cate_id }}" 
                                            />
                                        @else
                                            <input name="cate_hidden" 
                                                    class="form-check-input"  
                                                    type="checkbox" value="1" id="hidden-{{ $cates->cate_id }}" 
                                                    {{ $cates->cate_hidden == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $cates->cate_id }}" 
                                            /> 
                                        @endif
                                    </td>
                                    
                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="btn btn-success btn-sm m-1" title="Xem"
                                                    data-bs-toggle="modal" data-bs-target="#cate-modal-{{$cates->cate_id}}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form class="d-inline" action="{{ route('product-category.destroy', $cates->cate_id) }}" method="POST">
                                                @csrf 
                                                @method('DELETE')
                                                <button type='submit' onclick="return confirm('Bạn muốn đưa danh mục này vào thùng rác?')"
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

                                @include('backend.pages.product.product-cate.product_cate_edit')
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{ $allCates->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection