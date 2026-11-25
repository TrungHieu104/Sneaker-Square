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
                <form action="{{route('page.restoreAll')}}" method="post"> @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></button>
                </form>
                <form action="{{route('page.delete.all')}}" method="post" data-confirm="Bạn có chắc muốn xóa tất cả chính sách vĩnh viễn?"> @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger mt-1 mb-2" title="Xóa">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></button>
                </form>
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
                                            <form action="{{route('page.restore',['encryptedPageId'=>encrypt($data->page_id)])}}" method="post"> @csrf @method('PATCH')
                                                <x-admin.row-action action="restore" title="Khôi phục" type="submit" />
                                            </form>
                                            <form action="{{route('page.delete',['encryptedPageId'=>encrypt($data->page_id)])}}" method="post" data-confirm="Bạn có chắc muốn xóa chính sách này vĩnh viễn?"> @csrf @method('DELETE')
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
                {{$trashed->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    
@endsection