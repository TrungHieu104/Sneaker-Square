@extends('backend.index')

@section('title')
    Tài khoản quản trị
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Tài khoản /</span> <a href="{{route('account.index')}}" class="tab-sort">Quản trị viên</a></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản trị tài khoản</h5>
            <div class="px-4 d-flex">
                <a href="{{route('account.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                {{-- <a href="{{route('account.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>             --}}
                <div class="dropdown mt-2">
                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1">
                        <form action="{{route('exportad.scv')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="submit" name="export_scv" value="Xuất báo cáo" class="dropdown-item">
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" >
                                <a class="tab-sort" href="?sort-by=username&sort-type={{ $orderBy === 'username' ? $orderType : 'desc' }}">
                                    Username
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=email&sort-type={{ $orderBy === 'email' ? $orderType : 'desc' }}">
                                    Email
                                </a>
                            </th>
                            <th class="text-center">
                                {{-- <a class="tab-sort" href="?sort-by=name&sort-type={{ $orderBy === 'name' ? $orderType : 'desc' }}"> --}}
                                    Vai trò
                                {{-- </a> --}}
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=user_status&sort-type={{ $orderBy === 'user_status' ? $orderType : 'desc' }}">
                                    Trạng thái
                                </a>
                            </th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $userAdmin->firstItem();
                        @endphp
                        @if($userAdmin->getCollection()->every(fn ($data) => $data->user_id === Auth::user()->user_id))
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($userAdmin as $data)
                                @if($data->user_id !== Auth::user()->user_id)
                                    <tr>
                                        <td class="text-center">
                                            {{$stt}}
                                        </td>
                                        <td>
                                            {{$data->username}}
                                        </td>
                                        <td class="px-3">
                                            {{$data->email}}
                                        </td>
                                        <td class="text-center">
                                            @foreach($data->roles as $key => $role)
                                                {{$role->name}}
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            @if($data->user_status==0)
                                                <span style="color: red">Vô hiệu</span>
                                            @else
                                                <span style="color: green">Kích hoạt</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="row-actions">
                                                <x-admin.row-action action="edit" title="Chỉnh sửa" href="{{route('account.edits',['encryptedUserId' => encrypt($data->user_id)])}}" />
                                                <x-admin.row-action action="more" title="Phân vai trò" icon="fas fa-user-alt" href="{{route('assign_role',['encryptedUserId' => encrypt($data->user_id)])}}" />
                                                <x-admin.row-action action="more" title="Phân quyền" icon="fas fa-users" href="{{route('assign_permission',['encryptedUserId' => encrypt($data->user_id)])}}" />
                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                        $stt++;
                                    @endphp
                                
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{$userAdmin->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div> 
@endsection
