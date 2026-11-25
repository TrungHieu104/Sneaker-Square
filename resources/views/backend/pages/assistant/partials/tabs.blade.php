@php
    use App\Services\Assistant\SupportDesk;
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 mb-2">
    <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Quản trị /</span> Trợ lý AI</h4>
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link {{ $current === SupportDesk::TAB_WAITING ? 'active' : '' }}"
                href="{{ route('assistant_admin.index') }}">
                Chờ nhân viên <span class="badge rounded-pill {{ $current === SupportDesk::TAB_WAITING ? 'bg-white text-primary' : 'bg-label-secondary' }} ms-1">{{ $waiting }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $current === 'chua-tra-loi' ? 'active' : '' }}"
                href="{{ route('assistant_admin.unanswered') }}">
                Chưa trả lời được <span class="badge rounded-pill {{ $current === 'chua-tra-loi' ? 'bg-white text-primary' : 'bg-label-secondary' }} ms-1">{{ $openGroups }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $current === SupportDesk::TAB_ALL ? 'active' : '' }}"
                href="{{ route('assistant_admin.index', ['tab' => SupportDesk::TAB_ALL]) }}">Tất cả cuộc chat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $current === 'cau-tra-loi-mau' ? 'active' : '' }}"
                href="{{ route('assistant_admin.answers') }}">Câu trả lời mẫu</a>
        </li>
    </ul>
</div>
