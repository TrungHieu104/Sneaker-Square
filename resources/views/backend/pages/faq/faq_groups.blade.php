@extends('backend.index')

@section('title')
    Nhóm câu hỏi
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3">
        <span class="text-muted fw-light"><a href="{{ route('faq.index') }}" class="tab-back">Câu hỏi thường gặp / </a></span> Nhóm câu hỏi
    </h4>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-border-top">
                <h5 class="card-header">{{ $editing ? 'Sửa nhóm' : 'Thêm nhóm' }}</h5>
                <div class="card-body">
                    <form action="{{ $editing ? route('faq_group.update', $editing->group_id) : route('faq_group.store') }}" method="post">
                        @csrf
                        @if ($editing)
                            @method('PUT')
                        @endif
                        <div class="mb-3">
                            <label for="group-name" class="form-label">Tên nhóm</label>
                            <input type="text" class="form-control" id="group-name" name="name" maxlength="100"
                                value="{{ old('name', $editing?->group_name) }}" placeholder="Ví dụ: Bảo hành" />
                            @error('name')
                                <small class="text-danger fst-italic">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <span class="form-label d-block">Hiển thị</span>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" name="status" type="radio" value="1" id="group-shown" @checked((string) old('status', $editing->group_hidden ?? 1) === '1') />
                                    <label class="form-check-label" for="group-shown">Hiện</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" name="status" type="radio" value="0" id="group-hidden" @checked((string) old('status', $editing->group_hidden ?? 1) === '0') />
                                    <label class="form-check-label" for="group-hidden">Ẩn</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4">Lưu</button>
                            @if ($editing)
                                <a href="{{ route('faq_group.index') }}" class="btn btn-outline-secondary">Huỷ</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card card-border-top">
                <h5 class="card-header">Danh sách nhóm</h5>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 44px;"><span class="visually-hidden">Kéo</span></th>
                                    <th class="text-center">STT</th>
                                    <th>Tên nhóm</th>
                                    <th class="text-center">Số câu hỏi</th>
                                    <th class="text-center">Hiển thị</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody data-sortable-url="{{ route('faq_group.reorder') }}">
                                @forelse ($groups as $group)
                                    <tr data-id="{{ $group->group_id }}" @class(['table-active' => $editing?->group_id === $group->group_id])>
                                        <td class="text-center"><span class="drag-handle" title="Kéo để đổi chỗ"><i class="bx bx-grid-vertical"></i></span></td>
                                        <td class="text-center" data-row-number>{{ $loop->iteration }}</td>
                                        <td><b>{{ $group->group_name }}</b></td>
                                        <td class="text-center">
                                            <a href="{{ route('faq.index', ['group' => $group->group_id]) }}">{{ $group->faqs_count }}</a>
                                        </td>
                                        <td class="text-center">{{ $group->group_hidden ? 'Hiện' : 'Ẩn' }}</td>
                                        <td class="text-center">
                                            <div class="row-actions">
                                                <x-admin.row-action action="edit" title="Chỉnh sửa" href="{{ route('faq_group.edit', $group->group_id) }}" />
                                                {{-- A disabled button shows no tooltip, so the form keeps a box and carries it. --}}
                                                <form class="d-inline-flex" action="{{ route('faq_group.destroy', $group->group_id) }}" method="post" data-confirm="Xoá nhóm này?"
                                                    @if ($group->faqs_count > 0) title="Nhóm còn câu hỏi, hãy chuyển hoặc xoá các câu hỏi trước" @endif>
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-admin.row-action action="delete" title="Xóa" type="submit" :disabled="$group->faqs_count > 0" />
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Chưa có nhóm nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@include('backend.layouts.partials.sortable-rows')
