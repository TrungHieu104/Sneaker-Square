@extends('backend.index')

@section('title')
    Câu trả lời mẫu của trợ lý
@endsection

@section('content')
    @php
        $duocSua = auth()->user()->can('Quản trị Chính sách & FAQ');
        $toanShop = \App\Models\AssistantKnowledgeModel::SCOPE_ALL;
    @endphp

    @include('backend.pages.assistant.partials.tabs', ['current' => 'cau-tra-loi-mau'])

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        @if ($answers->isEmpty())
            <div class="card-body text-muted">Chưa có câu trả lời mẫu nào.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 28%;">Câu hỏi mẫu</th>
                            <th>Câu trả lời</th>
                            <th>Áp dụng cho</th>
                            <th>Cập nhật</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($answers as $muc)
                            <tr>
                                <td class="fw-semibold text-heading">{{ $muc['entry']->title }}</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($muc['entry']->content, 160) }}</td>
                                <td class="small">{{ $muc['scope'] }}</td>
                                <td class="text-nowrap small">{{ $muc['entry']->updated_at?->format('H:i d/m/Y') }}</td>
                                <td class="text-center text-nowrap">
                                    @if ($duocSua)
                                        <div class="row-actions">
                                            <x-admin.row-action action="edit" title="Chỉnh sửa" data-bs-toggle="offcanvas" data-bs-target="#sua-{{ $muc['entry']->id }}" />
                                            @if ($muc['entry']->scope_type === $toanShop)
                                                <x-admin.row-action action="more" title="Đăng lên trang FAQ" icon="fas fa-upload" data-bs-toggle="offcanvas" data-bs-target="#dang-{{ $muc['entry']->id }}" />
                                            @endif
                                            <form method="POST" action="{{ route('assistant_admin.answers.destroy', $muc['entry']->id) }}"
                                                data-confirm="Xoá câu trả lời này? Trợ lý sẽ không dùng nó nữa." data-confirm-ok="Xóa">
                                                @csrf
                                                @method('DELETE')
                                                <x-admin.row-action action="delete" title="Xóa" type="submit" />
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($duocSua)
        @foreach ($answers as $muc)
            <div class="offcanvas offcanvas-end" tabindex="-1" id="sua-{{ $muc['entry']->id }}" style="width: 520px;" aria-labelledby="sua-{{ $muc['entry']->id }}-tieu-de">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="sua-{{ $muc['entry']->id }}-tieu-de">Sửa câu trả lời</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
                </div>
                <div class="offcanvas-body">
                    <form method="POST" action="{{ route('assistant_admin.answers.update', $muc['entry']->id) }}">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.assistant.partials.answer-fields', [
                            'id' => 'sua-'.$muc['entry']->id,
                            'question' => $muc['entry']->title,
                            'answer' => $muc['entry']->content,
                            'choices' => $muc['choices'],
                            'scope' => $muc['entry']->scope_type,
                            'scopeId' => $muc['entry']->scope_id,
                        ])
                        <button class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>
            @if ($muc['entry']->scope_type === $toanShop)
                <div class="offcanvas offcanvas-end" tabindex="-1" id="dang-{{ $muc['entry']->id }}" style="width: 520px;" aria-labelledby="dang-{{ $muc['entry']->id }}-tieu-de">
                    <div class="offcanvas-header border-bottom">
                        <h5 class="offcanvas-title" id="dang-{{ $muc['entry']->id }}-tieu-de">Đăng lên trang câu hỏi thường gặp</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p class="text-muted small">
                            Câu này sẽ hiện công khai trên trang Câu hỏi thường gặp và rời khỏi danh sách câu trả lời mẫu.
                            Trợ lý vẫn dùng nó, lấy từ trang đó. Muốn sửa câu chữ thì sửa ở đây trước, hoặc sửa sau ở mục Câu hỏi thường gặp.
                        </p>
                        <div class="border rounded p-3 mb-3 small">
                            <div class="fw-semibold mb-1">{{ $muc['entry']->title }}</div>
                            <div style="white-space: pre-line;">{{ $muc['entry']->content }}</div>
                        </div>
                        @if ($faqGroups->isEmpty())
                            <p class="small mb-0">Chưa có nhóm câu hỏi nào đang hiện. <a href="{{ route('faq_group.index') }}">Thêm hoặc hiện một nhóm</a> trước.</p>
                        @else
                            <form method="POST" action="{{ route('assistant_admin.answers.publish', $muc['entry']->id) }}">
                                @csrf
                                <label class="form-label" for="dang-{{ $muc['entry']->id }}-nhom">Nhóm</label>
                                <select class="form-select mb-3" id="dang-{{ $muc['entry']->id }}-nhom" name="group_id" required>
                                    @foreach ($faqGroups as $group)
                                        <option value="{{ $group->group_id }}">{{ $group->group_name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-success">Đăng lên trang FAQ</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
