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
                <form action="{{route('menu.restoreAll')}}" method="post"> @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></button>
                </form>
                <form action="{{route('menus.delete.all')}}" method="post" data-confirm="Bạn có chắc muốn xóa tất cả menu vĩnh viễn?"> @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger mt-1 mb-2" title="Xóa">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></button>
                </form>
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
                                    <div class="row-actions">
                                        <form action="{{route('menu.restore', $data->menu_id)}}" method="post"> @csrf @method('PATCH')
                                            <x-admin.row-action action="restore" title="Khôi phục" type="submit" />
                                        </form>
                                        <form action="{{route('menu.delete', $data->menu_id)}}" method="post" data-confirm="Bạn có chắc muốn xóa menu này vĩnh viễn?"> @csrf @method('DELETE')
                                            <x-admin.row-action action="delete" title="Xóa vĩnh viễn" type="submit" />
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
                {{$menuTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
                

            </div>
        </div>
    </div>
@endsection
