@extends('backend.index')

@section('title')
    Gửi ưu đãi
@endsection

@section('content')
    <div class="py-3 mb-2">
        <h4 class="fw-bold mb-1">
            <a href="{{ route('admin.segments') }}" class="text-muted fw-light">Phân khúc khách hàng /</a> Gửi ưu đãi
        </h4>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <form method="POST" action="{{ route('admin.campaigns.store', $segment) }}" class="card h-100 card-border-top" id="campaign-form">
                @csrf
                <div class="card-body">
                    <div class="border rounded px-3 py-2 mb-3 d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <span class="badge chip {{ $segment->badgeClass() }} me-2">{{ $segment->label() }}</span>
                            <span class="fw-semibold"><span id="so-khach-chon">{{ $recipients->count() }}</span>/{{ $recipients->count() }} khách</span>
                            <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" title="Mỗi khách nhận một mã riêng, dùng một lần.{{ $segment->isOfferedOnce() ? ' Nhóm này chỉ được gửi một lần, khách đã nhận sẽ không nhận lại.' : '' }}"></i>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-nguoi-nhan">Xem và chọn khách</button>
                    </div>
                    @error('recipients') <small class="text-danger fst-italic d-block mb-3">{{ $message }}</small> @enderror
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="campaign-name">Tên chiến dịch</label>
                            <input class="form-control" id="campaign-name" name="name" required maxlength="100" value="{{ old('name', $draft['name']) }}">
                            @error('name') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="campaign-percent">Mức giảm</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="campaign-percent" name="percent" required value="{{ old('percent', $draft['percent']) }}">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('percent') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="campaign-days">Hiệu lực</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="campaign-days" name="days" required value="{{ old('days', $draft['days']) }}">
                                <span class="input-group-text">ngày</span>
                            </div>
                            @error('days') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="campaign-min">Đơn tối thiểu</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="campaign-min" name="min_order" step="1000" placeholder="Không yêu cầu" value="{{ old('min_order', $draft['min_order']) }}">
                                <span class="input-group-text">đ</span>
                            </div>
                            @error('min_order') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="campaign-max">Giảm tối đa</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="campaign-max" name="max_discount" step="1000" placeholder="Không giới hạn" value="{{ old('max_discount', $draft['max_discount']) }}">
                                <span class="input-group-text">đ</span>
                            </div>
                            @error('max_discount') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label" for="campaign-subject">Tiêu đề email</label>
                            <input class="form-control" id="campaign-subject" name="subject" required maxlength="150" value="{{ old('subject', $draft['subject']) }}">
                            @error('subject') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label" for="campaign-message">Lời nhắn</label>
                            <textarea class="form-control" id="campaign-message" name="message" rows="3" required maxlength="1000">{{ old('message', $draft['message']) }}</textarea>
                            @error('message') <small class="text-danger fst-italic">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="nut-gui" @disabled($recipients->isEmpty())>Gửi cho {{ $recipients->count() }} khách</button>
                        <a href="{{ route('admin.segments') }}" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card h-100 card-border-top">
                <div class="card-header"><h5 class="mb-0">Xem trước email</h5></div>
                <div class="card-body p-0" id="campaign-preview">
                    @include('mail.campaignBody', [
                        'name' => 'Minh Anh',
                        'lead' => old('message', $draft['message']),
                        'percent' => old('percent', $draft['percent']),
                        'minOrder' => old('min_order', $draft['min_order']),
                        'maxDiscount' => old('max_discount', $draft['max_discount']),
                        'code' => 'UUDAI7K2MQX',
                        'expires' => now()->addDays((int) old('days', $draft['days'])),
                        'visitUrl' => '#',
                        'unsubscribeUrl' => '#',
                    ])
                </div>
            </div>
        </div>
    </div>

    @include('backend.pages.statistical.partials.campaign_recipients')
@endsection

@include('backend.pages.statistical.partials.segment_styles')

@push('script-backend')
    <script>
        (function () {
            const form = document.querySelector('#campaign-form');
            const preview = document.querySelector('#campaign-preview');
            const show = (name, text) => { preview.querySelector('[data-preview="' + name + '"]').textContent = text; };

            const hop = [...document.querySelectorAll('.js-nguoi-nhan input')];
            const hang = [...document.querySelectorAll('.js-nguoi-nhan')];
            const tatCa = document.querySelector('#chon-tat-ca');
            const nutGui = document.querySelector('#nut-gui');

            function capNhatNguoiNhan() {
                const chon = hop.filter(o => o.checked).length;
                document.querySelector('#so-khach-chon').textContent = chon;
                nutGui.textContent = 'Gửi cho ' + chon + ' khách';
                nutGui.disabled = chon === 0;
                tatCa.checked = chon === hop.length && chon > 0;
                tatCa.indeterminate = chon > 0 && chon < hop.length;
                hang.forEach(h => h.classList.toggle('opacity-50', !h.querySelector('input').checked));
            }

            hop.forEach(o => o.addEventListener('change', capNhatNguoiNhan));
            tatCa.addEventListener('change', () => {
                hang.filter(h => !h.classList.contains('d-none')).forEach(h => { h.querySelector('input').checked = tatCa.checked; });
                capNhatNguoiNhan();
            });
            document.querySelector('#tim-nguoi-nhan').addEventListener('input', event => {
                const tu = event.target.value.trim().toLowerCase();
                hang.forEach(h => h.classList.toggle('d-none', !h.dataset.tim.includes(tu)));
            });
            capNhatNguoiNhan();

            preview.querySelectorAll('a').forEach(link => link.addEventListener('click', event => event.preventDefault()));

            form.addEventListener('input', () => {
                const max = +form.elements.max_discount.value;
                const min = +form.elements.min_order.value;
                const money = value => new Intl.NumberFormat('vi-VN').format(value) + ' VNĐ';
                const terms = [];
                if (min) terms.push('Đơn từ ' + money(min));
                if (max) terms.push((min ? 'giảm' : 'Giảm') + ' tối đa ' + money(max));
                show('how', min ? 'Nhập mã ở giỏ hàng khi đơn đủ điều kiện.' : 'Mã được áp sẵn khi bạn bấm nút bên dưới.');
                show('message', form.elements.message.value);
                show('percent', (+form.elements.percent.value || 0) + '%');
                show('terms', terms.length ? terms.join(', ') + ', tính trên tiền hàng.' : 'Tính trên tiền hàng.');
                show('expires', new Date(Date.now() + (+form.elements.days.value || 0) * 864e5).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }));
            });
        })();
    </script>
@endpush
