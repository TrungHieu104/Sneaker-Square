@extends('backend.index')

@section('title')
    Ví khách hàng
@endsection

@section('content')
    @php
        use App\Models\WalletWithdrawalModel;

        $tabs = [
            WalletWithdrawalModel::REQUESTED => 'Chờ chuyển',
            WalletWithdrawalModel::PAID => 'Đã chuyển',
            WalletWithdrawalModel::REJECTED => 'Đã từ chối',
        ];
    @endphp

    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Quản trị /</span> Ví khách hàng</h4>

    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Đang giữ hộ khách</div>
                    <h4 class="mb-0">{{ number_format($held, 0, ',', '.') }}đ</h4>
                    <div class="text-muted small">tổng số dư mọi ví</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Chờ chuyển khoản</div>
                    <h4 class="mb-0">{{ number_format($pending, 0, ',', '.') }}đ</h4>
                    <div class="text-muted small">{{ $counts[WalletWithdrawalModel::REQUESTED] }} yêu cầu</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Khách đã nạp</div>
                    <h4 class="mb-0">{{ number_format($toppedUp, 0, ',', '.') }}đ</h4>
                    <div class="text-muted small">cộng dồn từ trước tới nay</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card card-border-top h-100">
                <div class="card-body">
                    <div class="text-muted small">Đã hoàn vào ví</div>
                    <h4 class="mb-0">{{ number_format($refunded, 0, ',', '.') }}đ</h4>
                    <div class="text-muted small">huỷ đơn và trả hàng</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-border-top">
        <h5 class="card-header">Yêu cầu rút tiền</h5>
        <div class="card-body">
            <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
                @foreach ($tabs as $ma => $ten)
                    <li class="nav-item">
                        <a class="nav-link {{ $status === $ma ? 'active' : '' }}"
                            href="{{ route('wallet_admin.index', ['status' => $ma]) }}">
                            {{ $ten }} <span class="badge bg-label-secondary">{{ $counts[$ma] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($requests->isEmpty())
                <p class="text-muted mb-0">Không có yêu cầu nào ở mục này.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Tài khoản nhận</th>
                                <th class="text-end">Số tiền</th>
                                <th>Gửi lúc</th>
                                <th>Xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $yeuCau)
                                <tr>
                                    <td>
                                        {{ $yeuCau->wallet?->user?->name }}
                                        <div class="text-muted small">{{ $yeuCau->wallet?->user?->email }}</div>
                                        <div class="text-muted small">Số dư còn lại: {{ number_format((int) $yeuCau->wallet?->balance, 0, ',', '.') }}đ</div>
                                    </td>
                                    <td>
                                        <b>{{ $yeuCau->bank_name }}</b>
                                        <div>{{ $yeuCau->bank_account }}</div>
                                        <div class="text-muted small">{{ $yeuCau->account_holder }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format($yeuCau->amount, 0, ',', '.') }}đ</td>
                                    <td>{{ $yeuCau->created_at->format('H:i d/m/Y') }}</td>
                                    <td style="min-width: 260px;">
                                        @if ($yeuCau->status === WalletWithdrawalModel::REQUESTED)
                                            <form action="{{ route('wallet_admin.withdrawal_paid', $yeuCau->withdrawal_id) }}" method="POST" class="mb-2">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" name="note" placeholder="Mã giao dịch (không bắt buộc)">
                                                    <button type="submit" class="btn btn-success">Đã chuyển</button>
                                                </div>
                                            </form>
                                            <form action="{{ route('wallet_admin.withdrawal_reject', $yeuCau->withdrawal_id) }}" method="POST">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" name="reason" placeholder="Lý do từ chối">
                                                    <button type="submit" class="btn btn-outline-danger">Từ chối</button>
                                                </div>
                                            </form>
                                        @else
                                            <span class="badge bg-label-{{ $yeuCau->badge() }}">{{ $yeuCau->statusLabel() }}</span>
                                            <div class="text-muted small">{{ $yeuCau->decided_at?->format('H:i d/m/Y') }}</div>
                                            @if ($yeuCau->note)
                                                <div class="text-muted small">{{ $yeuCau->note }}</div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $requests->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            @endif
        </div>
    </div>
@endsection
