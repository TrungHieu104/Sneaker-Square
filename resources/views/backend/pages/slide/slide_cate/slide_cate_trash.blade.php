@extends('backend.index')

@section('title')
    Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('cate-slide.index')}}" class='tab-back'>Danh mục /</a></span> Thùng rác</h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thùng rác</h5>
            <div class="px-4">
                <a href="{{route('cate-slide.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
            
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <form action="{{route('cate_slide.restoreAll')}}" method="post"> @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></button>
                </form>
                <form action="{{route('cate_slide.delete.all')}}" method="post" data-confirm="Bạn có chắc muốn xóa tất cả danh mục slide vĩnh viễn?"> @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger mt-1 mb-2" title="Xóa">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></button>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">Tên danh mục</th>
                            <th class="text-center">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = 1;
                            @endphp
                        @if($cateSlides->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($cateSlides as $data)
                            <tr>
                                <td class="text-center">
                                    {{$stt}}
                                </td>
                                <td class="px-3 text-center">
                                    {{$data->cate_slide_name}}
                                </td>
                                <td class="text-center">
                                    @if($data->cate_slide_hidden==0)
                                        <span style="color: red">Ẩn</span>
                                    @else
                                        <span style="color: green">Hiện</span>
                                    @endif
                                </td>
                                
                                <td class="text-center">
                                    <div class="row-actions">
                                        <form action="{{route('cate_slide.restore',['id'=>$data->cate_slide_id])}}" method="post"> @csrf @method('PATCH')
                                            <x-admin.row-action action="restore" title="Khôi phục" type="submit" />
                                        </form>
                                        <form action="{{route('cate_slide.delete',['id'=>$data->cate_slide_id])}}" method="post" data-confirm="Bạn có chắc muốn xóa danh mục slide này vĩnh viễn?"> @csrf @method('DELETE')
                                            <x-admin.row-action action="delete" title="Xóa vĩnh viễn" type="submit" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @php
                                $stt++; 
                                @endphp
                            @endforeach
                            {{$cateSlides->onEachSide(1)->links('backend.layouts.partials.pagination')}}
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
