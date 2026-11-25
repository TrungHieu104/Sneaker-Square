@extends('backend.index')

@section('title')
    Đánh giá | Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm / Đánh giá /</span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý đánh giá</h5>
            <div class="px-4">
                <a href="{{ route('comment.index') }}" class="btn mt-1 btn-warning" title="Đánh giá"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            @if($commentTrash->isNotEmpty())
                <div class="d-flex mb-3 gap-3">
                    <form action="{{ route('comment.restore.all') }}" method="post"> @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success mt-1 mb-2" title="Khôi phục">Khôi phục tất cả &nbsp;<i class="fas fa-trash-restore"></i></button>
                    </form>
                    <form action="{{ route('comment.delete.all') }}" method="post" data-confirm="Bạn có chắc muốn xóa tất cả đánh giá vĩnh viễn?"> @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger mt-1 mb-2" title="Xóa">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></button>
                    </form>
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
                                    Không có đánh giá trong thùng rác.
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
                                        <div class="row-actions">
                                            @if($comments->getProducts) 
                                                <form action="{{route('comment.restore', $comments->comment_id)}}" method="post"> @csrf @method('PATCH')
                                                    <x-admin.row-action action="restore" title="Khôi phục" type="submit" />
                                                </form>
                                            @endif

                                            <form action="{{route('comment.delete', $comments->comment_id)}}" method="post" data-confirm="Bạn có chắc muốn xóa đánh giá này vĩnh viễn?"> @csrf @method('DELETE')
                                                <x-admin.row-action action="delete" title="Xóa vĩnh viễn" type="submit" />
                                            </form>
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