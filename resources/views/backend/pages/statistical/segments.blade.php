@extends('backend.index')

@use('Illuminate\Support\Js')

@section('title')
    Phân khúc khách hàng
@endsection

@php
    $total = array_sum(array_column($groups, 'customers'));
    $million = fn (int $value) => number_format($value / 1_000_000, 1, ',', '.').' tr';
    $canSend = auth()->user()->can('Quản trị Mã giảm giá');
@endphp

@section('content')
    <div class="py-3 mb-2">
        <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Thống kê /</span> Phân khúc khách hàng</h4>
        <small class="text-muted">Cập nhật lúc 02:00 mỗi đêm · {{ $total }} khách đã có đơn thành công</small>
    </div>

    @if ($total === 0)
        <div class="card card-border-top mb-4">
            <div class="card-body text-muted">Chưa có khách nào có đơn thành công để xếp phân khúc.</div>
        </div>
    @else
        <div class="row">
            <div class="col-xl-5 mb-4">
                <div class="card h-100 card-border-top">
                    <div class="card-header"><h5 class="mb-0">Tỷ trọng khách</h5></div>
                    <div class="card-body"><div id="chart-share"></div></div>
                </div>
            </div>
            <div class="col-xl-7 mb-4">
                <div class="card h-100 card-border-top">
                    <div class="card-header"><h5 class="mb-0">Doanh thu theo nhóm</h5></div>
                    <div class="card-body"><div id="chart-revenue"></div></div>
                </div>
            </div>
        </div>

        <div class="card card-border-top mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    Các nhóm khách
                    <button type="button" class="btn btn-link p-0 align-baseline text-muted" data-bs-toggle="modal" data-bs-target="#modal-cach-chia-nhom" aria-label="Cách chia nhóm khách" title="Cách chia nhóm khách">
                        <i class="bx bx-help-circle"></i>
                    </button>
                </h5>
            </div>
            <div class="card-body">
                @foreach ($groups as $group)
                    <div class="list-row d-flex flex-wrap align-items-center gap-3">
                        <div class="flex-grow-1 min-w-0 row-title">
                            <span class="badge chip {{ $group['segment']->badgeClass() }}">{{ $group['segment']->label() }}</span>
                        </div>
                        <div class="d-flex gap-4 stat">
                            <div style="min-width: 52px;"><small>Khách</small><b>{{ $group['customers'] }}</b></div>
                            <div style="min-width: 64px;"><small>Chi TB</small><b>{{ $million($group['averageSpent']) }}</b></div>
                            <div style="min-width: 112px;"><small>Mua cuối TB</small><b>{{ $group['customers'] > 0 ? $group['averageDaysSince'].' ngày trước' : '—' }}</b></div>
                        </div>
                        <div class="d-flex gap-2 row-actions">
                            <a href="{{ route('admin.segments.show', $group['segment']) }}" class="btn btn-sm btn-outline-secondary">Xem khách</a>
                            @if ($canSend)
                                <a href="{{ route('admin.campaigns.create', $group['segment']) }}" class="btn btn-sm btn-outline-primary">Gửi ưu đãi</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @include('backend.pages.statistical.partials.segment_help')
    @endif

    <div class="card card-border-top mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                Chiến dịch đã gửi
                <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Cách đếm" data-bs-toggle="tooltip"
                    title="Đơn là các đơn dùng đúng mã của chiến dịch, không tính đơn huỷ hoặc hoàn về. Lượt mở chỉ đếm được khi ứng dụng mail tải hình."></i>
            </h5>
        </div>
        <div class="card-body">
            @forelse ($campaigns as $campaign)
                <div class="list-row d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1 min-w-0 row-title">
                        <div class="fw-semibold">{{ $campaign->name }}</div>
                        <div class="small text-muted mt-1">
                            <span class="badge chip {{ $campaign->segment->badgeClass() }}">{{ $campaign->segment->label() }}</span>
                            <span class="ms-1">{{ $campaign->created_at?->format('d/m/Y') }} · Giảm {{ $campaign->percent }}%@if ($campaign->min_order) · Đơn từ {{ number_format($campaign->min_order, 0, ',', '.') }}đ @endif</span>
                        </div>
                    </div>
                    <div class="d-flex gap-4 stat">
                        <div style="min-width: 34px;"><small>Gửi</small><b>{{ $campaign->sent }}</b></div>
                        <div style="min-width: 34px;"><small>Mở</small><b>{{ $campaign->opened }}</b></div>
                        <div style="min-width: 34px;"><small>Bấm</small><b>{{ $campaign->clicked }}</b></div>
                        <div style="min-width: 34px;"><small>Đơn</small><b>{{ $campaign->getAttribute('orders') }}</b></div>
                        <div style="min-width: 88px;"><small>Doanh thu</small><b>{{ number_format($campaign->getAttribute('revenue'), 0, ',', '.') }} đ</b></div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Chưa gửi chiến dịch nào.</p>
            @endforelse
        </div>
    </div>
