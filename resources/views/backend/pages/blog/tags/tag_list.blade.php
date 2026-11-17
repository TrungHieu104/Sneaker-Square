@extends('backend.index')

@section('title')
    Tag bài viết
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Tag /</span> Danh sách</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thẻ tag</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('tags.create')}}" class="btn btn-success">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('tag.trashed')}}" class="btn btn-danger">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 150px;">
                                <a class="tab-sort" href="?sort-by=tag_content&sort-type={{ $orderBy === 'tag_content' ? $orderType : 'desc' }}">
                                    Nội dung thẻ
                                </a>
                            </th>
                            <th class="text-center" style="width: 100px;">Slug thẻ</th>
                            <th class="text-center" style="width: 60px;">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($tags->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">
                                    Chưa có thẻ tag nào
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $tags->firstItem();
                            @endphp
                            @foreach ($tags as $t)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="text-center">{{$t->tag_content}}</td>
                                    <td class="text-center">{{$t->tag_slug}}</td>
                                    <td class="text-center">
                                        <input name="tag-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $t->tag_id }}" {{ $t->tag_hidden == 1 ? 'checked' : '' }} data-id="{{ $t->tag_id }}" />
                                    </td>
                                    <td class="text-center">
                                        <div class="row-actions">
                                            <x-admin.row-action action="edit" title="Chỉnh sửa" href="{{route('tags.edit', ['tag' => $t->tag_id])}}" />
                                            <form action="{{route('tags.destroy', ['tag' => $t->tag_id])}}" method="POST">
                                                @csrf @method('delete')
                                                <x-admin.row-action action="delete" title="Xóa" onclick="return confirm('Bạn muốn xóa tag này?')" type="submit" />
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
            {{$tags->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
