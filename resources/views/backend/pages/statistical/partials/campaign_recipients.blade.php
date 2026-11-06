@use('Illuminate\Support\Str')

<div class="modal fade" id="modal-nguoi-nhan" tabindex="-1" aria-labelledby="modal-nguoi-nhan-tieu-de" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-nguoi-nhan-tieu-de">Khách nhận ưu đãi · {{ $segment->label() }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-2">
                <input type="search" class="form-control mb-3" id="tim-nguoi-nhan" placeholder="Tìm theo tên hoặc email" aria-label="Tìm khách">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input class="form-check-input" type="checkbox" id="chon-tat-ca" checked aria-label="Chọn tất cả"></th>
                            <th>Khách</th>
                            <th class="text-nowrap">Mua cuối</th>
                            <th class="text-nowrap text-end">Số đơn</th>
                            <th class="text-nowrap text-end">Tổng chi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recipients as $nguoiNhan)
                            <tr class="js-nguoi-nhan" data-tim="{{ Str::lower($nguoiNhan->user?->name.' '.$nguoiNhan->user?->email) }}">
                                <td>
                                    {{-- The modal sits outside the form, so each box names the form it belongs to. --}}
                                    <input class="form-check-input" type="checkbox" name="recipients[]" form="campaign-form" value="{{ $nguoiNhan->user_id }}"
                                        @checked(in_array($nguoiNhan->user_id, old('recipients', $recipients->pluck('user_id')->all())))
                                        aria-label="Gửi cho {{ $nguoiNhan->user?->name }}">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $nguoiNhan->user?->name }}</div>
                                    <small class="text-muted">{{ $nguoiNhan->user?->email }}</small>
                                </td>
                                <td class="text-nowrap">{{ (int) $nguoiNhan->last_order_on->diffInDays(now()->startOfDay()) }} ngày trước</td>
                                <td class="text-end">{{ $nguoiNhan->order_count }}</td>
                                <td class="text-end text-nowrap">{{ number_format($nguoiNhan->total_spent, 0, ',', '.') }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer justify-content-between flex-nowrap gap-3">
                <small class="text-muted">
                    @if ($members > $recipients->count())
                        {{ $members - $recipients->count() }} khách khác của nhóm không có trong danh sách vì đã huỷ nhận email hoặc {{ $segment->isOfferedOnce() ? 'đã nhận ưu đãi trước đó' : "vừa nhận ưu đãi trong {$quietDays} ngày qua" }}.
                    @endif
                </small>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-dismiss="modal">Xong</button>
            </div>
        </div>
    </div>
</div>
