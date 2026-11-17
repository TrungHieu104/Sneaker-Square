@extends('backend.index')

@section('title')
    Chính sách
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('page.index')}}" class="tab-back">Chính sách &amp; Giới thiệu /</a></span> Thùng rác</h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý chính sách</h5>
            <div class="px-4">
                <a href="{{route('page.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('page.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{route('page.delete.all')}}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả chính sách vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
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
                                        <div class="row-actions">
                                            <x-admin.row-action action="restore" title="Khôi phục" href="{{route('page.restore',['encryptedPageId'=>encrypt($data->page_id)])}}" />
                                            <x-admin.row-action action="delete" title="Xóa vĩnh viễn" href="{{route('page.delete',['encryptedPageId'=>encrypt($data->page_id)])}}" onclick="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')" />
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