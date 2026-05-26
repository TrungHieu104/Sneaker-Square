@extends('backend.index')

@section('title')
    Menu
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('menus.index')}}" class='tab-back'>Menu /</a></span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thanh menu</h5>
            <div class="px-4">
            </div>
            <div class="px-4">
                <a href="{{route('menus.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('menu.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{route('menus.delete.all')}}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả menu vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">Tên</th>
                            <th class="text-center">URL</th>
                            <th class="text-center">Vị trí</th>
                            <th class="text-center">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = 1;
                        @endphp
                        @if($menuTrash->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($menuTrash as $data)
                            <tr>
                                <td class="text-center">
                                    {{$stt}}
                                </td>
                                <td>
                                    {{$data->menu_name}}
                                </td>
                                <td class="px-3">
                                {{$data->menu_link}}
                                </td>
                                <td class="text-center">{{$data->menu_position}}</td>
                                <td class="text-center">
                                    {{$data->menu_hidden == 1 ? "Kích hoạt" : "Vô hiệu"}}
                                </td>
                                
                                <td class="text-center">
                                    <div>
                                        <a class="btn btn-success btn-sm mx-1"
                                            href="/admin/menus/restore/{{$data->menu_id}}" title="Sao lưu">
                                            <i class="fas fa-trash-restore"></i>
                                        </a>
                                        <a href="/admin/menus/delete/{{$data->menu_id}}"
                                            onclick="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')"
                                            class="btn btn-danger btn-sm ms-1"
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                {{$menuTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
                

            </div>
        </div>
    </div>
@endsection
