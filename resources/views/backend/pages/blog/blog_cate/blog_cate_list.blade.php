@extends('backend.index')

@section('title')
    Danh mục bài viết
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Bài viết /</span> <a href="{{route('blog-category.index')}}" class="tab-sort">Danh mục</a></h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý danh mục bài viết</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('blog-category.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('cate_blog.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
            </div>
            
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:100px;">
                                STT
                            </th>
                            <th class="text-center" style="100px">Hình</th>
                            <th class="text-center" style="width:300px;">
                                <a class="tab-sort" href="?sort-by=cate_news_name&sort-type={{ $orderBy === 'cate_news_name' ? $orderType : 'desc' }}">
                                    Tên danh mục
                                </a> 
                            </th>
                            <th class="text-center" style="width:100px;">
                                Thứ tự hiển thị
                            </th>
                            <th class="text-center" style="width:100px;">
                                Trạng thái
                            </th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($cateNews->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $cateNews->firstItem();
                            @endphp
                            @foreach ($cateNews as $cN)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{$cN->cate_news_img}}" class="img-list_admin rounded"
                                                alt="">
                                        </div> 
                                    </td>
                                    <td class="text-center">{{$cN->cate_news_name}}</td>
                                    <td class="text-center">{{$cN->cate_news_sort}}</td>
                                    <td class="text-center">
                                        <input name="cate-new-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $cN->cate_news_id }}" {{ $cN->cate_news_hidden == 1 ? 'checked' : '' }} data-id="{{ $cN->cate_news_id }}" />
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <a class="btn btn-primary btn-sm mx-1" href="{{route('blog-category.edit', ['blog_category' => $cN->cate_news_id])}}" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                            <form class="d-inline" action="{{route('blog-category.destroy', ['blog_category' => $cN->cate_news_id])}}" method="POST">
                                                @csrf @method('delete')
                                                <button onclick="return confirm('Bạn muốn xóa mục này?')" class="btn btn-danger btn-sm mx-1"> 
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $stt++;
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{$cateNews->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
@endsection
