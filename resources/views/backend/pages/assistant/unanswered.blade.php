@extends('backend.index')

@section('title')
    Câu hỏi trợ lý chưa trả lời được
@endsection

@section('content')
    @php
        use App\Enums\AssistantIntent;
        use App\Enums\AssistantTopic;
        use App\Models\AssistantUnansweredModel as Unanswered;
        use App\Services\Assistant\SupportDesk;

        $duocTraLoi = auth()->user()->can('Quản trị Chính sách & FAQ');
    @endphp

    @include('backend.pages.assistant.partials.tabs', ['current' => 'chua-tra-loi', 'openGroups' => $stats['open_groups']])

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card card-border-top h-100"><div class="card-body">
                <div class="text-muted small">Câu hỏi {{ SupportDesk::WINDOW_DAYS }} ngày</div>
                <h4 class="mb-0">{{ $stats['questions'] }}</h4>
                <div class="text-muted small">trong {{ $stats['chats'] }} cuộc chat</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-border-top h-100"><div class="card-body">
                <div class="text-muted small">Bot tự trả lời</div>
                <h4 class="mb-0">{{ $stats['bot_share'] === null ? '—' : $stats['bot_share'].'%' }}</h4>
                <div class="text-muted small">cuộc chat khách không bấm chuyển</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-border-top h-100"><div class="card-body">
                <div class="text-muted small">Chuyển nhân viên</div>
                <h4 class="mb-0">{{ $stats['handed'] }}</h4>
                <div class="text-muted small">thời gian chờ trung vị {{ $stats['median_wait'] === null ? '—' : $stats['median_wait'].' phút' }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-border-top h-100"><div class="card-body">
                <div class="text-muted small">Chưa trả lời được</div>
                <h4 class="mb-0">{{ $stats['open_groups'] }} nhóm</h4>
                <div class="text-muted small">{{ $stats['open_questions'] }} lượt hỏi</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        @if ($groups->isEmpty())
            <div class="card-body text-muted">Không còn câu hỏi nào chưa trả lời được.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 46%;">Câu hỏi (gom theo nhóm)</th>
                            <th class="text-end">Số lần</th>
                            <th>Hỏi gần nhất</th>
                            <th>Loại</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $nhom)
                            @php
                                $chuDe = AssistantTopic::tryFrom((string) $nhom['lead']->topic);
                                $yDinh = AssistantIntent::tryFrom($nhom['lead']->intent);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold text-heading">{{ $nhom['lead']->question }}</div>
                                    @if ($nhom['others']->isNotEmpty() || $nhom['more'] > 0)
                                        <div class="small text-muted">
                                            {{ $nhom['others']->implode(' · ') }}{{ $nhom['more'] > 0 ? ' · +'.$nhom['more'].' câu' : '' }}
                                        </div>
                                    @endif
                                    @if ($nhom['lead']->product)
                                        <div class="small text-muted">Hỏi ở trang: {{ $nhom['lead']->product->pro_name }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $nhom['asks'] }}</td>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($nhom['last_at'])->format('H:i d/m') }}</td>
                                <td>
                                    @if ($chuDe)
                                        <span class="badge bg-label-{{ $chuDe->badge() }}">{{ $chuDe->value }}</span>
                                    @elseif ($yDinh)
                                        <span class="badge bg-label-{{ $yDinh->badge() }}">{{ $yDinh->label() }}</span>
                                    @endif
                                    @if ($nhom['lead']->reason === Unanswered::UNHAPPY)
                                        <span class="badge bg-label-danger d-block mt-1">Khách chưa hài lòng</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($duocTraLoi)
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#tra-loi-{{ $nhom['id'] }}">Viết câu trả lời</button>
                                    @endif
                                    <form method="POST" action="{{ route('assistant_admin.dismiss', $nhom['id']) }}" class="d-inline" id="bo-qua-{{ $nhom['id'] }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary">Bỏ qua</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($duocTraLoi)
        @foreach ($groups as $nhom)
            <div class="offcanvas offcanvas-end" tabindex="-1" id="tra-loi-{{ $nhom['id'] }}" style="width: 520px;" aria-labelledby="tra-loi-{{ $nhom['id'] }}-tieu-de">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="tra-loi-{{ $nhom['id'] }}-tieu-de">Viết câu trả lời</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
                </div>
                <div class="offcanvas-body">
                    <form method="POST" action="{{ route('assistant_admin.answer', $nhom['id']) }}">
                        @csrf
                        <label class="form-label">Câu hỏi khách đã gửi ({{ $nhom['asks'] }})</label>
                        <div class="bg-lighter rounded p-2 px-3 small mb-3">
                            {{ $nhom['lead']->question }}
                            @foreach ($nhom['others'] as $cauKhac)
                                <br>{{ $cauKhac }}
                            @endforeach
                            @if ($nhom['more'] > 0)
                                <br>…
                            @endif
                        </div>
                        @if ($nhom['bot_answer'])
                            <label class="form-label">Trợ lý đã trả lời, khách vẫn đòi gặp nhân viên</label>
                            <div class="border rounded p-2 px-3 small mb-3 text-muted" style="white-space: pre-line;">{{ $nhom['bot_answer'] }}</div>
                        @endif
                        @include('backend.pages.assistant.partials.answer-fields', [
                            'id' => 'nhom-'.$nhom['id'],
                            'question' => $nhom['lead']->question,
                            'answer' => '',
                            'choices' => $nhom['choices'],
                        ])
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Lưu câu trả lời</button>
                            <button type="submit" form="bo-qua-{{ $nhom['id'] }}" class="btn btn-outline-secondary">Bỏ qua nhóm này</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@endsection
