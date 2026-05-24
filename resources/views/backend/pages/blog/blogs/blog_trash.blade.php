@extends('backend.index')

@section('title')
Bài viết
@endsection
@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Bài viết /</span> Danh sách</h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý bài viết</h5>
            <div class="px-4">
                <a href="{{route('blog.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
            
        </div> 
        <div class="card-body">
            @if ($blogTrash->isNotEmpty())
                <div class="d-flex mb-3 gap-3">
                    <a href="{{route('blog.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                    <a href="{{route('blog.deleteAll')}}" class="btn btn-danger mt-1 mb-2">Xóa tất cả</a>
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=news_title&sort-type={{ $orderBy === 'news_title' ? $orderType : 'desc' }}">
                                    Tiêu đề
                                </a>
                            </th>
                            <th class="text-center" style="width: 60px;">Ngày đăng</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($blogTrash->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Thùng rác rỗng.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $blogTrash->firstItem();
                            @endphp
                            @foreach ($blogTrash as $bT)
                                <tr>
                                    <td>
                                        {{$stt}}
                                    </td>
                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{asset($bT->news_img)}}" class="img-list_admin rounded"
                                                alt="">
                                        </div>
                                    </td>
                                    <td>{{$bT->news_title}}</td>
                                    <td>{{ date('d/m/Y', strtotime($bT->post_date)) }}</td>
                                    <td class="text-center">
                                        <div>
                                            <a class="p-2 btn btn-success btn-sm m-1"
                                                href="{{route('blog.restore',['id'=>$bT->news_id])}}" title="Hoàn tác"><i class="fas fa-trash-restore"></i>
                                            </a>
                                            <form class="d-inline" action="{{route('blog.delete',['id'=>$bT->news_id])}}" method="post"> @csrf @method('DELETE')
                                                <button onclick="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')" class="p-2 btn btn-danger btn-sm m-1" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                {{$blogTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
@endsection
