@use('App\Enums\CustomerSegment')
@use('App\Services\CustomerSegments\SegmentCalculator')

@php
    $trieu = fn ($value) => number_format((int) $value / 1_000_000, 1, ',', '.').' triệu';
    $coMoc = array_filter($levels, fn ($value) => $value !== null) !== [];
@endphp

<div class="modal fade" id="modal-cach-chia-nhom" tabindex="-1" aria-labelledby="modal-cach-chia-nhom-tieu-de" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cach-chia-nhom-tieu-de">Cách chia nhóm khách</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-3">Mỗi đêm, các khách có đơn thành công được so với nhau theo 3 tiêu chí dưới đây. Khách nằm giữa hai mốc là mức trung bình.</p>
                @if ($coMoc)
                    <table class="table table-bordered align-middle mb-2">
                        <thead>
                            <tr><th>Tiêu chí</th><th>Mức cao</th><th>Mức thấp</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Lần mua gần nhất</td>
                                <td>{{ $levels['recencyHigh'] !== null ? 'Trong '.$levels['recencyHigh'].' ngày qua' : '—' }}</td>
                                <td>{{ $levels['recencyLow'] !== null ? 'Đã '.$levels['recencyLow'].' ngày trở lên chưa mua' : '—' }}</td>
                            </tr>
                            <tr>
                                <td>Số đơn</td>
                                <td>{{ $levels['frequencyHigh'] !== null ? 'Từ '.$levels['frequencyHigh'].' đơn' : '—' }}</td>
                                <td>{{ $levels['frequencyLow'] !== null ? ($levels['frequencyLow'] > 1 ? 'Đến ' : '').$levels['frequencyLow'].' đơn' : '—' }}</td>
                            </tr>
                            <tr>
                                <td>Tổng tiền đã chi</td>
                                <td>{{ $levels['monetaryHigh'] !== null ? 'Từ '.$trieu($levels['monetaryHigh']) : '—' }}</td>
                                <td>{{ $levels['monetaryLow'] !== null ? 'Đến '.$trieu($levels['monetaryLow']) : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
                <small class="text-muted d-block mb-4">
                    Các mốc tính theo khách hiện có nên thay đổi theo thời gian. Dưới {{ SegmentCalculator::MIN_CUSTOMERS }} khách thì tất cả được xếp vào {{ CustomerSegment::Regular->label() }}.
                </small>
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr><th style="width: 150px;">Nhóm</th><th>Gồm những khách</th></tr>
                    </thead>
                    <tbody>
                        @foreach ([CustomerSegment::Loyal, CustomerSegment::Promising, CustomerSegment::AtRisk, CustomerSegment::Lost, CustomerSegment::Regular] as $nhom)
                            <tr>
                                <td><span class="badge chip {{ $nhom->badgeClass() }}">{{ $nhom->label() }}</span></td>
                                <td>{{ $nhom->criteria() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
