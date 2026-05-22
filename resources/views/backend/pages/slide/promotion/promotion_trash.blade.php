@extends('backend.index')

@section('title')
    Danh mục Slide
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('promotion.index')}}" class='tab-back'>Slide /</a></span> Danh mục</h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý danh mục Slide</h5>
            <div class="px-4">
                <a href="{{route('promotion.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
            
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('promotion.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{route('promotion.delete.all')}}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả hình ảnh vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">Hình ảnh</th>
                            <th class="pro-name">Tiêu đề</th>
                            <th class="text-center">Thứ tự</th>
                            <th class="text-center">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($promotionTrash->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($promotionTrash as $data)
                            <tr>
                                <td class="text-center">{{$loop->iteration}}</td>
                                <td class="text-center">
                                    <div class="img-container_admin mx-auto border p-1 rounded">
                                        <img onerror="this.src='/uploads/img_error.jpg'" src="{{asset($data->promotion_img)}}" class="img-list_admin rounded" alt="">
                                    </div>
                                </td>
                                <td class="px-3 pro-name">
                                    {{$data->promotion_name}}
                                </td>
                                <td class="px-3 text-center">
                                    {{$data->promotion_sort}}
                                </td>
                                <td class="text-center">
                                    @if($data->promotion_hidden==0)
                                        <span style="color: red">Ẩn</span>
                                    @else
                                        <span style="color: green">Hiện</span>
                                    @endif
                                </td>
                                
                                <td class="text-center">
                                    <div>
                                        <form class="d-inline" action="{{ route('promotion.restore', ['id' => $data->promotion_id]) }}" method="get">
                                            @csrf
                                            {{-- @method('PATCH') --}}
                                            <button type="submit" class="p-2 btn btn-success btn-sm m-1" title="Sao lưu">
                                                <i class="fas fa-trash-restore"></i>
                                            </button>
                                        </form>
                                        <form class="d-inline" action="{{route('promotion.delete',['id'=>$data->promotion_id])}}" method="get" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')">
                                            @csrf
                                            {{-- @method('DELETE') --}}
                                            <button type="submit" class="p-2 btn btn-danger btn-sm m-1" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                {{$promotionTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
@endsection
