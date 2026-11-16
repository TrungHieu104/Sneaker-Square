@extends('backend.index')

@section('title')
    Câu hỏi thường gặp
@endsection

@section('content')
    @php
        $sortLink = fn (string $column) => request()->fullUrlWithQuery(['sort-by' => $column, 'sort-type' => $orderBy === $column ? $orderType : 'desc']);
    @endphp
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> Câu hỏi thường gặp</h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý câu hỏi thường gặp</h5>
            <div class="px-4">
                <a href="{{ route('faq.create') }}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('faq.index') }}" class="row g-2 mb-3">
                <div class="col-lg-5">
                    <input type="search" name="keyword" class="form-control" placeholder="Tìm theo câu hỏi…" value="{{ request('keyword') }}" aria-label="Tìm theo câu hỏi">
                </div>
                <div class="col-lg-4">
                    <select name="group" class="form-select" aria-label="Lọc theo nhóm">
                        <option value="">Tất cả nhóm</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->group_id }}" @selected($groupId === $group->group_id)>{{ $group->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-auto">
                    <button class="btn btn-primary">Lọc</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th><a class="tab-sort" href="{{ $sortLink('faq_question') }}">Câu hỏi</a></th>
                            <th class="text-center">Nhóm</th>
                            <th class="text-center"><a class="tab-sort" href="{{ $sortLink('faq_position') }}">Thứ tự</a></th>
                            <th class="text-center"><a class="tab-sort" href="{{ $sortLink('faq_hidden') }}">Hiển thị</a></th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($faqs as $faq)
                            <tr>
                                <td class="text-center">{{ $faqs->firstItem() + $loop->index }}</td>
                                <td style="max-width: 520px;">
                                    <b class="d-block text-truncate">{{ $faq->faq_question }}</b>
                                    <small class="text-muted d-block text-truncate">{{ Str::limit(html_entity_decode(strip_tags($faq->faq_answer)), 140) }}</small>
                                </td>
                                <td class="text-center"><span class="badge bg-label-primary">{{ $faq->group?->group_name }}</span></td>
                                <td class="text-center">{{ $faq->faq_position }}</td>
                                <td class="text-center">
                                    <input name="faq-status" class="form-check-input" type="checkbox" value="1"
                                        data-url="{{ route('faq.status', $faq->faq_id) }}" aria-label="Hiển thị câu hỏi"
                                        @checked($faq->faq_hidden == 1) />
                                </td>
                                <td class="text-center text-nowrap">
                                    <a class="btn btn-primary btn-sm m-1 p-2" href="{{ route('faq.edit', $faq->faq_id) }}" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <form class="d-inline" action="{{ route('faq.destroy', $faq->faq_id) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm m-1 p-2" title="Xoá"
                                            onclick="return confirm('Xoá hẳn câu hỏi này? Muốn gỡ tạm thì bỏ chọn Hiển thị.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Không có câu hỏi nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $faqs->onEachSide(1)->links('backend.layouts.partials.pagination') }}
        </div>
    </div>
@endsection
