@extends('backend.index')

@section('title')
    Tài khoản quản trị
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Tài khoản /</span> Danh sách</h4>
    <!-- Bordered Table -->
    <div class="card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý tài khoản Quản trị viên</h5>
            <div class="px-4">
                <a href="{{route('account.create')}}" class="btn btn-success">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" >Username</th>
                            <th class="text-center" >Họ tên</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Vai trò</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        @php
                            $stt = $accountTrash->firstItem();
                        @endphp
                        @if($accountTrash->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($accountTrash as $data)
                            <tr>
                                <td class="text-center">
                                    {{$stt}}
                                </td>
                                <td>
                                    {{$data->username}}
                                </td>
                                <td>
                                    {{$data->name}}
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
                                    <div>
                                        
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
                {{$accountTrash->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div> 
    <!--/ Bordered Table -->
@endsection
