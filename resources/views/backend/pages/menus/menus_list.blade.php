@extends('backend.index')

@section('title')
    Menu
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> <a href="{{route('menus.index')}}" class="tab-sort">Menu</a></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thanh menu</h5>
            <div class="px-4 d-flex gap-3">
                @can('Quản trị Menu')
                <a href="{{route('menus.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('menu.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
                @endcan
            </div>
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=menu_name&sort-type={{ $orderBy === 'menu_name' ? $orderType : 'desc' }}">
                                    Tên
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=menu_link&sort-type={{ $orderBy === 'menu_link' ? $orderType : 'desc' }}">
                                    URL
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=menu_position&sort-type={{ $orderBy === 'menu_position' ? $orderType : 'desc' }}">
                                    Vị trí 
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=menu_hidden&sort-type={{ $orderBy === 'menu_hidden' ? $orderType : 'desc' }}">
                                    Hiển thị
                                </a>
                            </th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $menu->firstItem();
                        @endphp
                        @if($menu->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($menu as $data)
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
                                    <input name="m-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $data->menu_id }}" {{ $data->menu_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->menu_id }}" />
                                </td>
                                
                                <td class="text-center">
                                    <div>
                                        @can('Quản trị Menu')
                                            <a class="btn btn-primary btn-sm mx-1"
                                                href="/admin/menus/{{$data->menu_id}}/edit" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form class="d-inline" action="{{ route('menu.softDelete', ['id' => $data->menu_id]) }}" method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')" class="btn btn-danger btn-sm mx-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
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
            {{$menu->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
@endsection
