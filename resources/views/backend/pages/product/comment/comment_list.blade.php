@extends('backend.index')

@section('title')
    Danh sách bình luận
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Bình luận</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý bình luận</h5>
            <div class="px-4">
                <a href="{{ route('comment.trashed') }}" class="btn btn-danger" title="Thùng rác">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a> 
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center align-middle">ID</th>
                            <th class="text-center align-middle" style="min-width: 350px;">
                                <a class="tab-sort" href="?sort-by=comment_content&sort-type={{ $orderBy === 'comment_content' ? $orderType : 'desc' }}">
                                    Nội dung
                                </a>
                            </th>
                            <th class="text-center align-middle" style="min-width: 120px;">Thời gian</th>
                            <th class="text-center align-middle" style="min-width: 130px;">
                                <a class="tab-sort" href="?sort-by=pro_id&sort-type={{ $orderBy === 'pro_id' ? $orderType : 'desc' }}">
                                    Sản phẩm
                                </a>
                            </th>
                            <th class="text-center align-middle" style="min-width: 100px;">Họ tên</th>
                            <th class="text-center align-middle">Email</th>
                            <th class="text-center align-middle">Hiển thị</th>
                            <th class="text-center align-middle">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($allComments->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có bình luận.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $allComments->firstItem()
                            @endphp
                            @foreach ($allComments as $comments)
                                <tr data-id="{{ $comments->comment_id }}">
                                    <td class="text-center">
                                        {{ $stt }}
                                    </td>

                                    <td class="text-center text-wrap">
                                        {{ $comments->comment_content }}
                                    </td>

                                    <td class="text-center">
                                        {{ date('d-m-Y', strtotime($comments->comment_date)) }} {{ date('H:i', strtotime($comments->comment_date)) }}
                                    </td>

                                    <td class="text-center">
                                        {{ $comments->getProducts? $comments->getProducts->pro_name:'Sản phẩm đã bị xóa' }}
                                    </td>

                                    <td class="text-center">
                                        @if ($comments->user_id != NULL)
                                            {{ $comments->getUsers->name }}
                                        @elseif ($comments->comment_name != NULL)
                                            {{ $comments->comment_name }}
                                        @else
                                            {{ 'Không xác định' }}
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($comments->user_id != NULL)
                                            {{ $comments->getUsers->email }}
                                        @elseif ($comments->comment_email != NULL)
                                            {{ $comments->comment_email }}
                                        @else
                                            {{ 'Không xác định' }}
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <input name="comment_hidden" 
                                                    class="form-check-input"  
                                                    type="checkbox" value="1" id="hidden-{{ $comments->comment_id }}" 
                                                    {{ $comments->comment_hidden == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $comments->comment_id }}" 
                                        /> 
                                    </td>
                                    
                                    <td class="text-center">
                                        <div>
                                            <form class="d-inline" action="{{ route('comment.destroy', $comments->comment_id) }}" method="POST">
                                                @csrf 
                                                @method('DELETE')
                                                <button type='submit' onclick="return confirm('Bạn muốn đưa bình luận này vào thùng rác?')"
                                                        class="btn btn-danger btn-sm ms-1">
                                                <i class="fas fa-trash"></i>
                                                </button>
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
                {{ $allComments->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
