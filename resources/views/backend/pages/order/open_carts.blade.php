@extends('backend.index')

@section('title')
    Giỏ hàng đang treo
@endsection

@section('content')
    @php
        use App\Models\CartReminderModel;
        use App\Services\CartReminders\OpenCartBoard;
        use App\Services\ShopSettings;

        $tenLan = [
            CartReminderModel::FIRST => 'Lần 1',
            CartReminderModel::SECOND => 'Lần 2',
            CartReminderModel::OFFER => 'Tặng mã',
        ];
        $gioiHan = ShopSettings::CART_REMINDER_LIMITS;
        $duocTangMa = auth()->user()->can('Quản trị Mã giảm giá');
        $tien = fn (int $soTien) => number_format($soTien, 0, ',', '.').'đ';
        $dieuKien = 'Giỏ được đề xuất tặng mã khi đủ cả 4 điều kiện:'
            .'<br>• Đã nhận đủ '.($config['second_enabled'] ? 2 : 1).' email nhắc tự động mà chưa mua'
            .'<br>• Có mở ít nhất một email nhắc'
            .'<br>• Giỏ từ '.$tien($config['offer_min_value']).' trở lên'
            .'<br>• Chưa nhận mã nhắc giỏ trong '.$config['offer_cooldown_days'].' ngày gần nhất';
    @endphp

    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Quản trị /</span> Giỏ hàng đang treo</h4>

    <div class="row mb-2">
        <div class="col-md-4 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Giỏ đang treo</div>
                    <h4 class="mb-0">{{ $carts->total() }}</h4>
                    <div class="text-muted small">trong {{ $config['max_age_days'] }} ngày qua</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Tổng giá trị</div>
                    <h4 class="mb-0">{{ $tien($total) }}</h4>
                    <div class="text-muted small">tính theo giá và tồn kho hiện tại</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Đề xuất tặng mã</div>
                    <h4 class="mb-0">{{ $suggested }}</h4>
                    <div class="text-muted small">giỏ đủ điều kiện bên dưới</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-border-top">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Giỏ của khách đã đăng nhập, chưa đặt hàng</h5>
            <form method="GET" action="{{ route('open_cart.index') }}" class="d-flex flex-wrap align-items-center gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="chi-de-xuat" name="de-xuat" value="1"
                        @checked($suggestedOnly) onchange="this.form.submit()">
                    <label class="form-check-label" for="chi-de-xuat">Chỉ giỏ được đề xuất</label>
                    <i class="bx bx-help-circle text-muted align-middle" tabindex="0" role="button" aria-label="Điều kiện đề xuất"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-custom-class="tooltip-de-xuat"
                        title="{{ $dieuKien }}"></i>
                </div>
                <select name="sap-xep" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="{{ OpenCartBoard::SORT_VALUE }}" @selected($sort === OpenCartBoard::SORT_VALUE)>Giá trị lớn nhất</option>
                    <option value="{{ OpenCartBoard::SORT_RECENT }}" @selected($sort === OpenCartBoard::SORT_RECENT)>Mới bỏ quên nhất</option>
                </select>
            </form>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Bấm "Tặng mã" để gửi ngay cho khách một mã dùng một lần, giỏ đó sẽ không nhận email nhắc tự động nữa.
                <a href="{{ route('setting.edit') }}#tab-nhac-gio-hang">Cấu hình nhắc giỏ hàng</a>
            </p>

            @if ($carts->isEmpty())
                <p class="text-muted mb-0">Không có giỏ hàng nào đang treo.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th style="min-width: 240px;">Sản phẩm</th>
                                <th class="text-end">Giá trị</th>
                                <th>Email đã gửi</th>
                                <th style="min-width: 250px;">Tặng mã giảm giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($carts as $gio)
                                <tr>
                                    <td>
                                        {{ $gio['user']->name }}
                                        <div class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $gio['user']->email }}">{{ $gio['user']->email }}</div>
                                        @if ($gio['optedOut'])
                                            <span class="badge bg-label-secondary">Đã huỷ nhận email</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse (array_slice($gio['lines'], 0, 3) as $dong)
                                            @include('backend.pages.order.partials.open_cart_line')
                                        @empty
                                            <span class="text-muted small">Hàng trong giỏ đã hết</span>
                                        @endforelse
                                        @if (count($gio['lines']) > 3)
                                            <div class="collapse" id="gio-{{ $gio['cart']->key }}">
                                                @foreach (array_slice($gio['lines'], 3) as $dong)
                                                    @include('backend.pages.order.partials.open_cart_line')
                                                @endforeach
                                            </div>
                                            <a class="small toggle-lines collapsed" data-bs-toggle="collapse" href="#gio-{{ $gio['cart']->key }}"
                                                role="button" aria-expanded="false" aria-controls="gio-{{ $gio['cart']->key }}">
                                                <span class="khi-dong">Xem thêm {{ count($gio['lines']) - 3 }} sản phẩm</span>
                                                <span class="khi-mo">Thu gọn</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <span class="fw-semibold">{{ $tien($gio['value']) }}</span>
                                        <div class="text-muted small" title="{{ $gio['cart']->lastActivity->format('H:i d/m/Y') }}">
                                            bỏ quên {{ $gio['cart']->lastActivity->locale('vi')->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        @forelse ($gio['reminders']->sortKeys() as $buoc => $email)
                                            <div class="small">
                                                {{ $tenLan[$buoc] }}:
                                                @if ($email->clicked_at)
                                                    <span class="text-success">đã bấm</span>
                                                @elseif ($email->opened_at)
                                                    <span class="text-primary">đã mở</span>
                                                @else
                                                    <span class="text-muted">chưa mở</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-muted small">Chưa gửi</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($gio['reasons'] !== [])
                                            <div class="small mb-2">
                                                <span class="badge bg-label-warning">Đề xuất</span>
                                                <div class="text-muted mt-1">{{ implode(' · ', $gio['reasons']) }}</div>
                                            </div>
                                        @endif

                                        @if ($gio['reminders']->has(CartReminderModel::OFFER))
                                            @php $daTang = $gio['reminders'][CartReminderModel::OFFER]; @endphp
                                            <span class="small">Đã tặng {{ $daTang->coupon?->coupon_value }}%
                                                ({{ $daTang->coupon?->coupon_code }}) lúc {{ $daTang->sent_at->format('H:i d/m') }}</span>
                                            @if ($daTang->coupon && ($dieuKienMa = app(\App\Services\CartPricingService::class)->couponTerms($daTang->coupon)))
                                                <div class="text-muted small">{{ $dieuKienMa }}</div>
                                            @endif
                                        @elseif ($gio['optedOut'] || $gio['lines'] === [])
                                            <span class="text-muted small">Không gửi được</span>
                                        @elseif ($duocTangMa)
                                            @if ($gio['lastOffer'])
                                                <div class="text-muted small mb-1">Khách đã nhận mã nhắc giỏ ngày {{ $gio['lastOffer']->format('d/m/Y') }}</div>
                                            @endif
                                            <button type="button" class="btn btn-sm {{ $gio['reasons'] !== [] ? 'btn-warning' : 'btn-outline-secondary' }} js-tang-ma"
                                                data-action="{{ route('open_cart.offer', $gio['user']->user_id) }}"
                                                data-user="{{ $gio['user']->user_id }}"
                                                data-name="{{ $gio['user']->name }}"
                                                data-value="{{ $gio['value'] }}"
                                                data-min-order="{{ $gio['minOrder'] }}">
                                                Tặng mã
                                            </button>
                                        @else
                                            <span class="text-muted small">Cần quyền Quản trị Mã giảm giá</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $carts->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            @endif
        </div>
    </div>

    @if ($duocTangMa)
        <div class="modal fade" id="modal-tang-ma" tabindex="-1" aria-labelledby="modal-tang-ma-tieu-de" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="form-tang-ma" action="">
                    @csrf
                    <input type="hidden" name="offer_user" value="{{ old('offer_user') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-tang-ma-tieu-de">Tặng mã cho <span data-field="name"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Giỏ hiện có giá trị <b data-field="value"></b>. Mã chỉ giảm trên tiền hàng, không giảm phí ship.</p>
                        @if ($errors->any() && old('offer_user'))
                            <div class="alert alert-danger small py-2">
                                @foreach ($errors->all() as $loi)
                                    <div>{{ $loi }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="tm-percent">Mức giảm</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tm-percent" name="coupon_percent" required
                                        min="{{ $gioiHan['coupon_percent'][0] }}" max="{{ $gioiHan['coupon_percent'][1] }}"
                                        value="{{ old('coupon_percent', $config['coupon_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="tm-max">Giảm tối đa</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tm-max" name="max_discount" min="1000" step="1000"
                                        placeholder="Không giới hạn" value="{{ old('max_discount') }}">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="tm-min">Đơn tối thiểu</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tm-min" name="min_order" required min="0" step="1000"
                                        value="{{ old('min_order') }}">
                                    <span class="input-group-text">đ</span>
                                </div>
                                <div class="form-text">Gợi ý 90% giá trị giỏ.</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="tm-days">Hiệu lực</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tm-days" name="coupon_days" required
                                        min="{{ $gioiHan['coupon_days'][0] }}" max="{{ $gioiHan['coupon_days'][1] }}"
                                        value="{{ old('coupon_days', $config['coupon_days']) }}">
                                    <span class="input-group-text">ngày</span>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-secondary small mt-3 mb-0" data-field="summary"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-warning">Tặng mã và gửi email</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('css-backend')
    <style>
        .tooltip-de-xuat .tooltip-inner {
            max-width: 340px;
            text-align: left;
        }

        .stock-trigger {
            cursor: help;
            border-bottom: 1px dotted currentColor;
        }

        .stock-popover {
            max-width: 360px;
            font-size: 12px;
        }

        .toggle-lines.collapsed .khi-mo,
        .toggle-lines:not(.collapsed) .khi-dong {
            display: none;
        }
    </style>
@endpush

@push('script-backend')
    <script>
        // The card is rendered by the server from the shop's own data, so it
        // needs no sanitising, and Bootstrap's would strip the layout.
        (function () {
            const hop = document.getElementById('modal-tang-ma');
            if (!hop) return;
            const form = document.getElementById('form-tang-ma');
            const o = (ten) => form.elements[ten];
            const dong = (v) => new Intl.NumberFormat('vi-VN').format(v) + 'đ';
            const modal = bootstrap.Modal.getOrCreateInstance(hop);

            // What the customer will read, so the admin sees the deal before sending it.
            function tomTat() {
                const phanTram = +o('coupon_percent').value || 0;
                const toiDa = +o('max_discount').value || 0;
                const toiThieu = +o('min_order').value || 0;
                const hetHan = new Date(Date.now() + (+o('coupon_days').value || 0) * 864e5).toLocaleDateString('vi-VN');
                hop.querySelector('[data-field="summary"]').textContent =
                    'Khách được giảm ' + phanTram + '% tiền hàng'
                    + (toiDa ? ', tối đa ' + dong(toiDa) : '')
                    + (toiThieu ? ', khi tiền hàng từ ' + dong(toiThieu) : '')
                    + ', dùng một lần, hết hạn ngày ' + hetHan + '.';
            }

            function moCho(nut, giuGiaTri) {
                form.action = nut.dataset.action;
                o('offer_user').value = nut.dataset.user;
                hop.querySelector('[data-field="name"]').textContent = nut.dataset.name;
                hop.querySelector('[data-field="value"]').textContent = dong(nut.dataset.value);
                if (!giuGiaTri) {
                    o('min_order').value = nut.dataset.minOrder;
                    o('max_discount').value = '';
                }
                tomTat();
                modal.show();
            }

            document.querySelectorAll('.js-tang-ma').forEach((nut) => nut.addEventListener('click', () => moCho(nut, false)));
            form.addEventListener('input', tomTat);

            // Back from a refused offer: reopen it as it was sent, errors and all.
            const lanTruoc = document.querySelector('.js-tang-ma[data-user="' + o('offer_user').value + '"]');
            if (lanTruoc && hop.querySelector('.alert-danger')) moCho(lanTruoc, true);
        })();

        document.querySelectorAll('.stock-trigger').forEach(function (ten) {
            const the = ten.parentElement.querySelector('template.stock-card');
            if (!the) return;
            new bootstrap.Popover(ten, {
                trigger: 'hover focus',
                placement: 'right',
                container: 'body',
                html: true,
                sanitize: false,
                customClass: 'stock-popover',
                content: function () { return the.innerHTML; },
            });
        });
    </script>
@endpush