@endsection

@include('backend.pages.statistical.partials.segment_styles')

@if ($total > 0)
    @push('script-backend')
        <script>
            (function () {
                const colors = config.colors;
                const narrow = window.innerWidth < 576;
                const groups = {{ Js::from(array_map(fn (array $group) => [
                    'name' => $group['segment']->label(),
                    'colour' => $group['segment']->chartColour(),
                    'customers' => $group['customers'],
                    'revenue' => $group['revenue'],
                ], $groups)) }};
                const total = groups.reduce((sum, group) => sum + group.customers, 0);
                const percent = value => (value / total * 100).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + '%';
                // Plotted in millions: the chart places a label by the width of
                // the raw value, so nine digits push it back over its own bar.
                const inMillions = value => Math.round(value / 1e5) / 10;

                new ApexCharts(document.querySelector('#chart-share'), {
                    chart: { type: 'donut', height: narrow ? 270 : 320, fontFamily: 'inherit', animations: { enabled: false } },
                    series: groups.map(group => group.customers),
                    labels: groups.map(group => group.name + ' ' + percent(group.customers)),
                    colors: groups.map(group => group.colour),
                    legend: { position: 'bottom', labels: { colors: colors.headingColor } },
                    dataLabels: { enabled: false },
                    stroke: { width: 2 },
                    tooltip: { y: { formatter: value => value + ' khách' } },
                    plotOptions: { pie: { donut: { size: '70%', labels: {
                        show: true,
                        name: { fontSize: '13px', offsetY: -4 },
                        value: { color: colors.headingColor, fontSize: '24px', fontWeight: 600, offsetY: 4 },
                        total: { show: true, label: 'Tổng khách', fontSize: '13px', color: colors.axisColor, formatter: () => String(total) },
                    } } } },
                }).render();

                const element = document.querySelector('#chart-revenue');
                const sorted = [...groups].sort((a, b) => b.revenue - a.revenue);
                new ApexCharts(element, {
                    chart: { type: 'bar', height: element.clientWidth < 500 ? 210 : 300, toolbar: { show: false }, fontFamily: 'inherit', parentHeightOffset: 0, animations: { enabled: false } },
                    series: [{ name: '', data: sorted.map(group => inMillions(group.revenue)) }],
                    colors: sorted.map(group => group.colour),
                    plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '60%', borderRadius: 4, dataLabels: { position: 'top' } } },
                    dataLabels: { enabled: true, textAnchor: 'start', offsetX: 16, formatter: value => value.toLocaleString('vi-VN') + ' tr', style: { colors: [colors.headingColor], fontSize: '12px', fontWeight: 500 } },
                    legend: { show: false },
                    grid: { show: false, padding: { top: -10, right: 0, bottom: -10 } },
                    xaxis: { categories: sorted.map(group => group.name), labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                    // The value labels sit past the end of each bar; leave them room.
                    yaxis: { max: Math.max(...sorted.map(group => inMillions(group.revenue)), 1) * (element.clientWidth < 500 ? 1.7 : 1.25), labels: { maxWidth: 150, style: { colors: colors.headingColor, fontSize: '13px' } } },
                    tooltip: { y: { title: { formatter: () => '' }, formatter: value => value.toLocaleString('vi-VN') + ' triệu đồng' } },
                }).render();
            })();
        </script>
    @endpush
@endif
