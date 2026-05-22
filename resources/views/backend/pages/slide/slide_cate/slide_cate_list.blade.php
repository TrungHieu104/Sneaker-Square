@extends('backend.index')

@section('title')
    Danh mục Slide
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Slide /</span> <a href="{{route('cate-slide.index')}}" class="tab-sort">Danh mục</a></h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý danh mục Slide</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('cate-slide.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('cate_slide.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
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
                            <th class="text-center" style="width:300px;">
                                <a class="tab-sort" href="?sort-by=cate_slide_name&sort-type={{ $orderBy === 'cate_slide_name' ? $orderType : 'desc' }}">
                                    Tên danh mục
                                </a>
                            </th>
                            <th class="text-center" style="width:100px;">
                                <a class="tab-sort" href="?sort-by=cate_slide_hidden&sort-type={{ $orderBy === 'cate_slide_hidden' ? $orderType : 'desc' }}">
                                    Hiển thị
                                </a>
                            </th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $cateSlide->firstItem();
                        @endphp
                        @if($cateSlide->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($cateSlide as $data)
                            <tr>
                                <td class="text-center">
                                    {{$stt}}
                                </td>
                                <td>
                                    {{$data->cate_slide_name}}
                                </td>
                                <td class="text-center">
                                    <input name="status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $data->cate_slide_id }}" {{ $data->cate_slide_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->cate_slide_id }}" />
                                </td>
                                <td class="text-center">
                                    <div>
                                        <a class="btn btn-primary btn-sm mx-2 p-2"
                                        href="{{route('cate-slide.edit', ['cate_slide' => $data->cate_slide_id])}}" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                        <form class="d-inline" action="{{ route('cate_slide.softDelete', ['id' => $data->cate_slide_id]) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')" class="btn btn-danger btn-sm mx-1 p-2">
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
            {{$cateSlide->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
@endsection
