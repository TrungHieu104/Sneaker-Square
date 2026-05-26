@extends('backend.index')

@section('title')
    Liên hệ
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('info-contact.index')}}" class="tab-back">Thông tin /</a></span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thông tin</h5>
            <div class="px-4">
                <a href="{{route('info-contact.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('info_contact.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{route('info_contact.delete.all')}}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả thông tin vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">Thông tin</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = 1;
                        @endphp
                        @if($contactTrash->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($contactTrash as $data)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td>
                                        - Địa chỉ: {{$data->contact_address}} <br>
                                        - Số điện thoại: {{$data->contact_phone}} <br>
                                        - Email: {{$data->contact_email}}<br>
                                        - Hiển thị: @if($data->contact_hidden==0)
                                            <span style="color: red">Ẩn</span>
                                        @else
                                            <span style="color: green">Hiện</span>
                                        @endif<br>
                                    </td>
                                
                                    <td class="text-center">
                                        <div>
                                            <a class="btn btn-success btn-sm mx-1 p-2"
                                                href="{{route('info_contact.restore',['id'=>$data->contact_id])}}" title="Sao lưu">
                                                <i class="fas fa-trash-restore"></i>
                                            </a>
                                            <a href="{{route('info_contact.delete',['id'=>$data->contact_id])}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa vĩnh viễn?')"
                                                class="btn btn-danger btn-sm ms-1 p-2"
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
                    {{$contactTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
                </table>
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
