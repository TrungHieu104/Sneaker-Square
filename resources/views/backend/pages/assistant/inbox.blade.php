@extends('backend.index')

@section('title')
    Trợ lý AI
@endsection

@section('content')
    @php
        use App\Models\AssistantConversationModel as Chat;
        use App\Models\AssistantMessageModel as Msg;

        $trangThai = [
            Chat::WAITING_STAFF => ['Chờ nhân viên', 'warning'],
            Chat::ANSWERED => ['Đã trả lời', 'success'],
            Chat::BOT => ['Bot đang trả lời', 'info'],
            Chat::CLOSED => ['Đã đóng', 'secondary'],
        ];
        $nguoiGui = [Msg::CUSTOMER => 'Khách', Msg::BOT => 'Trợ lý', Msg::STAFF => 'Nhân viên'];
        $tien = fn (int $soTien) => number_format($soTien, 0, ',', '.').'đ';
        $duocLuuMau = auth()->user()->can('Quản trị Chính sách & FAQ');
        $xemDon = auth()->user()->can('Quản trị Đơn hàng');
    @endphp

    @include('backend.pages.assistant.partials.tabs', ['current' => $tab])

    @if ($errors->hasAny(['question', 'answer', 'scope', 'scope_id', 'message_id']))
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 assistant-inbox">
        <div class="col-xl-3 col-lg-4">
            <div class="card h-100">
                @forelse ($conversations as $chat)
                    <a href="{{ route('assistant_admin.index', ['tab' => $tab, 'chat' => $chat->id]) }}"
                        class="d-block px-3 py-3 border-bottom text-body {{ $selected?->id === $chat->id ? 'bg-label-primary border-start border-3 border-primary' : '' }}">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold text-heading text-truncate">{{ $chat->user->name ?? 'Khách vãng lai' }}</span>
                            <small class="text-muted text-nowrap">{{ $chat->last_message_at?->diffForHumans(short: true) }}</small>
                        </div>
                        <div class="small text-truncate mt-1">{{ $chat->preview ?? '—' }}</div>
                        <span class="badge bg-label-{{ $trangThai[$chat->status][1] }} mt-2">{{ $trangThai[$chat->status][0] }}</span>
                    </a>
                @empty
                    <div class="card-body text-muted">Chưa có cuộc chat nào cần nhân viên.</div>
                @endforelse
                @if ($conversations->hasPages())
                    <div class="card-body pt-3">{{ $conversations->links('backend.layouts.partials.pagination') }}</div>
                @endif
            </div>
        </div>

        <div class="col-xl-6 col-lg-8">
            <div class="card h-100 d-flex flex-column">
                @if ($selected)
                    <div class="card-header d-flex justify-content-between align-items-center gap-2 border-bottom">
                        <h5 class="mb-0">{{ $selected->user->name ?? 'Khách vãng lai' }}</h5>
                        @if ($selected->product)
                            <span class="badge bg-label-primary text-truncate" style="max-width: 60%;">Đang xem: {{ $selected->product->pro_name }}</span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column gap-2 bg-lighter overflow-auto" id="assistant-thread" style="max-height: 560px; min-height: 360px;">
                        @foreach ($selected->messages as $tin)
                            @if ($tin->sender === Msg::SYSTEM)
                                <div class="align-self-center small text-warning bg-label-warning px-2 py-1 rounded">{{ $tin->body }}</div>
                            @else
                                <div class="p-2 px-3 rounded {{ $tin->sender === Msg::CUSTOMER ? 'align-self-start bg-white border' : 'align-self-end '.($tin->sender === Msg::STAFF ? 'bg-label-success' : 'bg-label-primary') }}"
                                    style="max-width: 78%;">
                                    <small class="d-block text-muted">{{ $nguoiGui[$tin->sender] }}{{ $tin->staff ? ' '.$tin->staff->name : '' }} · {{ $tin->created_at?->format('H:i d/m') }}</small>
                                    <span style="white-space: pre-line;">{{ $tin->body }}</span>
                                    @if ($duocLuuMau && $tin->sender === Msg::STAFF && $replies[$tin->id]['saved'])
                                        <span class="d-block mt-1 text-muted" style="font-size: 12px;"><i class="bx bx-check" style="font-size: 13px;"></i> Đã lưu thành câu trả lời mẫu</span>
                                    @elseif ($duocLuuMau && $tin->sender === Msg::STAFF)
                                        <button type="button" class="btn btn-link btn-sm p-0 d-block mt-1 text-muted fw-normal" style="font-size: 12px;" data-bs-toggle="offcanvas" data-bs-target="#luu-mau-{{ $tin->id }}"><i class="bx bx-bookmark" style="font-size: 13px;"></i> Lưu thành câu trả lời mẫu</button>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @if ($selected->status !== Chat::CLOSED)
                        <div class="card-footer border-top pt-3">
                            <form method="POST" action="{{ route('assistant_admin.reply', $selected->id) }}" class="d-flex gap-2 align-items-start">
                                @csrf
                                <div class="flex-grow-1">
                                    <textarea name="body" rows="2" class="form-control" placeholder="Trả lời khách…" required maxlength="2000">{{ old('body') }}</textarea>
                                    @error('body')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                </div>
                                <button class="btn btn-primary">Gửi</button>
                            </form>
                            <form method="POST" action="{{ route('assistant_admin.close', $selected->id) }}" class="mt-2 text-end">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Đóng cuộc chat</button>
                            </form>
                        </div>
                    @endif
                @else
                    <div class="card-body text-muted">Chọn một cuộc chat bên trái để xem.</div>
                @endif
            </div>
        </div>

        <div class="col-xl-3 col-lg-12">
            <div class="card h-100">
                <div class="card-body">
                    @if ($customer === null)
                        <p class="text-muted mb-0">Chưa chọn cuộc chat.</p>
                    @elseif ($customer['guest'])
                        <h6>Khách hàng</h6>
                        <p class="small mb-3">Khách vãng lai</p>
                    @else
                        <h6>Khách hàng</h6>
                        <dl class="row small mb-3">
                            <dt class="col-6 text-muted fw-normal">Tài khoản</dt><dd class="col-6 text-end mb-1">#{{ $customer['user']->user_id }}</dd>
                            <dt class="col-6 text-muted fw-normal">Email</dt><dd class="col-6 text-end mb-1 text-truncate" title="{{ $customer['user']->email }}">{{ $customer['user']->email }}</dd>
                            <dt class="col-6 text-muted fw-normal">Phân khúc</dt><dd class="col-6 text-end mb-1">{{ $customer['segment'] ?? 'Chưa phân khúc' }}</dd>
                            <dt class="col-6 text-muted fw-normal">Số đơn</dt><dd class="col-6 text-end mb-1">{{ $customer['orders'] }} · {{ $tien($customer['spent']) }}</dd>
                            <dt class="col-6 text-muted fw-normal">Size đã mua</dt><dd class="col-6 text-end mb-1">{{ $customer['size'] ?? '—' }}</dd>
                        </dl>
                        @if ($customer['latest'])
                            <h6>Đơn gần nhất</h6>
                            <dl class="row small mb-3">
                                <dt class="col-6 text-muted fw-normal">{{ $customer['latest']->order_code }}</dt><dd class="col-6 text-end mb-1">{{ $tien((int) $customer['latest']->order_total) }}</dd>
                                <dt class="col-6 text-muted fw-normal">Trạng thái</dt><dd class="col-6 text-end mb-1">{{ $customer['latest']->order_status->label() }}</dd>
                            </dl>
                            @if ($xemDon)
                                <a class="small" href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($customer['latest']->order_id)]) }}">Mở đơn hàng</a>
                            @endif
                        @endif
                    @endif
                    @if ($duocLuuMau)
                        <hr>
                        <h6>Sau khi trả lời</h6>
                        <p class="text-muted small mb-0">
                            Nếu câu trả lời dùng được cho khách khác, bấm "Lưu thành câu trả lời mẫu" dưới tin nhắn của bạn để trợ lý dùng cho lần sau.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($duocLuuMau && $selected)
        @foreach ($selected->messages->where('sender', Msg::STAFF)->reject(fn ($tin) => $replies[$tin->id]['saved']) as $tin)
            <div class="offcanvas offcanvas-end" tabindex="-1" id="luu-mau-{{ $tin->id }}" style="width: 520px;" aria-labelledby="luu-mau-{{ $tin->id }}-tieu-de">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="luu-mau-{{ $tin->id }}-tieu-de">Lưu thành câu trả lời mẫu</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
                </div>
                <div class="offcanvas-body">
                    <p class="text-muted small">Sửa lại cho gọn và bỏ chi tiết riêng của khách này trước khi lưu, vì trợ lý sẽ dùng nội dung này cho mọi khách.</p>
                    <form method="POST" action="{{ route('assistant_admin.save_answer', $selected->id) }}">
                        @csrf
                        <input type="hidden" name="message_id" value="{{ $tin->id }}">
                        @include('backend.pages.assistant.partials.answer-fields', [
                            'id' => 'luu-mau-'.$tin->id,
                            'question' => $replies[$tin->id]['question'],
                            'answer' => $tin->body,
                            'choices' => $replies[$tin->id]['choices'],
                        ])
                        <button class="btn btn-primary">Lưu câu trả lời</button>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('script-backend')
    <script>
        // The newest line is the one staff answer; start the thread there.
        const thread = document.getElementById('assistant-thread');

        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    </script>
@endpush
