@extends('backend.index')

@section('title')
    {{ $segment->label() }}
@endsection

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 py-3 mb-2">
        <div>
            <h4 class="fw-bold mb-1">
                <a href="{{ route('admin.segments') }}" class="text-muted fw-light">Phân khúc khách hàng /</a> {{ $segment->label() }}
            </h4>
            <small class="text-muted">{{ $members->total() }} khách</small>
        </div>
        @can('Quản trị Mã giảm giá')
            <a href="{{ route('admin.campaigns.create', $segment) }}" class="btn btn-primary btn-sm">Gửi ưu đãi cho nhóm này</a>
        @endcan
    </div>

    <div class="card card-border-top mb-4">
        <div class="card-body">
            @forelse ($members as $member)
                <div class="list-row d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1 min-w-0 row-title">
                        <div class="fw-semibold">{{ $member->user?->name }}</div>
                        <div class="small text-muted text-truncate">{{ $member->user?->email }}</div>
                    </div>
                    <div class="d-flex gap-4 stat">
                        <div style="min-width: 110px;"><small>Lần mua cuối</small><b>{{ (int) $member->last_order_on->diffInDays(now()->startOfDay()) }} ngày trước</b></div>
                        <div style="min-width: 50px;"><small>Số đơn</small><b>{{ $member->order_count }}</b></div>
                        <div style="min-width: 100px;"><small>Tổng chi</small><b>{{ number_format($member->total_spent, 0, ',', '.') }} đ</b></div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Nhóm này chưa có khách nào.</p>
            @endforelse
            {{ $members->onEachSide(1)->links('backend.layouts.partials.pagination') }}
        </div>
    </div>
@endsection

@include('backend.pages.statistical.partials.segment_styles')
