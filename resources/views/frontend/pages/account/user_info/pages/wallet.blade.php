@extends('frontend.pages.account.user_info.layout.user_info_layout')
@section('content_user')
    @php
        use App\Models\WalletTopupModel;
        use App\Models\WalletTransactionModel;
        use App\Models\WalletWithdrawalModel;
    @endphp

    @push('css-access')
        <style>
            /* Bootstrap rules a line under every modal header and over every
               footer. On a form this short the two lines cut the dialog into
               three empty boxes, so the money form is styled from the padding
               out instead. */
            .wallet-modal .modal-content {
                border: none;
                border-radius: 2px;
            }

            .wallet-modal .modal-header,
            .wallet-modal .modal-footer {
                border: none;
            }

            .wallet-modal .modal-header {
                padding: 1.5rem 1.5rem 0;
            }

            .wallet-modal .modal-body {
                padding: 1.5rem;
            }

            .wallet-modal .modal-footer {
                padding: 0 1.5rem 1.5rem;
                gap: .5rem;
            }

            .wallet-modal .modal-title {
                font-weight: 700;
            }

            .wallet-modal .form-label {
                font-weight: 600;
                color: var(--color-secondary);
                margin-bottom: .5rem;
            }

            .wallet-modal .form-control {
                border-radius: 2px;
                padding: .6rem .9rem;
            }

            .wallet-modal .form-control:focus {
                border-color: var(--color-orange);
                box-shadow: none;
            }

            .wallet-amount {
                display: flex;
                align-items: center;
                border: 1px solid #dee2e6;
                background-color: #fff;
            }

            .wallet-amount:focus-within {
                border-color: var(--color-orange);
            }

            .wallet-amount input {
                flex: 1;
                min-width: 0;
                border: none;
                outline: none;
                padding: .7rem .9rem;
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--color-secondary);
                /* The spinner arrows land on top of the đ suffix. */
                -moz-appearance: textfield;
            }

            .wallet-amount input::-webkit-outer-spin-button,
            .wallet-amount input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            .wallet-amount__unit {
                padding: 0 1rem;
                color: var(--color-text);
            }

            .wallet-chips {
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
                margin-top: .75rem;
            }

            .wallet-chip {
                border: 1px solid #dee2e6;
                background-color: #fff;
                color: var(--color-secondary);
                padding: .35rem .85rem;
                font-size: .875rem;
                transition: border-color .2s ease, color .2s ease, background-color .2s ease;
            }

            .wallet-chip:hover {
                border-color: var(--color-orange);
                color: var(--color-orange);
            }

            .wallet-chip:focus-visible {
                outline: 2px solid var(--color-orange);
                outline-offset: 2px;
            }

            .wallet-chip.is-active {
                border-color: var(--color-orange);
                color: var(--color-orange);
                background-color: rgba(253, 126, 20, .06);
            }

            .wallet-gateways {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
            }

            .wallet-gateway {
                display: flex;
                align-items: center;
                gap: .75rem;
                margin: 0;
                padding: .75rem 1rem;
                border: 1px solid #dee2e6;
                cursor: pointer;
                transition: border-color .2s ease, background-color .2s ease;
            }

            .wallet-gateway:hover {
                border-color: var(--color-orange);
            }

            .wallet-gateway img {
                width: 32px;
                height: 32px;
                object-fit: contain;
            }

            .btn-check:checked + .wallet-gateway {
                border-color: var(--color-orange);
                background-color: rgba(253, 126, 20, .06);
            }

            .btn-check:focus-visible + .wallet-gateway {
                outline: 2px solid var(--color-orange);
                outline-offset: 2px;
            }

            .wallet-gateway__tick {
                margin-left: auto;
                color: var(--color-orange);
                opacity: 0;
            }

            .btn-check:checked + .wallet-gateway .wallet-gateway__tick {
                opacity: 1;
            }

            @media (max-width: 575.98px) {
                .wallet-gateways {
                    grid-template-columns: 1fr;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .wallet-chip,
                .wallet-gateway {
                    transition: none;
                }
            }
        </style>
    @endpush

    <div>
        <div class="mb-3 p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-md-7 col-12">
                    <div class="text-muted">Số dư khả dụng</div>
                    <div class="fw-bold" style="font-size: 2.25rem; line-height: 1.25;">
                        {{ number_format($wallet->balance, 0, ',', '.') }}<span class="fs-5 fw-normal text-muted"> đ</span>
                    </div>
                    <div class="text-muted">Dùng để thanh toán đơn hàng hoặc rút về ngân hàng.</div>
                </div>
                <div class="col-md-5 col-12 d-flex gap-2 justify-content-md-end">
                    <button type="button" class="custom-btn bgc-o text-white" data-bs-toggle="modal"
                        data-bs-target="#napTien">Nạp tiền</button>
                    <button type="button" class="border grey-hover border-1 custom-btn text-dark" data-bs-toggle="modal"
                        data-bs-target="#rutTien" @disabled($wallet->balance < WalletWithdrawalModel::MIN_AMOUNT)>Rút tiền</button>
                </div>
            </div>
        </div>

        <div class="mb-3 bg-white">
            <ul class="nav nav-underline d-flex align-items-center px-3" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60 active" id="vi-giao-dich-tab"
                        data-bs-toggle="pill" data-bs-target="#vi-giao-dich" role="tab" aria-controls="vi-giao-dich"
                        aria-selected="true">Lịch sử giao dịch</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link d-flex align-items-center h-60" id="vi-rut-tien-tab"
                        data-bs-toggle="pill" data-bs-target="#vi-rut-tien" role="tab" aria-controls="vi-rut-tien"
                        aria-selected="false">Yêu cầu rút tiền</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="vi-giao-dich" role="tabpanel" aria-labelledby="vi-giao-dich-tab"
                tabindex="0">
                @if ($transactions->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center my-5">
                            <div class="text-center">
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3 mb-0">Ví chưa có giao dịch nào !</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-3 p-4 bg-white">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-muted">
                                        <th class="fw-normal">Thời gian</th>
                                        <th class="fw-normal">Nội dung</th>
                                        <th class="fw-normal text-end">Số tiền</th>
                                        <th class="fw-normal text-end">Số dư sau</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $giaoDich)
                                        <tr>
                                            <td class="text-nowrap">{{ $giaoDich->created_at?->format('H:i d/m/Y') }}</td>
                                            <td>
                                                {{ $giaoDich->typeLabel() }}
                                                @if ($giaoDich->description)
                                                    <div class="text-muted small">{{ $giaoDich->description }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap fw-semibold"
                                                @style(['color: var(--color-red)' => ! $giaoDich->isCredit()])
                                                @class(['text-success' => $giaoDich->isCredit()])>
                                                {{ $giaoDich->signedAmount() }}
                                            </td>
                                            <td class="text-end text-nowrap">
                                                {{ number_format($giaoDich->balance_after, 0, ',', '.') }} đ
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($transactions->hasPages())
                            <div class="mt-3">
                                {{ $transactions->links('frontend.layouts.partials.pagination') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="vi-rut-tien" role="tabpanel" aria-labelledby="vi-rut-tien-tab" tabindex="0">
                @if ($withdrawals->isEmpty())
                    <div class="mb-3 p-3 bg-white">
                        <div class="d-flex justify-content-center my-5">
                            <div class="text-center">
                                <img class="d-flex m-auto img-emptycart"
                                    src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png"
                                    alt="">
                                <p class="mt-3 mb-0">Bạn chưa gửi yêu cầu rút tiền nào !</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-3 p-4 bg-white">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-muted">
                                        <th class="fw-normal">Thời gian</th>
                                        <th class="fw-normal">Tài khoản nhận</th>
                                        <th class="fw-normal text-end">Số tiền</th>
                                        <th class="fw-normal">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($withdrawals as $yeuCau)
                                        <tr>
                                            <td class="text-nowrap">{{ $yeuCau->created_at->format('H:i d/m/Y') }}</td>
                                            <td>
                                                {{ $yeuCau->bank_name }}
                                                <div class="text-muted small">
                                                    {{ $yeuCau->bank_account }} &middot; {{ $yeuCau->account_holder }}
                                                </div>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                {{ number_format($yeuCau->amount, 0, ',', '.') }} đ
                                            </td>
                                            <td>
                                                {{ $yeuCau->statusLabel() }}
                                                @if ($yeuCau->note)
                                                    <div class="text-muted small">{{ $yeuCau->note }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade wallet-modal" id="napTien" tabindex="-1" aria-labelledby="napTienLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('wallet.topup') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="napTienLabel">Nạp tiền vào ví</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="topup-amount" class="form-label">Số tiền</label>
                            <div class="wallet-amount">
                                <input type="number" id="topup-amount" name="amount"
                                    min="{{ WalletTopupModel::MIN_AMOUNT }}" max="{{ WalletTopupModel::MAX_AMOUNT }}"
                                    step="1000" value="{{ old('amount', 100000) }}">
                                <span class="wallet-amount__unit">đ</span>
                            </div>
                            <div class="wallet-chips">
                                @foreach ([100000, 200000, 500000, 1000000] as $mucTien)
                                    <button type="button" class="wallet-chip" data-amount="{{ $mucTien }}">
                                        {{ number_format($mucTien, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="form-text">
                                Từ {{ number_format(WalletTopupModel::MIN_AMOUNT, 0, ',', '.') }} đ
                                đến {{ number_format(WalletTopupModel::MAX_AMOUNT, 0, ',', '.') }} đ mỗi lần.
                            </div>
                            <div class="form-text d-flex justify-content-between">
                                <span>Số dư sau khi nạp</span>
                                <span class="fw-semibold text-dark" id="topup-after"
                                    data-balance="{{ $wallet->balance }}"></span>
                            </div>
                            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label class="form-label">Cổng thanh toán</label>
                            <div class="wallet-gateways">
                                <input type="radio" class="btn-check" name="gateway" id="topup-momo" value="payUrl"
                                    autocomplete="off" @checked(old('gateway', 'payUrl') === 'payUrl')>
                                <label class="wallet-gateway" for="topup-momo">
                                    <img src="/frontend/img/momo.png" alt="">
                                    <span>MoMo</span>
                                    <i class="bx bx-check-circle fs-5 wallet-gateway__tick"></i>
                                </label>

                                <input type="radio" class="btn-check" name="gateway" id="topup-vnpay" value="redirect"
                                    autocomplete="off" @checked(old('gateway') === 'redirect')>
                                <label class="wallet-gateway" for="topup-vnpay">
                                    <img src="/frontend/img/VNPAY.png" alt="">
                                    <span>VNPay</span>
                                    <i class="bx bx-check-circle fs-5 wallet-gateway__tick"></i>
                                </label>
                            </div>
                            @error('gateway') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="border grey-hover border-1 custom-btn text-dark"
                            data-bs-dismiss="modal">Trở lại</button>
                        <button type="submit" class="custom-btn bgc-o text-white">Tiếp tục</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade wallet-modal" id="rutTien" tabindex="-1" aria-labelledby="rutTienLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('wallet.withdraw') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="rutTienLabel">Rút tiền về ngân hàng</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="withdraw-amount" class="form-label">Số tiền</label>
                            <div class="wallet-amount">
                                <input type="number" id="withdraw-amount" name="amount"
                                    min="{{ WalletWithdrawalModel::MIN_AMOUNT }}" max="{{ $wallet->balance }}"
                                    step="1000" value="{{ old('amount') }}">
                                <span class="wallet-amount__unit">đ</span>
                            </div>
                            <div class="form-text">
                                Tối thiểu {{ number_format(WalletWithdrawalModel::MIN_AMOUNT, 0, ',', '.') }} đ.
                                Số dư trừ ngay khi gửi; cửa hàng từ chối thì tiền trả lại ví.
                            </div>
                            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="withdraw-bank" class="form-label">Ngân hàng</label>
                            <input type="text" class="form-control" id="withdraw-bank" name="bank_name"
                                value="{{ old('bank_name') }}">
                            @error('bank_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="withdraw-account" class="form-label">Số tài khoản</label>
                            <input type="text" class="form-control" id="withdraw-account" name="bank_account"
                                value="{{ old('bank_account') }}">
                            @error('bank_account') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="withdraw-holder" class="form-label">Chủ tài khoản</label>
                            <input type="text" class="form-control" id="withdraw-holder" name="account_holder"
                                value="{{ old('account_holder') }}">
                            @error('account_holder') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="border grey-hover border-1 custom-btn text-dark"
                            data-bs-dismiss="modal">Trở lại</button>
                        <button type="submit" class="custom-btn bgc-o text-white">Gửi yêu cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const napTien = document.getElementById('napTien');
            const soTien = document.getElementById('topup-amount');
            const soDuSau = document.getElementById('topup-after');
            const mucTien = napTien.querySelectorAll('.wallet-chip');
            const soDu = Number(soDuSau.dataset.balance);
            const dinhDang = new Intl.NumberFormat('vi-VN');

            // The field posts a plain integer; the thousand separators are put on
            // here so the number stays readable, and taken off again on submit.
            // It ships as type=number so a browser without this script still
            // gets a numeric keypad and the min/max check.
            soTien.type = 'text';
            soTien.inputMode = 'numeric';

            function doc() {
                return Number(soTien.value.replace(/\D/g, '')) || 0;
            }

            function ve() {
                const nhap = doc();
                soTien.value = nhap ? dinhDang.format(nhap) : '';
                soDuSau.textContent = dinhDang.format(soDu + nhap) + ' đ';
                mucTien.forEach(function (nut) {
                    nut.classList.toggle('is-active', Number(nut.dataset.amount) === nhap);
                });
            }

            mucTien.forEach(function (nut) {
                nut.addEventListener('click', function () {
                    soTien.value = nut.dataset.amount;
                    ve();
                });
            });

            soTien.addEventListener('input', ve);
            soTien.form.addEventListener('submit', function () {
                soTien.value = doc();
            });
            ve();
        })();
    </script>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById(
                    {{ Illuminate\Support\Js::from($errors->hasAny(['bank_name', 'bank_account', 'account_holder']) ? 'rutTien' : 'napTien') }}
                )).show();
            });
        </script>
    @endif
@endsection
