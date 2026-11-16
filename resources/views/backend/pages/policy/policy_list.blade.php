@extends('backend.index')

@section('title')
    Chính sách
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> <a href="{{ route('policy.index') }}"
            class="tab-sort">Chính sách</a></h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý chính sách</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{ route('policy.create') }}" class="btn btn-success mt-1">Thêm mới &nbsp;<i
                        class="bi bi-plus-circle"></i></a>
                <a href="{{ route('policy.trashed') }}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i
                        class='bx bxs-trash'></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">
                                <a class="tab-sort"
                                    href="?sort-by=page_name&sort-type={{ $orderBy === 'page_name' ? $orderType : 'desc' }}">
                                    Thông tin
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort"
                                    href="?sort-by=page_hidden&sort-type={{ $orderBy === 'page_hidden' ? $orderType : 'desc' }}">
                                    Hiển thị
                                </a>
                            </th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $policies->firstItem();
                        @endphp
                        @if ($policies->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach ($policies as $data)
                                <tr>
                                    <td class="text-center">
                                        {{ $stt }}
                                    </td>
                                    <td>
                                        - Tên: <b>{{ $data->page_name }} </b><br>
                                        - Đường dẫn: {{ $data->page_slug }} <br>
                                        - Hiển thị:
                                        <span id="status_{{ $data->id }}"
                                            style="color: {{ $data->page_hidden == 0 ? 'red' : 'green' }}">
                                            {{ $data->page_hidden == 0 ? 'Ẩn' : 'Hiện' }}
                                        </span> <br>
                                        - Tác giả: {{ $data->page_created_by }}<br>
                                        - Chỉnh sửa bởi: {{ $data->page_updated_by }}<br>
                                    </td>
                                    <td class="text-center">
                                        <input name="policy-status" class="form-check-input" type="checkbox" value="1"
                                            id="checkbox_{{ $data->page_id }}"
                                            {{ $data->page_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->page_id }}" />
                                    </td>

                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="p-2 btn btn-success btn-sm m-1" title="Xem"
                                                data-bs-toggle="modal" data-bs-target="#pageModal{{ $data->page_id }}"
                                                data-page-id="{{ $data->page_id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a class="btn btn-primary btn-sm m-1 p-2"
                                                href="{{ route('policy.edit', ['encryptedPageId' => encrypt($data->page_id)]) }}"
                                                title="Chỉnh sửa"><i class="fas fa-edit"></i>
                                            </a>
                                            <form class="d-inline" action="{{ route('policy.softDelete', ['encryptedPageId' => encrypt($data->page_id)]) }}"
                                                method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')"
                                                        class="btn btn-danger btn-sm m-1 p-2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!--Modal-->
                                <div class="modal fade" id="pageModal{{ $data->page_id }}" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <input type="hidden" value="{{ $data->page_id }}">
                                                <h1 class="modal-title fs-5 text-center" id="exampleModalLabel">
                                                    #{{ $stt }}</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body view_page_data">
                                                <h3 class="text-center">{{ $data->page_name }}</h3>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id3" class="form-label">Tác giả</label>
                                                        <input type="text" id="id3" value="{{ $data->page_created_by}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id1" class="form-label">Slug</label>
                                                        <input type="text" id="id1" value="{{$data->page_slug}}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id2" class="form-label">Hiển thị</label>
                                                        <input type="text" id="id2" value="{{ $data->page_hidden == 0 ? 'Ẩn' : 'Hiện' }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id4" class="form-label">Chỉnh sửa bởi</label>
                                                        <input type="text" id="id4" value="{{ $data->page_updated_by}}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id8" class="form-label">Mô tả</label>
                                                    <textarea name="" class="form-control" id="id8" cols="" rows="4" readonly>{!! strip_tags($data->page_description) !!}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id7" class="form-label">Nội dung</label>
                                                    <textarea name="" class="form-control" id="id7" cols="" rows="10" readonly>{!! strip_tags($data->page_content) !!}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id5" class="form-label">SEO Keywords</label>
                                                    <input type="text" id="id5" value="{{ $data->page_meta_keywords}}" class="form-control" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id6" class="form-label">SEO Description</label>
                                                    <textarea name="" class="form-control" id="id6" cols="" rows="3" readonly>{{ $data->page_meta_description}}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Đóng</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $stt++;
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{ $policies->onEachSide(1)->links('backend.layouts.partials.pagination') }}
        </div>
    </div>
@endsection