@extends('backend.index')

@section('title')
    Tài khoản khách hàng
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Tài khoản /</span> <a href="{{route('account.user')}}" class="tab-sort">Khách hàng</a></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý tài khoản</h5>
            {{-- <div class="px-4">
                <a href="" class="btn btn-success">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
            </div> --}}
            <div class="dropdown px-4">
                <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1">
                    <form action="{{route('exportus.scv')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="submit" name="export_scv" value="Xuất báo cáo" class="dropdown-item">
                    </form>
                </div>
            </div>
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;">STT</th>
                            <th class="text-center dx-info">
                                <a class="tab-sort" href="?sort-by=name&sort-type={{ $orderBy === 'name' ? $orderType : 'desc' }}">
                                    Họ tên
                                </a>
                            </th>
                            <th class="text-center" style="width: 50px;">
                                <a class="tab-sort" href="?sort-by=email&sort-type={{ $orderBy === 'email' ? $orderType : 'desc' }}">
                                    Email
                                </a>
                            </th>
                            <th class="text-center dx-res" style="width: 60px;">
                                <a class="tab-sort" href="?sort-by=user_role&sort-type={{ $orderBy === 'user_role' ? $orderType : 'desc' }}">
                                    Vai trò
                                </a>
                            </th>
                            <th class="text-center dx-res" style="width: 60px;">
                                <a class="tab-sort" href="?sort-by=user_status&sort-type={{ $orderBy === 'user_status' ? $orderType : 'desc' }}">
                                    Trạng thái
                                </a>
                            </th>
                            <th class="text-center dx-res" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $account->firstItem();
                        @endphp
                            @if($account->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                            @else
                                @foreach($account as $data)
                                    <tr>
                                        <td class="text-center">
                                            {{$stt}}
                                        </td>
                                
                                        <td class="pro-name">
                                            {{$data->name}}
                                        </td>
                                        <td class="px-3">
                                            {{$data->email}}
                                        </td>
                                        <td class="text-center">
                                            @if($data->user_role==0)
                                                <span style="color: green">Khách hàng</span>
                                            @else
                                                <span style="color: red">Quản trị viên</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($data->user_status==0)
                                                <span style="color: red">Vô hiệu</span>
                                            @else
                                                <span style="color: green">Kích hoạt</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div>
                                                <a class="btn btn-primary btn-sm m-1 p-2" href="{{route('account.edituser', ['encryptedUserId' => encrypt($data->user_id)])}}" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                            </div>
                                        </td>
                                        @php
                                            $stt++;
                                        @endphp
                                    </tr>
                                @endforeach
                            @endif
                    </tbody>
                </table>
            </div>
            {{$account->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>

@endsection
