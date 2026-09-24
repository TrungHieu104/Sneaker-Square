@extends('backend.index')

@section('title')
    Giả lập GHN
@endsection

@php
    use App\Http\Controllers\Backend\GhnSimulatorController as Sim;
    use App\Services\Shipping\GhnStatus;
@endphp

@section('content')
    <h4 class="fw-bold py-3 mb-3">Giả lập GHN <span class="badge bg-label-warning align-middle fs-6">chỉ ở local</span></h4>

    <div class="card mb-4 card-border-top">
        <div class="card-body">
            <p class="mb-2">
                Gửi vào hệ thống đúng request mà GHN gọi webhook, rồi xử lý bằng chính code nhận webhook thật.
                Chỉ bỏ qua bước HTTP và token trên URL.
            </p>
            <p class="mb-2">
                Sandbox GHN chỉ cho shop đổi <b>4 trạng thái</b>: <code>ready_to_pick</code>, <code>cancel</code>,
                <code>storing</code>, <code>return</code>. Các chặng còn lại do nhân viên GHN quét ngoài thực địa,
                shop không gọi được — nên phải giả lập ở đây. Trong 4 trạng thái đó, thực tế trên sandbox chỉ
                <code>cancel</code> dùng được: <code>storing</code> và <code>return</code> được cấp quyền nhưng
                GHN đòi vận đơn đã qua bước lấy hàng, mà bước đó sandbox không chạy.
            </p>
            <p class="mb-2">
                Trạng thái nào GHN cho gọi thì có thêm nút <b>GHN thật</b>: nút này gọi API của GHN, đổi vận đơn
                trên hệ thống GHN. Hành trình trong shop chỉ đổi khi GHN gọi webhook về, nên phải bật tunnel và
                khai báo URL webhook trên trang GHN. Nút <b>Giả lập</b> thì ghi thẳng vào hệ thống, không đụng GHN.
            </p>
            <p class="mb-0 text-muted small">
                Tên và thứ tự trạng thái lấy từ payload GHN đã gửi về hệ thống.
                Trang này chỉ bật khi <code>GHN_SIMULATOR=true</code>, ở production luôn trả 404.
            </p>
        </div>
    </div>

    @if ($parcels->isEmpty())
        <div class="alert alert-info">Chưa có đơn hàng nào có mã vận đơn. Tạo hoặc gắn vận đơn cho một đơn trước.</div>
    @else
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <form method="GET" action="{{ route('ghn_simulator.index') }}" class="mb-3">
                            <label for="sim-parcel" class="form-label">Vận đơn</label>
                            <select id="sim-parcel" name="ma" class="form-select" onchange="this.form.submit()">
                                @foreach ($parcels as $ma => $p)
                                    <option value="{{ $ma }}" @selected($ma === $code)>{{ $ma }} · {{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        </form>

                        @if ($parcel)
                            <div class="mb-3">
                                Trạng thái hiện tại:
                                <b>{{ $parcel['status'] ? GhnStatus::label($parcel['status']) : 'Chưa có' }}</b>
                                <div>
                                    <a href="{{ route('orders.edit', encrypt($parcel['order_id'])) }}" target="_blank">Mở đơn hàng</a>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('ghn_simulator.auto_complete') }}" class="mb-3">
                                @csrf
                                <input type="hidden" name="ma" value="{{ $code }}">
                                <label for="sim-days" class="form-label">Chạy tự hoàn thành đơn, giả định đã qua</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="sim-days" name="days_later" class="form-control" min="0" max="60"
                                        value="{{ app(\App\Services\ShopSettings::class)->autoCompleteDays() }}">
                                    <span class="input-group-text">ngày</span>
                                    <button type="submit" class="btn btn-outline-secondary">Chạy</button>
                                </div>
                                <div class="form-text">
                                    Chạy ngay lệnh <code>orders:auto-complete</code> như thể đã qua số ngày này kể từ bây giờ,
                                    không cần đợi scheduler. Không đổi dữ liệu của GHN.
                                </div>
                            </form>

                            <h6 class="mt-4">Hành trình</h6>
                            @forelse ($events as $e)
                                <div class="small mb-1">
                                    <span class="text-muted">{{ $e->happened_at->format('H:i d/m/Y') }}</span>
                                    · {{ GhnStatus::label($e->status) }}
                                </div>
                            @empty
                                <div class="small text-muted">Chưa có sự kiện nào.</div>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>

            @if ($parcel)
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('ghn_simulator.send') }}" id="sim-form">
                                @csrf
                                <input type="hidden" name="ma" value="{{ $code }}">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nhóm</th>
                                                <th>Trạng thái</th>
                                                <th>Sandbox GHN thật</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (Sim::GROUPS as $nhom => $statuses)
                                                @foreach ($statuses as $i => $status)
                                                    <tr @class(['table-active' => $parcel['status'] === $status])>
                                                        @if ($i === 0)
                                                            <td rowspan="{{ count($statuses) }}" class="fw-semibold">{{ $nhom }}</td>
                                                        @endif
                                                        <td>
                                                            {{ GhnStatus::label($status) }}
                                                            <div class="small text-muted"><code>{{ $status }}</code></div>
                                                        </td>
                                                        <td class="small">
                                                            @if ($sandbox = Sim::ON_SANDBOX[$status] ?? null)
                                                                @if ($sandbox['reachable'])
                                                                    <span class="text-success">Có</span>
                                                                @else
                                                                    <span class="text-warning">Có API, chưa tới được</span>
                                                                @endif
                                                                · {{ $sandbox['note'] }}
                                                            @else
                                                                <span class="text-danger">Không</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end text-nowrap">
                                                            <button type="submit" name="status" value="{{ $status }}"
                                                                class="btn btn-sm btn-primary">Giả lập</button>
                                                            @if (Sim::ON_SANDBOX[$status]['api'] ?? null)
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#goiGhnThat"
                                                                    data-status="{{ $status }}"
                                                                    data-label="{{ GhnStatus::label($status) }}">GHN thật</button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="modal fade" id="goiGhnThat" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('ghn_simulator.real') }}">
                    @csrf
                    <input type="hidden" name="ma" value="{{ $code }}">
                    <input type="hidden" name="status" id="goi-ghn-status">
                    <div class="modal-header">
                        <h5 class="modal-title">Gọi GHN thật</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p>Chuyển vận đơn <b>{{ $code }}</b> sang <b id="goi-ghn-label"></b> trên hệ thống GHN.</p>
                        <p class="mb-0 text-muted small">
                            Đây là thao tác thật trên vận đơn GHN, không hoàn tác từ trang này được. Hành trình
                            trong shop chỉ đổi khi GHN gọi webhook về.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-danger">Gọi GHN</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                const hopThoai = document.getElementById('goiGhnThat');

                hopThoai.addEventListener('show.bs.modal', function (e) {
                    hopThoai.querySelector('#goi-ghn-status').value = e.relatedTarget.dataset.status;
                    hopThoai.querySelector('#goi-ghn-label').textContent = e.relatedTarget.dataset.label;
                });
            })();
        </script>
    @endif
@endsection
