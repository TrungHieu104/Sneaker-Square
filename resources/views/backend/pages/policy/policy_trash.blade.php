@extends('backend.index')

@section('title')
    Chính sách
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('policy.index')}}" class="tab-back">Chính sách /</a></span> Thùng rác</h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý chính sách</h5>
            <div class="px-4">
                <a href="{{route('policy.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('policy.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{route('policy.delete.all')}}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả chính sách vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center" style="max-width: 200px;">Tên</th>
                            <th class="text-center">Hiển thị</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $stt = 1;
                        @endphp
                            @if($trashed->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($trashed as $data)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3">
                                        {{$data->page_name}} 
                                    </td>
                                    <td class="text-center">                                       
                                        {{($data->page_hidden == 1) ? "Hiện" : "Ẩn"}}
                                    </td>
                                    
                                    <td class="text-center dx-res">
                                        <div>
                                            <a class="p-2 btn btn-success btn-sm m-1"
                                                href="{{route('policy.restore',['encryptedPageId'=>encrypt($data->page_id)])}}" title="Sao lưu">
                                                <i class="fas fa-trash-restore"></i>
                                            </a>
                                            <a href="{{route('policy.delete',['encryptedPageId'=>encrypt($data->page_id)])}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')"
                                                class="p-2 btn btn-danger btn-sm m-1"
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
                {{$trashed->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    
@endsection