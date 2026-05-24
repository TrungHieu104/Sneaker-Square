@extends('backend.index')

@section('title')
    Bình luận | Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm / Bình luận /</span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý bình luận</h5>
            <div class="px-4">
                <a href="{{ route('comment.index') }}" class="btn mt-1 btn-warning" title="Bình luận"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            @if($commentTrash->isNotEmpty())
                <div class="d-flex mb-3 gap-3">
                    <a href="{{ route('comment.restore.all') }}" class="btn btn-success mt-1 mb-2" title="Khôi phục">Khôi phục tất cả &nbsp;<i class="fas fa-trash-restore"></i></a>
                    <a href="{{ route('comment.delete.all') }}" class="btn btn-danger mt-1 mb-2" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa tất cả bình luận vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center align-middle">ID</th>
                            <th class="text-center align-middle" style="min-width: 350px;">Nội dung</th>
                            <th class="text-center align-middle" style="min-width: 120px;">Thời gian</th>
                            <th class="text-center align-middle" style="min-width: 130px;">Sản phẩm</th>
                            <th class="text-center align-middle" style="min-width: 100px;">Họ tên</th>
                            <th class="text-center align-middle">Email</th>
                            <th class="text-center align-middle" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($commentTrash->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có bình luận trong thùng rác.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $commentTrash->firstItem();
                            @endphp
                            @foreach($commentTrash as $comments)
                                <tr>
                                    <td class="text-center dx-num">
                                        {{ $stt }}
                                    </td>

                                    <td class="px-3 text-wrap">
                                        {{ $comments->comment_content }}
                                    </td>

                                    <td class="text-center">
                                        {{ date('d-m-Y', strtotime($comments->comment_date)) }} {{ date('H:i', strtotime($comments->comment_date)) }}
                                    </td>

                                    <td class="text-center">
                                        {{ $comments->getProducts ? $comments->getProducts->pro_name:'Sản phẩm đã bị xóa' }}
                                    </td>
    
                                    <td class="text-center">
                                        @if ($comments->user_id != NULL)
                                            {{ $comments->getUsers->name }}
                                        @elseif ($comments->comment_name != NULL)
                                            {{ $comments->comment_name }}
                                        @else
                                            {{ 'Không có họ tên' }}
                                        @endif
                                    </td>
    
                                    <td class="text-center">
                                        @if ($comments->user_id != NULL)
                                            {{ $comments->getUsers->email }}
                                        @elseif ($comments->comment_email != NULL)
                                            {{ $comments->comment_email }}
                                        @else
                                            {{ 'Không có email' }}
                                        @endif
                                    </td>

                                    <td class="text-center dx-res">
                                        <div>
                                            @if($comments->getProducts) 
                                                <a class="btn btn-success btn-sm mx-1" title="Khôi phục"
                                                    href="{{route('comment.restore', $comments->comment_id)}}">
                                                    <i class="fas fa-trash-restore"></i>
                                                </a>
                                            @endif

                                            <a href="{{route('comment.delete', $comments->comment_id)}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa bình luận này vĩnh viễn?')"
                                                class="btn btn-danger btn-sm ms-1"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>                                     
                                        </div>
                                    </td>
                                </tr>

                                @php
                                    $stt++
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{ $commentTrash->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection