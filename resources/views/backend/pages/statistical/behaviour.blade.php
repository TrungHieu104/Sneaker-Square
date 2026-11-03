@extends('backend.index')

@use('App\Models\CartReminderModel')
@use('App\Services\Analytics\BehaviourReport')
@use('Illuminate\Support\Js')

@section('title')
    Hành vi khách hàng
@endsection

@php
    $number = fn (int $value) => number_format($value, 0, ',', '.');
    $percent = fn (?float $value) => $value === null ? '—' : number_format($value, 1, ',', '.').'%';

    $reminderLabels = [CartReminderModel::FIRST => 'Nhắc lần 1', CartReminderModel::SECOND => 'Nhắc lần 2', CartReminderModel::OFFER => 'Tặng mã'];
    $adviceClass = [
        BehaviourReport::RESTOCK_NOW => 'bg-label-danger',
        BehaviourReport::RUNNING_LOW => 'bg-label-warning',
        BehaviourReport::NEVER_STOCKED => 'bg-label-info',
    ];

    $shownRows = 5;
    $restockByProduct = $restock->groupBy('pro_name');
    $mostViews = max(1, (int) $viewedNotBought->max('views'));
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 py-3 mb-2">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Thống kê /</span> Hành vi khách hàng</h4>
            <small class="text-muted">{{ $from->format('d/m') }} – {{ $to->format('d/m/Y') }}, cập nhật lúc 01:00 mỗi đêm</small>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Khoảng thời gian">
            @foreach ($periods as $period)
                <a href="{{ route('admin.behaviour', ['days' => $period]) }}" @class(['btn', 'btn-primary' => $period === $days, 'btn-outline-primary' => $period !== $days])
                    @if ($period === $days) aria-current="page" @endif>{{ $period }} ngày</a>
            @endforeach
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card h-100 card-border-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Phễu chuyển đổi</h5>
                    <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Cách đếm" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Số phiên đếm theo từng ngày: khách quay lại vào ngày khác được tính thêm một phiên."></i>
                </div>
                <div class="card-body">
                    <div id="chart-funnel"></div>
                    <div class="d-flex flex-wrap gap-4 border-top pt-3 mt-1">
                        <div>
                            <small class="text-muted d-block">Tỷ lệ chuyển đổi</small>
                            <span class="fs-5 fw-semibold">{{ $percent($funnel[array_key_last($funnel)]['fromTop']) }}</span>
                        </div>
                        @if ($biggestDrop)
                            <div>
                                <small class="text-muted d-block">Khách bỏ đi nhiều nhất</small>
                                <span class="fw-semibold">{{ $biggestDrop['from'] }} → {{ $biggestDrop['to'] }}</span>
                                <span class="text-danger text-nowrap">{{ $number($biggestDrop['sessions']) }} phiên ({{ $percent($biggestDrop['rate']) }})</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card h-100 card-border-top">
                <div class="card-header">
                    <h5 class="mb-0">Từ khoá tìm không ra kết quả</h5>
                </div>
                <div class="card-body">
                    @if ($missedSearches->isEmpty())
                        <p class="text-muted mb-0">Không có lượt tìm nào trượt.</p>
                    @else
                        <div id="chart-searches"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-start">
        <div class="col-xl-5 mb-4">
            <div class="card card-border-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cần nhập thêm</h5>
                    <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Ý nghĩa đề xuất" data-bs-toggle="tooltip"
                        data-bs-placement="left" data-bs-html="true"
                        title="<b>Nhập ngay</b>: hết hàng mà khách vẫn chọn.<br><b>Sắp thiếu</b>: số còn lại không đủ bán thêm một kỳ như kỳ này.<br><b>Chưa có biến thể</b>: cặp size, màu shop chưa từng nhập."></i>
                </div>
                <div class="card-body">
                    @if ($restockByProduct->isEmpty())
                        <p class="text-muted mb-0">Chưa có biến thể nào cần nhập thêm.</p>
                    @else
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($adviceClass as $advice => $class)
                                <span class="badge chip {{ $class }}">{{ $advice }}</span>
                            @endforeach
                        </div>
                        @foreach ($restockByProduct->chunk($shownRows) as $page => $products)
                            @if ($page === 1)
                                <div class="collapse" id="more-restock">
                            @endif
                            @foreach ($products as $name => $variants)
                                <div class="list-row">
                                    <div class="fw-semibold mb-1">{{ $name }}</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($variants as $variant)
                                            <span class="badge chip {{ $adviceClass[$variant->advice] }}" tabindex="0" data-bs-toggle="tooltip"
                                                title="{{ $variant->advice }}: chạm hết hàng {{ $variant->hits }}, chọn {{ $variant->selects }}, thêm giỏ {{ $variant->carts }}, đã bán {{ $variant->sold }}, tồn {{ $variant->stock ?? '—' }}">{{ $variant->color ?? '—' }} · {{ $variant->size ?? '—' }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            @if ($page > 0 && $loop->last)
                                </div>
                            @endif
                        @endforeach
                        @if ($restockByProduct->count() > $shownRows)
                            <button type="button" class="btn btn-sm btn-link px-0 mt-2" data-bs-toggle="collapse" data-bs-target="#more-restock"
                                onclick="this.classList.add('d-none')">Xem thêm {{ $restockByProduct->count() - $shownRows }} sản phẩm</button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7 mb-4">
            <div class="card card-border-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Xem nhiều nhưng ít người mua</h5>
                    <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Điều kiện" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Chỉ tính sản phẩm có từ 10 lượt xem, tỷ lệ mua thấp nhất đứng đầu."></i>
                </div>
                <div class="card-body">
                    @if ($viewedNotBought->isEmpty())
                        <p class="text-muted mb-0">Chưa có sản phẩm nào đủ 10 lượt xem.</p>
                    @else
                        @foreach ($viewedNotBought->chunk($shownRows) as $page => $products)
                            @if ($page === 1)
                                <div class="collapse" id="more-viewed">
                            @endif
                            @foreach ($products as $product)
                                <div class="list-row d-flex align-items-center gap-3">
                                    <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($product->pro_img) }}" alt=""
                                        class="rounded border flex-shrink-0" width="36" height="36" style="object-fit: cover;">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="text-truncate" title="{{ $product->pro_name }}">{{ $product->pro_name }}</span>
                                            <span @class(['text-nowrap fw-semibold', 'text-danger' => $product->purchases === 0])>{{ $percent($product->conversion) }}</span>
                                        </div>
                                        <div class="progress my-1" style="height: 6px;">
                                            <div class="progress-bar" style="width: {{ $product->views / $mostViews * 100 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $number($product->views) }} lượt xem · {{ $number($product->carts) }} thêm giỏ · {{ $number($product->purchases) }} mua</small>
                                    </div>
                                </div>
                            @endforeach
                            @if ($page > 0 && $loop->last)
                                </div>
                            @endif
                        @endforeach
                        @if ($viewedNotBought->count() > $shownRows)
                            <button type="button" class="btn btn-sm btn-link px-0 mt-2" data-bs-toggle="collapse" data-bs-target="#more-viewed"
                                onclick="this.classList.add('d-none')">Xem thêm {{ $viewedNotBought->count() - $shownRows }} sản phẩm</button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card h-100 card-border-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Email nhắc giỏ hàng</h5>
                    <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Cách tính" data-bs-toggle="tooltip"
                        data-bs-placement="left"
                        title="Mua lại: đơn có sản phẩm trong giỏ được nhắc, đặt trong {{ BehaviourReport::ATTRIBUTION_DAYS }} ngày sau khi khách bấm nút trong email. Đơn không qua email không được tính, nên đây là con số tối thiểu. Lượt mở chỉ đếm được khi ứng dụng mail tải hình."></i>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="fs-4 fw-semibold">{{ $number(array_sum(array_column($cartReminders, 'value'))) }} đ</span>
                        <small class="text-muted ms-1">doanh thu từ {{ $number(array_sum(array_column($cartReminders, 'orders'))) }} giỏ mua lại</small>
                    </div>
                    <div id="chart-reminders"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card h-100 card-border-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Hiệu quả gợi ý sản phẩm</h5>
                    <i class="bx bx-help-circle text-muted" tabindex="0" role="button" aria-label="Cách tính" data-bs-toggle="tooltip"
                        data-bs-placement="left"
                        title="Lượt hiện đếm từng sản phẩm được gợi ý. Thêm giỏ và mua chỉ tính khi đúng sản phẩm được bấm, trong cùng lượt truy cập và cùng ngày."></i>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-4 mb-2">
                        @foreach ($recommendations as $block)
                            <div>
                                <small class="text-muted d-block">{{ $block['label'] }}</small>
                                <span class="fw-semibold">{{ $number($block['clicks']) }}</span>
                                <small class="text-muted">lượt bấm / {{ $number($block['shown']) }} lượt hiện</small>
                            </div>
                        @endforeach
                    </div>
                    <div id="chart-recommendations"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css-backend')
    <style>
        .list-row + .list-row,
        .collapse > .list-row:first-child {
            border-top: 1px solid #eceef1;
            margin-top: .75rem;
            padding-top: .75rem;
        }

        .chip {
            text-transform: none;
            font-size: .8125rem;
        }

        .min-w-0 {
            min-width: 0;
        }
    </style>
@endpush

@push('script-backend')
    <script>
        (function () {
            const funnel = {{ Js::from($funnel) }};
            const searches = {{ Js::from($missedSearches->take(8)->values()) }};
            const reminders = {{ Js::from($cartReminders) }};
            const reminderLabels = {{ Js::from($reminderLabels) }};
            const recommendations = {{ Js::from($recommendations) }};

            const colors = config.colors;
            const number = value => new Intl.NumberFormat('vi-VN').format(value);
            const percent = value => value === null ? '—' : value.toLocaleString('vi-VN', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            const axisLabels = { rotate: 0, hideOverlappingLabels: false, style: { colors: colors.axisColor, fontSize: '12px' } };
            const base = {
                chart: { toolbar: { show: false }, fontFamily: 'inherit', parentHeightOffset: 0, animations: { enabled: false } },
                grid: { borderColor: colors.borderColor, strokeDashArray: 4, padding: { top: -10 } },
                legend: { position: 'top', horizontalAlign: 'left', markers: { radius: 12 }, labels: { colors: colors.headingColor } },
                states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
            };

            // The value labels sit past the end of each bar; leave them room, more on a phone.
            function horizontalBars(selector, labels, values, color, label, tooltip) {
                const element = document.querySelector(selector);
                const headroom = element.clientWidth < 500 ? 2 : 1.35;

                new ApexCharts(element, {
                    ...base,
                    chart: { ...base.chart, type: 'bar', height: labels.length * 38 + 20 },
                    series: [{ name: '', data: values }],
                    colors: [color],
                    plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4, dataLabels: { position: 'top' } } },
                    dataLabels: {
                        enabled: true, textAnchor: 'start', offsetX: 16, formatter: label,
                        style: { colors: [colors.headingColor], fontSize: '12px', fontWeight: 500 },
                    },
                    grid: { show: false, padding: { top: -10, right: 0, bottom: -10 } },
                    xaxis: { categories: labels, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { max: Math.max(...values, 1) * headroom, labels: { maxWidth: 150, style: { colors: colors.headingColor, fontSize: '13px' } } },
                    tooltip: { y: { title: { formatter: () => '' }, formatter: tooltip } },
                }).render();
            }

            horizontalBars('#chart-funnel', funnel.map(step => step.label), funnel.map(step => step.sessions), colors.primary,
                (value, { dataPointIndex }) => number(value) + ' · ' + percent(funnel[dataPointIndex].fromTop),
                value => number(value) + ' phiên');

            if (searches.length) {
                horizontalBars('#chart-searches', searches.map(search => search.keyword), searches.map(search => search.misses), colors.warning,
                    value => number(value),
                    (value, { dataPointIndex }) => number(value) + ' / ' + number(searches[dataPointIndex].searches) + ' lượt tìm không ra');
            }

            const sent = reminders.filter(step => step.sent > 0);
            const reminderRates = [null, 'openRate', 'clickRate', 'orderRate'];
            new ApexCharts(document.querySelector('#chart-reminders'), {
                ...base,
                chart: { ...base.chart, type: 'bar', height: 250 },
                series: sent.map(step => ({ name: reminderLabels[step.step], data: [step.sent, step.opened, step.clicked, step.orders] })),
                colors: [colors.primary, colors.info, colors.warning],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
                dataLabels: { enabled: false },
                xaxis: { categories: ['Đã gửi', 'Đã mở', ['Bấm', 'vào giỏ'], 'Mua lại'], labels: axisLabels, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { ...axisLabels, formatter: value => number(Math.round(value)) } },
                tooltip: {
                    y: {
                        formatter: (value, { seriesIndex, dataPointIndex }) => {
                            const rate = reminderRates[dataPointIndex];
                            return rate ? number(value) + ' (' + percent(sent[seriesIndex][rate]) + ')' : number(value) + ' email';
                        },
                    },
                },
                noData: { text: 'Chưa gửi email nhắc nào trong kỳ này.' },
            }).render();

            const recommendationRates = [['ctr', 'clicks', 'shown'], ['cartRate', 'carts', 'clicks'], ['purchaseRate', 'purchases', 'clicks']];
            new ApexCharts(document.querySelector('#chart-recommendations'), {
                ...base,
                chart: { ...base.chart, type: 'bar', height: 250 },
                series: recommendations.map(block => ({ name: block.label, data: recommendationRates.map(([rate]) => block[rate] ?? 0) })),
                colors: [colors.primary, colors.info],
                plotOptions: { bar: { columnWidth: '50%', borderRadius: 3, dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, offsetY: -18, formatter: value => percent(value), style: { colors: [colors.headingColor], fontSize: '11px', fontWeight: 500 } },
                xaxis: { categories: [['Tỷ lệ', 'bấm'], ['Bấm rồi', 'thêm giỏ'], ['Bấm rồi', 'mua']], labels: axisLabels, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { ...axisLabels, formatter: value => Math.round(value) + '%' } },
                tooltip: {
                    y: {
                        formatter: (value, { seriesIndex, dataPointIndex }) => {
                            const [, count, of] = recommendationRates[dataPointIndex];
                            const block = recommendations[seriesIndex];
                            return percent(value) + ' (' + number(block[count]) + ' / ' + number(block[of]) + ')';
                        },
                    },
                },
            }).render();
        })();
    </script>
@endpush
