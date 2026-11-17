@extends('backend.index')

@section('title')
    {{ $faq->exists ? 'Chỉnh sửa câu hỏi' : 'Thêm câu hỏi' }}
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3">
        <span class="text-muted fw-light"><a href="{{ route('faq.index') }}" class="tab-back">Câu hỏi thường gặp / </a></span>
        {{ $faq->exists ? 'Chỉnh sửa' : 'Thêm mới' }}
    </h4>
    <form action="{{ $faq->exists ? route('faq.update', $faq->faq_id) : route('faq.store') }}" method="post">
        @csrf
        @if ($faq->exists)
            @method('PUT')
        @endif
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8 mb-3">
                        <label for="faq-question" class="form-label">Câu hỏi</label>
                        <input type="text" class="form-control" id="faq-question" name="question" maxlength="255"
                            value="{{ old('question', $faq->faq_question) }}" placeholder="Ví dụ: Shop giao hàng bằng đơn vị nào?" />
                        @error('question')
                            <small class="text-danger fst-italic">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label for="faq-group" class="form-label">Nhóm</label>
                        <select class="form-select" id="faq-group" name="group_id">
                            <option value="">Chọn nhóm</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->group_id }}" @selected((int) old('group_id', $faq->group_id) === $group->group_id)>{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <small class="text-danger fst-italic">{{ $message }}</small>
                        @enderror
                        @if ($groups->isEmpty())
                            <small class="text-muted">Chưa có nhóm nào. <a href="{{ route('faq_group.index') }}">Thêm nhóm</a> trước.</small>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8 mb-3">
                        <label class="form-label" for="faq-author">{{ $faq->exists ? 'Người chỉnh sửa' : 'Người tạo' }}</label>
                        <input type="text" class="form-control" id="faq-author" value="{{ Auth::user()->name }}" readonly />
                    </div>
                    <div class="col-lg-4 mb-3">
                        <span class="form-label d-block">Hiển thị</span>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="1" id="faq-shown" @checked((string) old('status', $faq->faq_hidden) === '1') />
                                <label class="form-check-label" for="faq-shown">Hiện</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="0" id="faq-hidden" @checked((string) old('status', $faq->faq_hidden) === '0') />
                                <label class="form-check-label" for="faq-hidden">Ẩn</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Câu trả lời</label>
                    <textarea class="form-control" id="content" name="answer" rows="8">{{ old('answer', $faq->faq_answer) }}</textarea>
                    @error('answer')
                        <small class="text-danger fst-italic">{{ $message }}</small>
                    @enderror
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button type="submit" class="btn btn-success px-5">Lưu</button>
                    <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                </div>
            </div>
        </div>
    </form>
@endsection
