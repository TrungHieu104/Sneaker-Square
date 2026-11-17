@extends('backend.index')

@section('title')
    Chính sách & Giới thiệu
@endsection

@section('content')
    @php
        $sortLink = fn (string $column) => request()->fullUrlWithQuery(['sort-by' => $column, 'sort-type' => $orderBy === $column ? $orderType : 'desc']);
        $tabs = ['' => 'Tất cả', 'policy' => 'Chính sách', 'about' => 'Giới thiệu'];
    @endphp
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> <a href="{{ route('page.index') }}"
            class="tab-sort">Chính sách &amp; Giới thiệu</a></h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Trang chính sách và giới thiệu</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{ route('page.create') }}" class="btn btn-success mt-1">Thêm chính sách &nbsp;<i
                        class="bi bi-plus-circle"></i></a>
                <a href="{{ route('page.trashed') }}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i
                        class='bx bxs-trash'></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="btn-group" role="group" aria-label="Lọc theo loại trang">
                    @foreach ($tabs as $value => $label)
                        <a href="{{ route('page.index', array_filter(['type' => $value, 'keyword' => $keyword])) }}"
                            @class(['btn', 'btn-primary' => ($type?->value ?? '') === $value, 'btn-outline-primary' => ($type?->value ?? '') !== $value])>
                            {{ $label }}
                            <small class="ms-1 opacity-75">{{ $value === '' ? $counts->sum() : ($counts[$value] ?? 0) }}</small>
                        </a>
                    @endforeach
                </div>
                <form method="GET" data-list-filter action="{{ route('page.index') }}" class="d-flex gap-2">
                    @if ($type)
                        <input type="hidden" name="type" value="{{ $type->value }}">
                    @endif
                    <input type="search" name="keyword" class="form-control" style="width: 280px;" placeholder="Tìm theo tên trang…"
                        value="{{ $keyword }}" aria-label="Tìm theo tên trang">
                    <button class="btn btn-primary">Lọc</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th><a class="tab-sort" href="{{ $sortLink('page_name') }}">Trang</a></th>
                            <th class="text-center">Loại</th>
                            <th>Cập nhật</th>
                            <th class="text-center"><a class="tab-sort" href="{{ $sortLink('page_hidden') }}">Hiển thị</a></th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $pages->firstItem();
                        @endphp
                        @if ($pages->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach ($pages as $data)
                                @php
                                    $isAbout = $data->page_type === \App\Enums\PageType::About;
                                    $path = $isAbout ? route('about.page', [], false) : ($data->page_slug ? route('policy.detail', $data->page_slug, false) : '—');
                                @endphp
                                <tr @class(['table-light' => $isAbout])>
                                    <td class="text-center">{{ $stt }}</td>
                                    <td>
                                        <b>{{ $data->page_name }}</b>
                                        <small class="d-block text-muted font-monospace">{{ $path }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span @class(['badge', 'bg-label-warning' => $isAbout, 'bg-label-primary' => ! $isAbout])>{{ $isAbout ? 'Giới thiệu' : 'Chính sách' }}</span>
                                    </td>
                                    <td>
                                        {{ $data->page_updated_by ?: $data->page_created_by }}
                                        <small class="d-block text-muted">tạo bởi {{ $data->page_created_by }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if ($isAbout)
                                            <input class="form-check-input" type="checkbox" checked disabled title="Trang giới thiệu luôn hiện" aria-label="Trang giới thiệu luôn hiện" />
                                        @else
                                            <input name="page-status" class="form-check-input" type="checkbox" value="1"
                                                id="checkbox_{{ $data->page_id }}" aria-label="Hiển thị chính sách"
                                                {{ $data->page_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->page_id }}" />
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="row-actions">
                                            <x-admin.row-action action="view" title="Xem" data-bs-toggle="modal" data-bs-target="#pageModal{{ $data->page_id }}" />
                                            <x-admin.row-action action="edit" title="Chỉnh sửa" href="{{ route('page.edit', ['encryptedPageId' => encrypt($data->page_id)]) }}" />
                                            @if ($isAbout)
                                                {{-- A disabled button shows no tooltip, so the span carries it. --}}
                                                <span class="d-inline-flex" title="Trang giới thiệu không xoá được">
                                                    <x-admin.row-action action="delete" title="Xóa" disabled />
                                                </span>
                                            @else
                                                <form action="{{ route('page.softDelete', ['encryptedPageId' => encrypt($data->page_id)]) }}" method="post">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-admin.row-action action="delete" title="Xóa" type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')" />
                                                </form>
                                            @endif
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
                                            <div class="modal-body">
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
            {{ $pages->onEachSide(1)->links('backend.layouts.partials.pagination') }}
        </div>
    </div>
@endsection