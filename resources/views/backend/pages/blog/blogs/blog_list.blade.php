@extends('backend.index')

@section('title')
    Danh sách bài viết
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Bài viết /</span> Danh sách</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý bài viết</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('blog.create')}}" class="btn btn-success">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('blog.trashed')}}" class="btn btn-danger">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
            </div>
        </div>
        <div class="card-body">
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
                            <th class="text-center" style="width: 60px;">Hiển thị</th>
                            <th class="text-center" style="width: 60px;">Nổi bật</th>
                            <th class="text-center" style="width: 60px;">Lượt xem</th>
                            <th class="text-center" style="max-width:100px;">Thẻ tag</th>
                            <th class="text-center" style="width: 60px;">Người đăng</th>
                            <th class="text-center" style="width: 60px;">Ngày đăng</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                   
                        @if ($news->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">
                                    Chưa có bài viết nào
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $news->firstItem();
                            @endphp
                            @foreach ($news as $n)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{asset($n->news_img)}}" class="img-list_admin rounded"
                                                alt="">
                                        </div>
                                    </td> 
                                    <td>{{$n->news_title}}</td>
                                    <td class="text-center">
                                        <input name="n-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $n->news_id }}" {{ $n->news_hidden == 1 ? 'checked' : '' }} data-id="{{ $n->news_id }}" />
                                    </td>
                                    <td class="text-center">
                                        <input name="n-hot" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $n->news_id }}" {{ $n->news_hot == 1 ? 'checked' : '' }} data-id="{{ $n->news_id }}" />
                                    </td>
                                    <td>{{$n->views}}</td>
                                    <td>
                                        @foreach ($n->getTags as $tag)
                                            <span class="badge btn-secondary">{{$tag->tag_content}}</span>
                                        @endforeach
                                    </td>
                                    <td>{{$n->news_created_by}}</td>
                                    <td>{{ date('d/m/Y', strtotime($n->post_date)) }}</td>
                                    <td class="text-center">
                                        <div class="row-actions">
                                            <x-admin.row-action action="view" title="Xem" href="{{route('news.detail', $n->news_slug)}}" target="_blank" />
                                            <x-admin.row-action action="edit" title="Chỉnh sửa" href="{{route('blog.edit', ['blog' => $n->news_id])}}" />
                                            <form action="{{route('blog.destroy', ['blog' => $n->news_id])}}" method="POST">
                                                @csrf @method('delete')
                                                <x-admin.row-action action="delete" title="Xóa" onclick="return confirm('Bạn muốn xóa bài viết này?')" type="submit" />
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
            </div>
            {{$news->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
